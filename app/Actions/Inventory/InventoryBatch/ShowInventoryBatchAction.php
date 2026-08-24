<?php

    namespace App\Actions\Inventory\InventoryBatch;

    use App\Models\InventoryBatch;

    class ShowInventoryBatchAction {
        public function execute(InventoryBatch $batch): InventoryBatch {
            return $batch->fresh(InventoryBatch::DEFAULT_RELATIONS);
        }
    }
