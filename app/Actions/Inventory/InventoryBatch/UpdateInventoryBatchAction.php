<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class UpdateInventoryBatchAction {
        public function execute(InventoryBatch $batch, array $data): InventoryBatch {
            return DB::transaction(function () use ($batch, $data) {
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($batch->id);

                $hasOperationalHistory = $batch->movements()->exists() || $batch->adjustments()
                        ->exists() || $batch->allocations()->exists() || $batch->returnAllocations()->exists();

                if ($hasOperationalHistory) {
                    if (array_key_exists('product_id', $data) || array_key_exists('received_at', $data)) {
                        throw new BusinessRuleException('محصول و تاریخ دریافت بچ دارای سابقه عملیاتی قابل تغییر نیست.');
                    }
                }

                unset($data['product_id'], $data['quantity'], $data['reserved_quantity'], $data['received_at'],);

                $batch->fill($data);
                $batch->save();

                return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
            });
        }
    }
