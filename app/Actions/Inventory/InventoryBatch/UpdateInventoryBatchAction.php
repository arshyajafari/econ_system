<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class UpdateInventoryBatchAction {
        public function execute(InventoryBatch $batch, array $data): InventoryBatch {
            return DB::transaction(function () use (
                $batch, $data
            ) {
                unset($data['quantity'], $data['reserved_quantity']);
                $batch->fill($data);
                $batch->save();

                return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
            });
        }
    }
