<?php

    namespace App\Actions\Order;

    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Order;
    use Illuminate\Support\Facades\DB;

    class SubmitOrderAction {
        public function execute(Order $order): Order {
            return DB::transaction(function () use ($order) {
                $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

                if ($order->status !== OrderStatus::DRAFT) {
                    throw new BusinessRuleException('فقط سفارش در وضعیت draft قابل ارسال است.');
                }

                if ($order->items->isEmpty()) {
                    throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');
                }

                $order->status = OrderStatus::PENDING;
                $order->ordered_at = now();

                $order->save();

                return $order->fresh(Order::DEFAULT_RELATIONS);
            });
        }
    }
