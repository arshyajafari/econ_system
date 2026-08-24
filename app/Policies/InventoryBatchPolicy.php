<?php

    namespace App\Policies;

    use App\Models\InventoryBatch;
    use App\Models\User;

    class InventoryBatchPolicy {
        public function viewAny(User $user): bool {
            return $user->can('inventory_batches.view');
        }

        public function view(User $user, InventoryBatch $batch): bool {
            return $user->can('inventory_batches.view');
        }

        public function create(User $user): bool {
            return $user->can('inventory_batches.create');
        }

        public function update(User $user, InventoryBatch $batch): bool {
            return $user->can('inventory_batches.update');
        }

        public function delete(User $user, InventoryBatch $batch): bool {
            return $user->can('inventory_batches.delete');
        }
    }
