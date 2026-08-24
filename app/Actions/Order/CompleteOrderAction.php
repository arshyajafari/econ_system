<?php

    namespace App\Actions\Order;

    use App\Enums\InventoryMovementType;
    use App\Enums\OrderStatus;
    use App\Models\InventoryMovement;
    use App\Models\Order;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class CompleteOrderAction {
        public function execute(Order $order): Order {
            return DB::transaction(function () use ($order) {
                $order = Order::query()->lockForUpdate()->with([
                        'items.allocations.inventoryBatch',
                    ])->findOrFail($order->id);

                if ($order->status !== OrderStatus::CONFIRMED) {
                    throw new RuntimeException('فقط سفارش تأییدشده قابل تکمیل است.');
                }

                $movedAt = now();

                foreach ($order->items as $item) {
                    $allocatedQuantity = $item->allocations->sum('quantity');

                    if ($allocatedQuantity !== (int)$item->quantity) {
                        throw new RuntimeException('مجموع تخصیص انبار با مقدار سفارش برابر نیست.');
                    }

                    foreach ($item->allocations as $allocation) {
                        $batch = $allocation->inventoryBatch;

                        $quantity = (int)$allocation->quantity;

                        if ($batch->reserved_quantity < $quantity) {
                            throw new RuntimeException('موجودی رزروشده برای تکمیل سفارش کافی نیست.');
                        }

                        if ($batch->quantity < $quantity) {
                            throw new RuntimeException('موجودی واقعی برای تکمیل سفارش کافی نیست.');
                        }

                        $batch->quantity -= $quantity;
                        $batch->reserved_quantity -= $quantity;

                        $batch->save();

                        InventoryMovement::create([
                            'inventory_batch_id' => $batch->id,
                            'type' => InventoryMovementType::OUT,
                            'quantity' => $quantity,
                            'reason' => 'order_completed',
                            'moved_at' => $movedAt,
                        ]);
                    }
                }

                $order->status = OrderStatus::COMPLETED;
                $order->save();

                return $order->fresh(Order::DEFAULT_RELATIONS);
            });
        }
    }
