<?php

    namespace App\Actions\Order\OrderItem;

    use App\Models\InventoryBatch;
    use App\Models\OrderItem;
    use App\Models\OrderItemAllocation;
    use Illuminate\Support\Facades\DB;
    use RuntimeException;

    class AllocateOrderItemAction {
        public function execute(OrderItem $orderItem, array $allocations): OrderItem {
            return DB::transaction(function () use ($orderItem, $allocations) {
                $orderItem = OrderItem::query()->lockForUpdate()->findOrFail($orderItem->id);

                $existingAllocatedQuantity = OrderItemAllocation::query()->where('order_item_id', $orderItem->id)
                    ->sum('quantity');

                if ($existingAllocatedQuantity > 0) {
                    throw new RuntimeException('برای این قلم سفارش قبلاً تخصیص انبار ثبت شده است.');
                }

                $allocatedQuantity = 0;

                foreach ($allocations as $allocation) {
                    $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($allocation['inventory_batch_id']);

                    if ($batch->product_id !== $orderItem->product_id) {
                        throw new RuntimeException('بچ انتخاب‌شده متعلق به محصول این قلم سفارش نیست.');
                    }

                    $quantity = (int)$allocation['quantity'];

                    $availableQuantity = max(0, $batch->quantity - $batch->reserved_quantity);

                    if ($quantity > $availableQuantity) {
                        throw new RuntimeException('موجودی قابل دسترس برای این تخصیص کافی نیست.');
                    }

                    $allocatedQuantity += $quantity;

                    OrderItemAllocation::create([
                        'order_item_id' => $orderItem->id,
                        'inventory_batch_id' => $batch->id,
                        'quantity' => $quantity,
                    ]);
                }

                if ($allocatedQuantity !== (int)$orderItem->quantity) {
                    throw new RuntimeException('مجموع تخصیص‌ها باید برابر با مقدار قلم سفارش باشد.');
                }

                return $orderItem->fresh([
                    'product',
                    'allocations.inventoryBatch.product',
                ]);
            });
        }
    }
