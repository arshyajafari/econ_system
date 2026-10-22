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
        $deleted = EmployeeLocation::query()
            ->where('captured_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} employee location history records older than 30 days.");

        return self::SUCCESS;
    }
}
