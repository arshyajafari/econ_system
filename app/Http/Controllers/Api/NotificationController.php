<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\StoreSystemMessageRequest;
use App\Http\Resources\SystemNotificationResource;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Role as SpatieRole;

class NotificationController extends Controller {
    public function index(Request $request) {
        $perPage = min(max($request->integer('per_page', 20), 1), 50);

        return SystemNotificationResource::collection(
            $request->user()->notifications()->latest()->paginate($perPage)
        );
    }

    public function unreadCount(Request $request) {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification) {
        $model = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $model->markAsRead();

        return SystemNotificationResource::make($model->fresh());
    }

    public function markAllRead(Request $request) {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function recipients(Request $request) {
        abort_unless($request->user()->hasRole(Role::ADMIN->value), 403);

        $users = User::query()
            ->active()
            ->with('employee:id,public_id,first_name,last_name')
            ->get(['id', 'public_id', 'employee_id', 'login'])
            ->map(fn (User $user) => [
                'id' => $user->public_id,
                'name' => $user->employee?->full_name ?? $user->login,
            ])
            ->values();

        $roles = SpatieRole::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return response()->json([
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, string $notification) {
        abort_unless($request->user()->hasRole(Role::ADMIN->value), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);
        $rows = DatabaseNotification::query()
            ->where('data->message_id', $notification)
            ->where('data->sender_id', (int) $request->user()->getKey())
            ->get();
        if ($rows->isEmpty()) abort(404);
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

    public function destroy(Request $request, string $notification) {
        abort_unless($request->user()->hasRole(Role::ADMIN->value), 403);
        $rows = $request->user()->notifications()
            ->where('data->message_id', $notification)
            ->where('data->sender_id', (int) $request->user()->getKey())
            ->get();
        if ($rows->isEmpty()) abort(404);
        foreach ($rows as $row) $row->delete();
        return response()->noContent();
    }

    public function send(StoreSystemMessageRequest $request) {
        $data = $request->validated();

        $users = match ($data['target_type']) {
            'all' => User::active()->get(),
            'users' => User::active()->whereIn('public_id', $data['user_ids'])->get(),
            'roles' => User::active()->whereHas('roles', function ($query) use ($data) {
                $query->whereIn('name', $data['role_names'])->where('guard_name', 'web');
            })->get(),
        };

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'هیچ کاربر فعالی برای دریافت این پیام پیدا نشد.',
            ], 422);
        }

        $notification = new SystemMessageNotification(
            $data['title'],
            $data['body'],
            $data['priority'],
            (int) $request->user()->getKey(),
            (string) Str::uuid(),
        );

        foreach ($users as $user) {
            $user->notify($notification);
        }

        return response()->json([
            'success' => true,
            'recipients_count' => $users->count(),
        ], 201);
    }
}
