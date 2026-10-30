<?php

namespace App\Console\Commands;

use App\Actions\ScientificVisitorInventory\ReconcileScientificVisitorInventoryUsageAction;
use Illuminate\Console\Command;

class ReconcileScientificVisitorInventoryUsage extends Command
{
    protected $signature = 'scientific-visitor-inventory:reconcile-usage {--dry-run : Preview changes without updating inventory}';

    protected $description = 'Reconcile scientific visitor inventory consumption against active visit samples';

    public function handle(ReconcileScientificVisitorInventoryUsageAction $action): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $summary = $action->execute($dryRun);

        $this->info($dryRun ? 'پیش‌نمایش تطبیق موجودی انجام شد.' : 'تطبیق موجودی ویزیتورها انجام شد.');
        $this->table(
            ['بررسی‌شده', 'نیازمند اصلاح', 'بدون تغییر', 'نیازمند بررسی دستی'],
            [[
                $summary['checked'],
                $summary['updated'],
                $summary['unchanged'],
                count($summary['skipped']),
            ]]
        );

        foreach ($summary['skipped'] as $item) {
            $this->warn("{$item['inventory_id']}: {$item['reason']}");
        }

        return self::SUCCESS;
    }
}
