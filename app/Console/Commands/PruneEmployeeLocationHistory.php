<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\EmployeeLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneEmployeeLocationHistory extends Command
{
    protected $signature = 'employee-locations:prune-history';

    protected $description = 'Prune expired sessions, stale device registrations, access tokens, cache entries, and employee location history';

    private const RETENTION_DAYS = 32;

    private const BATCH_SIZE = 1000;

    public function handle(): int
    {
        $now = now();
        $cutoff = $now->copy()->subDays(self::RETENTION_DAYS);

        $locationsDeleted = $this->deleteInBatches(
            fn () => EmployeeLocation::query()
                ->where('captured_at', '<', $cutoff)
                ->orderBy('captured_at')
                ->limit(self::BATCH_SIZE)
                ->pluck('id'),
            fn ($ids) => EmployeeLocation::query()->whereIn('id', $ids)->delete()
        );

        // Session rows are authentication state, not durable business history.
        // Laravel stores last_activity as a Unix timestamp in this table.
        $sessionsDeleted = $this->deleteInBatches(
            fn () => DB::table('sessions')
                ->where('last_activity', '<', $cutoff->timestamp)
                ->orderBy('last_activity')
                ->limit(self::BATCH_SIZE)
                ->pluck('id'),
            fn ($ids) => DB::table('sessions')->whereIn('id', $ids)->delete()
        );

        // Password-reset tokens are transient credentials; never retain old ones.
        $resetTokensDeleted = $this->deleteInBatches(
            fn () => DB::table('password_reset_tokens')
                ->where('created_at', '<', $cutoff)
                ->orderBy('created_at')
                ->limit(self::BATCH_SIZE)
                ->pluck('email'),
            fn ($ids) => DB::table('password_reset_tokens')->whereIn('email', $ids)->delete()
        );

        // Device registrations are metadata, not user accounts. Preserve devices
        // seen within the retention window and remove inactive registrations.
        $devicesDeleted = $this->deleteInBatches(
            fn () => Device::query()
                ->where(function ($query) use ($cutoff) {
                    $query->where('last_seen_at', '<', $cutoff)
                        ->orWhere(function ($query) use ($cutoff) {
                            $query->whereNull('last_seen_at')
                                ->where('created_at', '<', $cutoff);
                        });
                })
                ->orderBy('id')
                ->limit(self::BATCH_SIZE)
                ->pluck('id'),
            fn ($ids) => Device::query()->whereIn('id', $ids)->delete()
        );

        // Remove expired tokens immediately, plus unused tokens older than 32 days.
        $tokensDeleted = $this->deleteInBatches(
            fn () => DB::table('personal_access_tokens')
                ->where(function ($query) use ($cutoff, $now) {
                    $query->where('expires_at', '<=', $now)
                        ->orWhere('last_used_at', '<', $cutoff)
                        ->orWhere(function ($query) use ($cutoff) {
                            $query->whereNull('last_used_at')
                                ->where('created_at', '<', $cutoff);
                        });
                })
                ->orderBy('id')
                ->limit(self::BATCH_SIZE)
                ->pluck('id'),
            fn ($ids) => DB::table('personal_access_tokens')->whereIn('id', $ids)->delete()
        );

        // The project config uses the database cache store; remove expired cache
        // values and locks, not live entries or queue jobs.
        $cacheDeleted = $this->deleteInBatches(
            fn () => DB::table('cache')
                ->where('expiration', '<=', $now->timestamp)
                ->orderBy('expiration')
                ->limit(self::BATCH_SIZE)
                ->pluck('key'),
            fn ($ids) => DB::table('cache')->whereIn('key', $ids)->delete()
        );

        $cacheLocksDeleted = $this->deleteInBatches(
            fn () => DB::table('cache_locks')
                ->where('expiration', '<=', $now->timestamp)
                ->orderBy('expiration')
                ->limit(self::BATCH_SIZE)
                ->pluck('key'),
            fn ($ids) => DB::table('cache_locks')->whereIn('key', $ids)->delete()
        );

        $this->info("Deleted {$locationsDeleted} employee location history records older than 32 days.");
        $this->info("Deleted {$sessionsDeleted} expired session records.");
        $this->info("Deleted {$resetTokensDeleted} old password-reset tokens.");
        $this->info("Deleted {$devicesDeleted} inactive device registrations older than 32 days.");
        $this->info("Deleted {$tokensDeleted} expired or stale personal access tokens.");
        $this->info("Deleted {$cacheDeleted} expired cache entries.");
        $this->info("Deleted {$cacheLocksDeleted} expired cache locks.");

        return self::SUCCESS;
    }

    /**
     * Delete transient records in bounded batches to reduce lock duration.
     */
    private function deleteInBatches(callable $selectIds, callable $deleteIds): int
    {
        $deleted = 0;

        do {
            $ids = $selectIds();

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += (int) $deleteIds($ids);
        } while ($ids->count() === self::BATCH_SIZE);

        return $deleted;
    }
}
