<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class DeleteInventoryBatchAction {
        public function execute(InventoryBatch $batch): void {
            DB::transaction(function () use ($batch) {
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($batch->id);

                $hasOperationalHistory = $batch->movements()->exists() || $batch->adjustments()
                        ->exists() || $batch->allocations()->exists() || $batch->returnAllocations()->exists();

                if ($hasOperationalHistory) {
                    throw new BusinessRuleException('بچ موجودی دارای سابقه عملیاتی است و امکان حذف آن وجود ندارد.');
                }

                $batch->delete();
            });
        }
    }
