<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class DeleteInventoryBatchAction {
        public function execute(InventoryBatch $batch): void {
            DB::transaction(function () use ($batch) {
                $hasMovements = $batch->movements()->exists();
                $hasAdjustments = $batch->adjustments()->exists();
                $hasAllocations = $batch->allocations()->exists();
                $hasReturnAllocations = $batch->returnAllocations()->exists();

                if ($hasMovements || $hasAdjustments || $hasAllocations || $hasReturnAllocations) {
                    throw new BusinessRuleException('بچ موجودی دارای سابقه عملیاتی است و امکان حذف آن وجود ندارد.');
                }

                $batch->delete();
            });
        }
    }
