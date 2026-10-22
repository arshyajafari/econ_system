<?php

namespace App\Console\Commands;

use App\Models\EmployeeLocation;
use Illuminate\Console\Command;

class PruneEmployeeLocationHistory extends Command
{
    protected $signature = 'employee-locations:prune-history';

    protected $description = 'Delete employee location history older than 30 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);
        $deleted = 0;
        do {
            $ids = EmployeeLocation::query()
                ->where('captured_at', '<', $cutoff)
                ->orderBy('captured_at')
                ->limit(1000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += EmployeeLocation::query()->whereIn('id', $ids)->delete();
        } while ($ids->count() === 1000);

        $this->info("Deleted {$deleted} employee location history records older than 30 days.");

        return self::SUCCESS;
    }
}
