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

        $samples = $visit->samples()->orderBy('id')->get();

        foreach ($samples as $sample) {
            $inventory = ScientificVisitorInventory::query()
                ->where('employee_id', $owner->id)
                ->where('product_id', $sample->product_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory || (int) $inventory->used_quantity < (int) $sample->quantity) {
                throw new BusinessRuleException(
                    'موجودی ثبت‌شده ویزیتور با سابقه نمونه‌ها سازگار نیست؛ لغو یا حذف انجام نشد تا موجودی اشتباه نشود.'
                );
            }

            app(\\App\\Actions\\ScientificVisitorInventory\\AdjustScientificVisitorInventoryUsageAction::class)
                ->execute($inventory, -(int) $sample->quantity);
        }
    }
}
