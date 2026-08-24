<?php

    namespace App\Policies;

    use App\Models\Order;
    use App\Models\User;

    class OrderPolicy {
        public function viewAny(User $user): bool {
            return $user->can('orders.view');
        }

        public function view(User $user, Order $order): bool {
            return $user->can('orders.view');
        }

        public function create(User $user): bool {
            return $user->can('orders.create');
        }

        public function update(User $user, Order $order): bool {
            return $user->can('orders.update');
        }

        public function submit(User $user, Order $order): bool {
            return $user->can('orders.submit');
        }

        public function confirm(User $user, Order $order): bool {
            return $user->can('orders.confirm');
        }

        public function complete(User $user, Order $order): bool {
            return $user->can('orders.complete');
        }

        public function cancel(User $user, Order $order): bool {
            return $user->can('orders.cancel');
        }

        public function export(User $user): bool {
            return $user->can('orders.export');
        }
    }
