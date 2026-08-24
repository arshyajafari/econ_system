<?php

    namespace App\Policies;

    use App\Models\InventoryAdjustment;
    use App\Models\User;

    class InventoryAdjustmentPolicy {
        public function viewAny(User $user): bool {
            return $user->can('inventory_adjustments.view');
        }

        public function view(User $user, InventoryAdjustment $adjustment): bool {
            return $user->can('inventory_adjustments.view');
        }

        public function create(User $user): bool {
            return $user->can('inventory_adjustments.create');
        }
    }
