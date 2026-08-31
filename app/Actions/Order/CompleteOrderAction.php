<?php

    namespace App\Actions\Order;

    use App\Enums\InventoryMovementType;
    use App\Enums\OrderStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use App\Models\InventoryMovement;
    use App\Models\Order;
    use Illuminate\Support\Facades\DB;

    class CompleteOrderAction {
        public function execute(Order $order): Order {
            return DB::transaction(function () use ($order) {
                $order = Order::query()->lockForUpdate()->with([
                    'items.allocations',
                ])->findOrFail($order->id);

                if ($order->status !== OrderStatus::CONFIRMED) {
                    throw new BusinessRuleException('فقط سفارش تأییدشده قابل تکمیل است.');
                }

                if ($order->items->isEmpty()) {
                    throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');
                }

                $movedAt = now();

                foreach ($order->items as $item) {
                    if ($item->allocations->isEmpty()) {
                        throw new BusinessRuleException('برای تمام اقلام سفارش باید تخصیص انبار ثبت شده باشد.');
                    }

                    $allocatedQuantity = $item->allocations->sum(fn($allocation) => (int)$allocation->quantity);

                    if ($allocatedQuantity !== (int)$item->quantity) {
                        throw new BusinessRuleException('مجموع تخصیص انبار با مقدار سفارش برابر نیست.');
                    }

                    foreach ($item->allocations as $allocation) {
                        $quantity = (int)$allocation->quantity;

                        if ($quantity <= 0) {
                            throw new BusinessRuleException('مقدار تخصیص انبار باید بیشتر از صفر باشد.');
                        }

                        $batch = InventoryBatch::query()->lockForUpdate()->findOrFail($allocation->inventory_batch_id);

                        if ((int)$batch->product_id !== (int)$item->product_id) {
                            throw new BusinessRuleException('Batch انتخاب‌شده متعلق به محصول این آیتم سفارش نیست.');
                        }

                        if ((int)$batch->reserved_quantity < $quantity) {
                            throw new BusinessRuleException('موجودی رزروشده برای تکمیل سفارش کافی نیست.');
                        }

                        if ((int)$batch->quantity < $quantity) {
                            throw new BusinessRuleException('موجودی واقعی برای تکمیل سفارش کافی نیست.');
                        }

                        $batch->quantity -= $quantity;
                        $batch->reserved_quantity -= $quantity;

                        if ($batch->reserved_quantity < 0) {
                            throw new BusinessRuleException('موجودی رزروشده نمی‌تواند منفی شود.');
                        }

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
