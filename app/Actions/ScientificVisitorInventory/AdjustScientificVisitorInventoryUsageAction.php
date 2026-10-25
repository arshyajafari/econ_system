<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Exceptions\BusinessRuleException;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class AdjustScientificVisitorInventoryUsageAction
{
    /**
     * Adjust consumption in the visitor ledger and, when source-batch history
     * is complete, mirror the same delta into its transfer allocations.
     * Positive delta consumes visitor stock; negative delta releases it.
     *
     * The caller must hold a database transaction and lock the inventory row.
     */
    public function execute(ScientificVisitorInventory $inventory, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $currentUsed = (int) $inventory->used_quantity;
        $newUsed = $currentUsed + $delta;

        if ($newUsed < 0 || $newUsed > (int) $inventory->received_quantity) {
            throw new BusinessRuleException('تغییر مصرف با موجودی ثبت‌شده ویزیتور سازگار نیست.');
        }

        $transfers = DB::table('scientific_visitor_inventory_transfers')
            ->where('scientific_visitor_inventory_id', $inventory->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $activeTransferred = (int) $transfers->sum(
            fn ($transfer) => (int) $transfer->quantity - (int) $transfer->returned_quantity
        );
        $allocatedUsed = (int) $transfers->sum('used_quantity');
        $isFullyTracked = $activeTransferred === (int) $inventory->received_quantity
            && $allocatedUsed === $currentUsed;

        // Old ledgers predate source-batch tracking. Preserve aggregate accounting
        // for them, but do not invent a batch allocation that cannot be proven.
        if ($isFullyTracked && $transfers->isNotEmpty()) {
            $remaining = abs($delta);
            $ordered = $delta > 0 ? $transfers : $transfers->sortByDesc('id');

            foreach ($ordered as $transfer) {
                $activeQuantity = (int) $transfer->quantity - (int) $transfer->returned_quantity;
                $used = (int) $transfer->used_quantity;
                $capacity = $delta > 0 ? $activeQuantity - $used : $used;
                if ($capacity <= 0) {
                    continue;
                }

                $adjustment = min($remaining, $capacity);
                DB::table('scientific_visitor_inventory_transfers')
                    ->where('id', $transfer->id)
                    ->update([
                        'used_quantity' => $delta > 0 ? $used + $adjustment : $used - $adjustment,
                        'updated_at' => now(),
                    ]);

                $remaining -= $adjustment;
                if ($remaining === 0) {
                    break;
                }
            }

            if ($remaining !== 0) {
                throw new BusinessRuleException('ردیابی بچ‌های تحویلی با مصرف ثبت‌شده سازگار نیست؛ عملیات انجام نشد.');
            }
        }

        $inventory->used_quantity = $newUsed;
        $inventory->save();
    }
}
