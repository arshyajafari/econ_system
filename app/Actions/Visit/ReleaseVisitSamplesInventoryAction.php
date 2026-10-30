<?php

namespace App\Actions\Visit;

use App\Exceptions\BusinessRuleException;
use App\Models\ScientificVisitorInventory;
use App\Models\Visit;

class ReleaseVisitSamplesInventoryAction
{
    /**
     * Release sample quantities back to the visit owner's usable inventory.
     *
     * The visit must be locked by the caller and this method must run inside
     * the caller's database transaction. Main warehouse stock is not changed:
     * the goods were already transferred to the scientific visitor.
     */
    public function execute(Visit $visit): void
    {
        $visit->loadMissing('employee.user');

        $owner = $visit->employee;
        if (!$owner?->user?->hasRole('scientific visitor')) {
            return;
        }

        // Aggregate by product so the adjustment is calculated once against
        // the full visit sample quantity for each product.
        $samples = $visit->samples()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->get();

        foreach ($samples as $sample) {
            $inventory = ScientificVisitorInventory::query()
                ->where('employee_id', $owner->id)
                ->where('product_id', $sample->product_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new BusinessRuleException(
                    'موجودی ویزیتور برای یکی از محصولات این بازدید پیدا نشد؛ لغو یا حذف انجام نشد.'
                );
            }

            app(\App\Actions\ScientificVisitorInventory\AdjustScientificVisitorInventoryUsageAction::class)
                ->execute($inventory, -(int) $sample->quantity);
        }
    }
}
