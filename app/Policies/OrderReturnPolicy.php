<?php

    namespace App\Policies;

    use App\Models\OrderReturn;
    use App\Models\User;

    class OrderReturnPolicy {
        public function viewAny(User $user): bool {
            return $user->can('order_returns.view');
        }

        public function view(User $user, OrderReturn $orderReturn): bool {
            return $user->can('order_returns.view');
        }

        public function create(User $user): bool {
            return $user->can('order_returns.create');
        }

        public function update(User $user, OrderReturn $orderReturn): bool {
            return $user->can('order_returns.update');
        }

        public function submit(User $user, OrderReturn $orderReturn): bool {
            return $user->can('order_returns.submit');
        }

        public function confirm(User $user, OrderReturn $orderReturn): bool {
            return $user->can('order_returns.confirm');
        }

        public function complete(User $user, OrderReturn $orderReturn): bool {
            return $user->can('order_returns.complete');
        }

        public function cancel(User $user, OrderReturn $orderReturn): bool {
            return $user->can('order_returns.cancel');
        }

        public function allocate(User $user, OrderReturn $orderReturn): bool {
            return $user->can('order_returns.allocate');
        }

        public function export(User $user): bool {
            return $user->can('order_returns.export');
        }
    }
