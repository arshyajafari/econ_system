<?php

    namespace App\Actions\Order;

    use App\Models\Order;

    class ShowOrderAction {
        public function execute(Order $order): Order {
            return $order->fresh(Order::DEFAULT_RELATIONS);
        }
    }
