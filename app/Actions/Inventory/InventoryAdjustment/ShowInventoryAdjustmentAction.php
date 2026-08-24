<?php

    namespace App\Actions\Inventory\InventoryAdjustment;

    use App\Models\InventoryAdjustment;

    class ShowInventoryAdjustmentAction {
        public function execute(InventoryAdjustment $adjustment): InventoryAdjustment {
            return $adjustment->fresh(InventoryAdjustment::DEFAULT_RELATIONS);
        }
    }
