<?php

namespace App\\Actions\\ScientificVisitorInventory;

use App\\Exceptions\\BusinessRuleException;
use App\\Models\\ScientificVisitorInventory;
use Illuminate\\Support\\Facades\\DB;

class DeleteScientificVisitorInventoryAction
{
    public function execute(ScientificVisitorInventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            $inventory = ScientificVisitorInventory::query()
                ->lockForUpdate()
                ->findOrFail($inventory->id);

            $receivedQuantity = (int) $inventory->received_quantity;
            $usedQuantity = (int) $inventory->used_quantity;
            $availableQuantity = max(0, $receivedQuantity - $usedQuantity);

            // A row can only be removed if none of its delivered quantity has
            // ever been consumed. Soft deletion preserves the audit history
            // while removing it from normal inventory and sample selectors.
            if ($usedQuantity !== 0 || $availableQuantity !== $receivedQuantity) {
                throw new BusinessRuleException(
                    "حذف فقط زمانی مجاز است که هیچ مقداری مصرف نشده باشد. دریافتی: {$receivedQuantity}، مصرف‌شده: {$usedQuantity}، قابل استفاده: {$availableQuantity}."
                );
            }

            $inventory->delete();
        });
    }
}
