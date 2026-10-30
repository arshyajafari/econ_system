<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Exceptions\BusinessRuleException;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class AdjustScientificVisitorInventoryUsageAction
{
    /**
     * Apply a sample-consumption delta to the aggregate visitor ledger and
     * deterministically rebuild its per-delivery allocation.
     *
     * The caller must own a transaction and lock the aggregate inventory row.
     * Consumption is allocated oldest-delivery-first. When usage is released,
     * the same deterministic allocation is recalculated from the new total.
     */
    public function execute(ScientificVisitorInventory $inventory, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        // The sample ledger is the source of truth. Never build the new balance
        // on a possibly stale aggregate used_quantity value.
        $sampleUsage = (int) DB::table('samples as s')
            ->join('visits as v', 'v.id', '=', 's.visit_id')
            ->where('v.employee_id', $inventory->employee_id)
            ->where('s.product_id', $inventory->product_id)
            ->whereNull('s.deleted_at')
            ->whereNull('v.deleted_at')
            ->where(function ($query) {
                $query->where('v.status', '!=', 'cancelled')
                    ->orWhereNull('v.inventory_released_at');
            })
            ->sum('s.quantity');

        $newUsed = $sampleUsage + $delta;
        $received = (int) $inventory->received_quantity;

        if ($newUsed < 0 || $newUsed > $received) {
            throw new BusinessRuleException('تغییر مصرف با موجودی ثبت‌شده ویزیتور سازگار نیست.');
        }

        $transfers = DB::table('scientific_visitor_inventory_transfers')
            ->where('scientific_visitor_inventory_id', $inventory->id)
            ->orderByRaw('COALESCE(delivered_at, DATE(created_at)) ASC')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($transfers->isNotEmpty()) {
            $activeTransferred = (int) $transfers->sum(
                fn ($transfer) => max(0, (int) $transfer->quantity - (int) $transfer->returned_quantity)
            );

            // Never silently update only the aggregate while the delivery ledger
            // would continue to show a different available quantity.
            if ($activeTransferred !== $received) {
                throw new BusinessRuleException(
                    'جمع تحویل‌های ثبت‌شده با موجودی ویزیتور برابر نیست؛ ابتدا سوابق تحویل باید تطبیق داده شوند. هیچ تغییری اعمال نشد.'
                );
            }

            $remainingUsage = $newUsed;

            foreach ($transfers as $transfer) {
                $activeQuantity = max(0, (int) $transfer->quantity - (int) $transfer->returned_quantity);
                $allocatedUsage = min($remainingUsage, $activeQuantity);

                if ((int) $transfer->used_quantity !== $allocatedUsage) {
                    DB::table('scientific_visitor_inventory_transfers')
                        ->where('id', $transfer->id)
                        ->update([
                            'used_quantity' => $allocatedUsage,
                            'updated_at' => now(),
                        ]);
                }

                $remainingUsage -= $allocatedUsage;
            }

            if ($remainingUsage !== 0) {
                throw new BusinessRuleException(
                    'مصرف نمونه‌ها از مجموع تحویل‌های فعال بیشتر است؛ عملیات برای جلوگیری از مغایرت لغو شد.'
                );
            }
        }

        $inventory->used_quantity = $newUsed;
        $inventory->save();
    }
}
