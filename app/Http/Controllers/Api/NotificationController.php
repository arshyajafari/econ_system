<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmployeeActivityType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\StoreSystemMessageRequest;
use App\Http\Resources\SystemNotificationResource;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
use App\Services\NotificationRecipientResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 50);

        $user = $request->user();

        $query = $this->canManageMessages($user)
            ? $this->visibleNotificationsForManager($user)
            : $this->visibleNotifications($user);

        return SystemNotificationResource::collection(
            $query->latest('created_at')->paginate($perPage)
        );
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => $this->visibleNotifications($request->user())
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markRead(Request $request, string $notification)
    {
        $model = $this->visibleNotifications($request->user())
            ->whereKey($notification)
            ->firstOrFail();
        $model->markAsRead();

        return SystemNotificationResource::make($model->fresh());
    }

    public function markAllRead(Request $request)
    {
        $this->visibleNotifications($request->user())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Return only notifications that are actually addressed to the
     * authenticated user.
     *
     * Notifications are stored as individual database rows, so the
     * notifiable user is already a strong boundary. The explicit recipient
     * metadata is an additional defense-in-depth check that prevents a
     * targeted message from becoming visible if recipient rows were ever
     * created too broadly.
     */
    private function canManageMessages(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN->value,
            Role::ACCOUNTANT->value,
        ]);
    }

    private function visibleNotificationsForManager(User $user)
    {
        /*
         * Admins and accountants have management visibility over the complete
         * system-message history. Other users are restricted to
         * visibleNotifications(), which only returns messages addressed to
         * their own account.
         */
        $query = DatabaseNotification::query()
            ->where('type', SystemMessageNotification::class);

        /*
         * A system message is stored once per recipient. Managers must see
         * one row per sent message, not one row per recipient. Grouping by the
         * message UUID keeps the inbox compact while the fallback preserves
         * legacy notifications that do not have message_id.
         */
        return $query
            ->selectRaw(
                "MAX(id) AS id,
                 MAX(type) AS type,
                 MAX(notifiable_type) AS notifiable_type,
                 MAX(notifiable_id) AS notifiable_id,
                 MAX(data) AS data,
                 MAX(read_at) AS read_at,
                 MAX(created_at) AS created_at,
                 MAX(updated_at) AS updated_at"
            )
            ->groupByRaw(
                "COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(data, '$.message_id')),
                    CONCAT('legacy:', id)
                )"
            );
    }

    private function visibleNotifications(User $user)
    {
        $query = $user->notifications();
        $this->applyRecipientVisibility($query, $user);

        return $query;
    }

    private function applyRecipientVisibility($query, User $user): void
    {
        $activityType = $user->employee?->activity_type?->value;

        $query->where(function ($query) use ($user, $activityType): void {
            $query
                ->whereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(data, '$.target_type')) IS NULL"
                )
                ->orWhereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(data, '$.target_type')) = ?",
                    ['all']
                )
                ->orWhere(function ($query) use ($user): void {
                    $query
                        ->whereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(data, '$.target_type')) = ?",
                            ['users']
                        )
                        ->whereRaw(
                            "JSON_CONTAINS(JSON_EXTRACT(data, '$.target_values'), JSON_QUOTE(?))",
                            [$user->getAttribute('public_id')]
                        );
                })
                ->orWhere(function ($query) use ($activityType): void {
                    if ($activityType === null) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    $query
                        ->whereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(data, '$.target_type')) = ?",
                            ['positions']
                        )
                        ->whereRaw(
                            "JSON_CONTAINS(JSON_EXTRACT(data, '$.target_values'), JSON_QUOTE(?))",
                            [$activityType]
                        );
                });
        });
    }

    public function recipients(Request $request)
    {
        abort_unless($this->canManageMessages($request->user()), 403);

        $users = User::query()
            ->active()
            ->with('employee:id,public_id,first_name,last_name')
            ->get(['id', 'public_id', 'employee_id', 'login'])
            ->map(fn (User $user) => [
                'id' => $user->public_id,
                'name' => $user->employee?->full_name ?? $user->login,
            ])
            ->values();

        $positions = collect(EmployeeActivityType::cases())
            ->map(fn (EmployeeActivityType $position) => [
                'value' => $position->value,
                'label' => $position->label(),
            ])
            ->values();

        return response()->json([
            'users' => $users,
            'positions' => $positions,
        ]);
    }

    public function update(Request $request, string $notification)
    {
        abort_unless($this->canManageMessages($request->user()), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $rows = DatabaseNotification::query()
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(data, '$.message_id')) = ?",
                [$notification]
            )
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(data, '$.sender_id')) = ?",
                [(int) $request->user()->getKey()]
            )
            ->get();

        if ($rows->isEmpty()) {
            abort(404);
        }

        foreach ($rows as $row) {
            $payload = $row->data;
            $payload['title'] = $data['title'];
            $payload['body'] = $data['body'];
            $payload['priority'] = $data['priority'];
            $row->data = $payload;
            $row->save();
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $notification)
    {
        abort_unless($this->canManageMessages($request->user()), 403);

        $rows = DatabaseNotification::query()
            ->where('data->message_id', $notification)
            ->where('data->sender_id', (int) $request->user()->getKey())
            ->get();

        if ($rows->isEmpty()) {
            abort(404);
        }

        foreach ($rows as $row) {
            $row->delete();
        }

        return response()->noContent();
    }

    public function send(
        StoreSystemMessageRequest $request,
        NotificationRecipientResolver $recipientResolver,
    ) {
        $data = $request->validated();

        $positionTypes = $data['target_type'] === 'positions'
            ? $recipientResolver->normalizePositions($data['position_types'])
            : [];

        $users = $recipientResolver->resolve(
            targetType: $data['target_type'],
            userIds: $data['user_ids'] ?? [],
            positionTypes: $positionTypes,
        );

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'هیچ کاربر فعالی برای دریافت این پیام پیدا نشد.',
            ], 422);
        }

        $messageId = (string) Str::uuid();
        $targetValues = match ($data['target_type']) {
            'all' => [],
            'users' => array_values(array_unique($data['user_ids'] ?? [])),
            'positions' => $positionTypes,
        };

        foreach ($users as $user) {
            $user->notify(new SystemMessageNotification(
                title: $data['title'],
                body: $data['body'],
                priority: $data['priority'],
                senderId: (int) $request->user()->getKey(),
                messageId: $messageId,
                targetType: $data['target_type'],
                targetValues: $targetValues,
            ));
        }

        return response()->json([
            'success' => true,
            'recipients_count' => $users->count(),
        ], 201);
    }
}
