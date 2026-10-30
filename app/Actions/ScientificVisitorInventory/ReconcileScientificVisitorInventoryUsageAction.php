<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class ReconcileScientificVisitorInventoryUsageAction
{
    /**
     * Rebuild usage from non-deleted samples belonging to non-cancelled visits.
     * Delivery rows are allocated oldest-first to keep their displayed balances
     * consistent with the aggregate visitor inventory.
     */
    public function execute(bool $dryRun = false): array
    {
        $summary = [
            'checked' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => [],
        ];

        ScientificVisitorInventory::query()
            ->orderBy('id')
            ->chunkById(100, function ($inventories) use (&$summary, $dryRun) {
                foreach ($inventories as $candidate) {
                    $summary['checked']++;

                    $result = DB::transaction(function () use ($candidate, $dryRun) {
                        $inventory = ScientificVisitorInventory::query()
                            ->lockForUpdate()
                            ->find($candidate->id);

                        if (!$inventory) {
                            return ['status' => 'skipped', 'reason' => 'موجودی در حین تطبیق حذف شده است.'];
                        }

                        $expectedUsed = (int) DB::table('samples as s')
                            ->join('visits as v', 'v.id', '=', 's.visit_id')
                            ->where('v.employee_id', $inventory->employee_id)
                            ->where('s.product_id', $inventory->product_id)
                            ->whereNull('s.deleted_at')
                            ->whereNull('v.deleted_at')
                            ->where(function ($query) {
                                // A legacy cancelled visit with no release timestamp
                                // still holds its usage; otherwise a later delete could release it twice.
                                $query->where('v.status', '!=', 'cancelled')
                                    ->orWhereNull('v.inventory_released_at');
                            })
                            ->sum('s.quantity');

                        $received = (int) $inventory->received_quantity;
                        if ($expectedUsed > $received) {
                            return [
                                'status' => 'skipped',
                                'reason' => "مصرف معتبر ({$expectedUsed}) از کل تحویل ({$received}) بیشتر است.",
                            ];
                        }

                        $transfers = DB::table('scientific_visitor_inventory_transfers')
                            ->where('scientific_visitor_inventory_id', $inventory->id)
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->get();

                        if ($transfers->isNotEmpty()) {
                            $activeTransferred = (int) $transfers->sum(
                                fn ($transfer) => max(0, (int) $transfer->quantity - (int) $transfer->returned_quantity)
                            );

                            if ($activeTransferred !== $received) {
                                return [
                                    'status' => 'skipped',
                                    'reason' => "جمع تحویل‌های فعال ({$activeTransferred}) با موجودی ویزیتور ({$received}) برابر نیست.",
                                ];
                            }
                        }

                        $changed = (int) $inventory->used_quantity !== $expectedUsed;
                        $remainingUsage = $expectedUsed;
                        $allocations = [];

                        foreach ($transfers as $transfer) {
                            $activeQuantity = max(0, (int) $transfer->quantity - (int) $transfer->returned_quantity);
                            $allocated = min($remainingUsage, $activeQuantity);
                            $allocationChanged = (int) $transfer->used_quantity !== $allocated;
                            $allocations[] = ['id' => $transfer->id, 'used_quantity' => $allocated, 'changed' => $allocationChanged];
                            $changed = $changed || $allocationChanged;
                            $remainingUsage -= $allocated;
                        }

                        if ($remainingUsage !== 0) {
                            return [
                                'status' => 'skipped',
                                'reason' => 'مصرف نمونه‌ها در بچ‌های تحویلی قابل تخصیص نیست.',
                            ];
                        }

                        if (!$dryRun && $changed) {
                            foreach ($allocations as $allocation) {
                                if (!$allocation['changed']) {
                                    continue;
                                }

                                DB::table('scientific_visitor_inventory_transfers')
                                    ->where('id', $allocation['id'])
                                    ->update([
                                        'used_quantity' => $allocation['used_quantity'],
                                        'updated_at' => now(),
                                    ]);
                            }

                            $inventory->used_quantity = $expectedUsed;
                            $inventory->save();
                        }

                        return ['status' => $changed ? 'updated' : 'unchanged'];
                    });

                    if ($result['status'] === 'skipped') {
                        $summary['skipped'][] = [
                            'inventory_id' => $candidate->public_id,
                            'reason' => $result['reason'],
                        ];
                    } elseif ($result['status'] === 'updated') {
                        $summary['updated']++;
                    } else {
                        $summary['unchanged']++;
                    }
                }
            });

        return $summary;
    }
}
