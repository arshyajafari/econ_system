<?php

    namespace App\Actions\Order;

    use App\Actions\Inventory\ReserveInventoryAction;
    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Order;
    use App\Models\OrderItemAllocation;
    use Illuminate\Support\Facades\DB;

    class ConfirmOrderAction {
        public function __construct(protected ReserveInventoryAction $reserveInventory) {
        }

        public function execute(Order $order): Order {
            return DB::transaction(function () use ($order) {
                $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

                if ($order->status !== OrderStatus::PENDING) {
                    throw new BusinessRuleException('فقط سفارش در وضعیت pending قابل تأیید است.');
                }

                if ($order->items->isEmpty()) {
                    throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');
                }

                foreach ($order->items as $item) {
                    $allocations = $this->reserveInventory->execute($item);

                    foreach ($allocations as $allocation) {
                        OrderItemAllocation::create([
                            'order_item_id' => $item->id,
                            'inventory_batch_id' => $allocation['inventory_batch_id'],
                            'quantity' => $allocation['quantity'],
                        ]);
                    }
                }

                $order->status = OrderStatus::CONFIRMED;
                $order->save();

                return $order->fresh(Order::DEFAULT_RELATIONS);
            });
        }
    }
