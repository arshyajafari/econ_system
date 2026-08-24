<?php

    namespace App\Policies;

    use App\Models\OrderItem;
    use App\Models\User;

    class OrderItemPolicy {
        public function view(User $user, OrderItem $orderItem): bool {
            return $user->can('orders.view');
        }

        public function allocate(User $user, OrderItem $orderItem): bool {
            return $user->can('orders.allocate');
        }
    }
