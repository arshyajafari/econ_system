<?php

namespace App\Actions\Inventory\InventoryBatch;

use App\Exceptions\BusinessRuleException;
use App\Models\InventoryBatch;
use Illuminate\Support\Facades\DB;

class DeleteInventoryBatchAction
{
    public function execute(InventoryBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $lockedBatch = InventoryBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if (!$lockedBatch->canBeDeleted()) {
                throw new BusinessRuleException(
                    'این بچ سابقه عملیاتی دارد یا موجودی آن رزرو شده است؛ برای حفظ سوابق امکان حذف آن وجود ندارد.'
                );
            }

            // Soft deletion keeps the initial receipt records and audit trail intact.
            $lockedBatch->delete();
        });
    }
}
