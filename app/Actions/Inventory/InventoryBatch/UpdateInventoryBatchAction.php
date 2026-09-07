<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class UpdateInventoryBatchAction {
        public function execute(InventoryBatch $batch, array $data): InventoryBatch {
            return DB::transaction(function () use ($batch, $data) {
                $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($batch->id);

                $batch->fill([
                    'batch_number' => array_key_exists('batch_number',
                        $data) ? $data['batch_number'] : $batch->batch_number,

                    'expire_date' => array_key_exists('expire_date',
                        $data) ? $data['expire_date'] : $batch->expire_date,

                    'description' => array_key_exists('description',
                        $data) ? $data['description'] : $batch->description,
                ]);

                $batch->save();

                return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
            });
        }
    }
