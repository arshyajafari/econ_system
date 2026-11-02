<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\EmployeeLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneEmployeeLocationHistory extends Command
{
    protected $signature = 'employee-locations:prune-history';

    protected $description = 'Prune transient device, expired access-token, and employee location records older than 32 days';

    private const BATCH_SIZE = 1000;

    public function handle(): int
    {
        $cutoff = now()->subDays(32);

        $locationsDeleted = $this->deleteInBatches(
            fn () => EmployeeLocation::query()
                ->where('captured_at', '<', $cutoff)
                ->orderBy('captured_at')
                ->limit(self::BATCH_SIZE)
                ->pluck('id'),
            fn ($ids) => EmployeeLocation::query()->whereIn('id', $ids)->delete()
        );

        // Device registrations are transient metadata, not user accounts. Keep
        // recently seen devices; remove registrations inactive for 32 days.
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

        // Expired Sanctum tokens are always removed. Non-expired tokens that
        // have not been used (or created, if never used) for 32 days are also
        // pruned to limit stale credentials in the database.
        $tokensDeleted = $this->deleteInBatches(
            fn () => DB::table('personal_access_tokens')
                ->where(function ($query) use ($cutoff) {
                    $query->where('expires_at', '<=', now())
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

        $this->info("Deleted {$locationsDeleted} employee location history records older than 32 days.");
        $this->info("Deleted {$devicesDeleted} inactive device registrations older than 32 days.");
        $this->info("Deleted {$tokensDeleted} expired or stale personal access tokens.");

        return self::SUCCESS;
    }

    /**
     * Delete old transient records in bounded batches to avoid one large
     * delete operation and keep database lock duration under control.
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
