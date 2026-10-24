<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Exceptions\BusinessRuleException;
use App\Models\ScientificVisitorInventory;
use Illuminate\Support\Facades\DB;

class DeleteScientificVisitorInventoryAction
{
    public function execute(ScientificVisitorInventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            $inventory = ScientificVisitorInventory::query()
                ->lockForUpdate()
                ->findOrFail($inventory->id);

            // This aggregate ledger has no source-batch allocation history. Once
            // quantities have been received or used, deleting it would hide
            // operational history and make reconciliation impossible.
            if ((int) $inventory->received_quantity !== 0 || (int) $inventory->used_quantity !== 0) {
                throw new BusinessRuleException(
                    'حذف موجودی ویزیتور دارای سابقه تحویل یا مصرف مجاز نیست؛ برای حفظ سابقه انبار، ابتدا باید مغایرت از مسیر اصلاح و تطبیق موجودی رسیدگی شود.'
                );
            }

            $inventory->delete();
        });
    }
}
