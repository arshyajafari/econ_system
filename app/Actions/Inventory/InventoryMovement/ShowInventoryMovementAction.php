<?php

    namespace App\Actions\Inventory\InventoryMovement;

    use App\Models\InventoryMovement;

    class ShowInventoryMovementAction {
        public function execute(InventoryMovement $movement): InventoryMovement {
            return $movement->fresh(InventoryMovement::DEFAULT_RELATIONS);
        }
    }
