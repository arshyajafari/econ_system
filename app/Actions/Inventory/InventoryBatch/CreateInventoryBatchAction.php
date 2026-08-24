<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Models\InventoryBatch;
    use Illuminate\Support\Facades\DB;

    class CreateInventoryBatchAction {
        public function execute(array $data): InventoryBatch {
            return DB::transaction(function () use ($data) {
                $batch = new InventoryBatch();
                $batch->fill($data);
                $batch->reserved_quantity ??= 0;
                $batch->save();

                return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
            });
        }
    }
