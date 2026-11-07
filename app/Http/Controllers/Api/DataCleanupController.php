<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\EmployeeLocation;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class DataCleanupController extends Controller
{
    private const RETENTION_DAYS = 32;
    private const BATCH_SIZE = 500;

    private const CATEGORIES = [
        'employee_locations' => 'تاریخچه خام موقعیت کارکنان',
        'sessions' => 'نشست‌های منقضی‌شده',
        'password_reset_tokens' => 'توکن‌های قدیمی بازنشانی رمز',
        'personal_access_tokens' => 'توکن‌های منقضی یا بلااستفاده',
        'devices' => 'دستگاه‌های غیرفعال و توکن‌های مرتبط',
        'cache' => 'Cache منقضی‌شده',
        'cache_locks' => 'قفل‌های Cache منقضی‌شده',
    ];

    public function preview(Request $request)
    {
        $this->authorizeAdmin($request);
        $cutoff = now()->subDays(self::RETENTION_DAYS);
        $counts = [];

        foreach (self::CATEGORIES as $key => $label) {
            $counts[$key] = [
                'label' => $label,
                'count' => $this->queryFor($key, $cutoff)?->count() ?? 0,
            ];
        }

        return response()->json([
            'data' => [
                'retention_days' => self::RETENTION_DAYS,
                'cutoff_at' => $cutoff->toISOString(),
                'categories' => $counts,
                'protected' => [
                    'employee_work_sessions', 'orders', 'order_items', 'invoices',
                    'invoice_items', 'payments', 'customer_transactions',
                    'inventory_batches', 'inventory_movements', 'inventory_adjustments',
                    'scientific_visitor_inventory_transfers', 'visits', 'deliveries',
                ],
            ],
        ]);
    }

    public function history(Request $request)
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'data' => DB::table('data_cleanup_runs')
                ->leftJoin('users', 'users.id', '=', 'data_cleanup_runs.user_id')
                ->select([
                    'data_cleanup_runs.id', 'data_cleanup_runs.categories',
                    'data_cleanup_runs.reason', 'data_cleanup_runs.status',
                    'data_cleanup_runs.deleted_counts', 'data_cleanup_runs.started_at',
                    'data_cleanup_runs.finished_at', 'data_cleanup_runs.failure_message',
                    'users.login as performed_by',
                ])
                ->orderByDesc('data_cleanup_runs.id')
                ->limit(50)
                ->get()
                ->map(function ($run) {
                    $run->categories = json_decode($run->categories, true) ?? [];
                    $run->deleted_counts = json_decode($run->deleted_counts, true) ?? [];
                    return $run;
                }),
        ]);
    }

    public function execute(Request $request)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', 'distinct', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $lock = Cache::lock('data-cleanup:global', 7200);
        if (!$lock->get()) {
            throw ValidationException::withMessages([
                'cleanup' => ['یک عملیات پاک‌سازی دیگر در حال اجراست.'],
            ]);
        }

        $runId = null;
        try {
            $startedAt = now();
            $runId = DB::table('data_cleanup_runs')->insertGetId([
                'user_id' => $request->user()->id,
                'categories' => json_encode(array_values($data['categories'])),
                'reason' => 'پاک‌سازی دوره‌ای برای کاهش حجم دیتابیس',
                'status' => 'running',
                'started_at' => $startedAt,
                'created_at' => $startedAt,
                'updated_at' => $startedAt,
            ]);

            $cutoff = $startedAt->copy()->subDays(self::RETENTION_DAYS);
            $deleted = [];
            foreach ($data['categories'] as $category) {
                $categoryResult = $this->deleteCategory($category, $cutoff);

                if ($category === 'devices') {
                    $deleted['devices'] = $categoryResult['devices'];
                    $deleted['device_tokens'] = $categoryResult['device_tokens'];
                } else {
                    $deleted[$category] = $categoryResult;
                }

                DB::table('data_cleanup_runs')->where('id', $runId)->update([
                    'deleted_counts' => json_encode($deleted),
                    'updated_at' => now(),
                ]);
            }

            DB::table('data_cleanup_runs')->where('id', $runId)->update([
                'status' => 'completed',
                'deleted_counts' => json_encode($deleted),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'عملیات پاک‌سازی با موفقیت انجام شد.',
                'data' => ['run_id' => $runId, 'deleted_counts' => $deleted],
            ]);
        } catch (Throwable $exception) {
            if ($runId) {
                DB::table('data_cleanup_runs')->where('id', $runId)->update([
                    'status' => 'failed',
                    'deleted_counts' => isset($deleted) ? json_encode($deleted) : null,
                    'failure_message' => mb_substr($exception->getMessage(), 0, 4000),
                    'finished_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
    }

    private function queryFor(string $category, $cutoff): ?Builder
    {
        switch ($category) {
            case 'employee_locations':
                return Schema::hasTable('employee_locations')
                    ? DB::table('employee_locations')->where('captured_at', '<', $cutoff)
                    : null;
            case 'sessions':
                return Schema::hasTable('sessions')
                    ? DB::table('sessions')->where('last_activity', '<', $cutoff->timestamp)
                    : null;
            case 'password_reset_tokens':
                return Schema::hasTable('password_reset_tokens')
                    ? DB::table('password_reset_tokens')->where('created_at', '<', $cutoff)
                    : null;
            case 'personal_access_tokens':
                return Schema::hasTable('personal_access_tokens')
                    ? DB::table('personal_access_tokens')->where(function ($query) use ($cutoff) {
                        $query->where(function ($expired) {
                            $expired->whereNotNull('expires_at')
                                ->where('expires_at', '<=', now());
                        })->orWhere(function ($inactive) use ($cutoff) {
                            $inactive->where('last_used_at', '<', $cutoff)
                                ->orWhere(function ($neverUsed) use ($cutoff) {
                                    $neverUsed->whereNull('last_used_at')
                                        ->where('created_at', '<', $cutoff);
                                });
                        });
                    })
                    : null;
            case 'devices':
                if (!Schema::hasTable('devices') || !Schema::hasTable('personal_access_tokens')) {
                    return null;
                }

                $morphType = (new User())->getMorphClass();

                return DB::table('devices')
                    ->where(function ($query) use ($cutoff) {
                        $query->where('last_seen_at', '<', $cutoff)
                            ->orWhere(function ($query) use ($cutoff) {
                                $query->whereNull('last_seen_at')
                                    ->where('created_at', '<', $cutoff);
                            });
                    })
                    ->whereNotExists(function ($query) use ($morphType, $cutoff) {
                        $query->selectRaw('1')->from('personal_access_tokens')
                            ->whereColumn('personal_access_tokens.tokenable_id', 'devices.user_id')
                            ->whereColumn('personal_access_tokens.name', 'devices.device_id')
                            ->where('personal_access_tokens.tokenable_type', $morphType)
                            ->where(function ($valid) {
                                $valid->whereNull('personal_access_tokens.expires_at')
                                    ->orWhere('personal_access_tokens.expires_at', '>', now());
                            })
                            ->where(function ($recent) use ($cutoff) {
                                $recent->where('personal_access_tokens.last_used_at', '>=', $cutoff)
                                    ->orWhere(function ($neverUsed) use ($cutoff) {
                                        $neverUsed->whereNull('personal_access_tokens.last_used_at')
                                            ->where('personal_access_tokens.created_at', '>=', $cutoff);
                                    });
                            });
                    });
            case 'cache':
                return Schema::hasTable('cache')
                    ? DB::table('cache')->where('expiration', '<=', now()->timestamp)
                    : null;
            case 'cache_locks':
                return Schema::hasTable('cache_locks')
                    ? DB::table('cache_locks')->where('expiration', '<=', now()->timestamp)
                    : null;
            default:
                return null;
        }
    }

    private function deleteCategory(string $category, $cutoff): int|array
    {
        $total = 0;
        $hasMore = false;
        $relatedTokenTotal = 0;

        do {
            $query = $this->queryFor($category, $cutoff);
            if (!$query) {
                return $category === 'devices'
                    ? ['devices' => 0, 'device_tokens' => 0]
                    : $total;
            }

            if ($category === 'devices') {
                $devices = (clone $query)
                    ->orderBy('id')
                    ->limit(self::BATCH_SIZE)
                    ->get(['id', 'user_id', 'device_id']);

                if ($devices->isEmpty()) {
                    break;
                }

                $morphType = (new User())->getMorphClass();
                foreach ($devices->groupBy('user_id') as $userId => $userDevices) {
                    $relatedTokenTotal += DB::table('personal_access_tokens')
                        ->where('tokenable_type', $morphType)
                        ->where('tokenable_id', $userId)
                        ->whereIn('name', $userDevices->pluck('device_id'))
                        ->delete();
                }

                $deletedDevices = DB::table('devices')
                    ->whereIn('id', $devices->pluck('id'))
                    ->delete();

                $total += $deletedDevices;
                $hasMore = $devices->count() === self::BATCH_SIZE;
                continue;
            }

            $key = match ($category) {
                'employee_locations' => 'id',
                'sessions' => 'id',
                'password_reset_tokens' => 'email',
                'personal_access_tokens' => 'id',
                'cache', 'cache_locks' => 'key',
            };

            $ids = (clone $query)->orderBy($key)->limit(self::BATCH_SIZE)->pluck($key);
            if ($ids->isEmpty()) {
                break;
            }

            $deleted = match ($category) {
                'employee_locations' => DB::table('employee_locations')->whereIn('id', $ids)->delete(),
                'sessions' => DB::table('sessions')->whereIn('id', $ids)->delete(),
                'password_reset_tokens' => DB::table('password_reset_tokens')->whereIn('email', $ids)->delete(),
                'personal_access_tokens' => DB::table('personal_access_tokens')->whereIn('id', $ids)->delete(),
                'cache' => DB::table('cache')->whereIn('key', $ids)->delete(),
                'cache_locks' => DB::table('cache_locks')->whereIn('key', $ids)->delete(),
            };

            $total += $deleted;
            $hasMore = $ids->count() === self::BATCH_SIZE;
        } while ($hasMore);

        return $category === 'devices'
            ? ['devices' => $total, 'device_tokens' => $relatedTokenTotal]
            : $total;
    }
}