<?php

    namespace App\Actions\Inventory;

    use App\Models\OrderItemAllocation;

    class ReleaseInventoryReservationAction {
        public function execute(OrderItemAllocation $allocation): void {
            $batch = $allocation->inventoryBatch()->lockForUpdate()->firstOrFail();

            $quantity = (int)$allocation->quantity;

            if ($quantity <= 0) {
                throw new RuntimeException('مقدار تخصیص رزرو باید بیشتر از صفر باشد.');
            }

            if ($batch->reserved_quantity < $quantity) {
                throw new RuntimeException('موجودی رزروشده برای آزادسازی کافی نیست.');
            }

            $batch->reserved_quantity -= $quantity;
            $batch->save();

            $allocation->delete();
        }
    }
