<?php

    namespace App\Policies;

    use App\Models\Delivery;
    use App\Models\User;

    class DeliveryPolicy {
        public function viewAny(User $user): bool {
            return $user->can('deliveries.view');
        }

        public function view(User $user, Delivery $delivery): bool {
            return $user->can('deliveries.view');
        }

        public function create(User $user): bool {
            return $user->can('deliveries.create');
        }

        public function update(User $user, Delivery $delivery): bool {
            return $user->can('deliveries.update');
        }

        public function prepare(User $user, Delivery $delivery): bool {
            return $user->can('deliveries.prepare');
        }

        public function ship(User $user, Delivery $delivery): bool {
            return $user->can('deliveries.ship');
        }

        public function complete(User $user, Delivery $delivery): bool {
            return $user->can('deliveries.complete');
        }

        public function cancel(User $user, Delivery $delivery): bool {
            return $user->can('deliveries.cancel');
        }

        public function export(User $user): bool {
            return $user->can('deliveries.export');
        }
    }
