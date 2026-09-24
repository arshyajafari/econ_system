<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class UpdateInventoryBatchAction {
        public function execute(InventoryBatch $batch, array $data): InventoryBatch {
            return DB::transaction(function () use ($batch, $data) {
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($batch->id);

                if (array_key_exists('quantity', $data) && (int) $data['quantity'] < (int) $batch->reserved_quantity) {
                    throw new \InvalidArgumentException('تعداد موجودی نمی‌تواند کمتر از تعداد رزروشده باشد.');
                }

                $batch->fill([
                    'batch_number' => array_key_exists('batch_number',
                        $data) ? $data['batch_number'] : $batch->batch_number,

                    'expire_date' => array_key_exists('expire_date',
                        $data) ? $data['expire_date'] : $batch->expire_date,

                    'quantity' => array_key_exists('quantity',
                        $data) ? (int) $data['quantity'] : $batch->quantity,

                    'description' => array_key_exists('description',
                        $data) ? $data['description'] : $batch->description,
                ]);

                $batch->save();

                return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
            });
        }
    }
