<?php

    namespace App\Actions\Inventory;

    use App\Exceptions\BusinessRuleException;
    use App\Models\InventoryBatch;
    use App\Models\OrderItem;
    use Illuminate\Support\Collection;

    class ReserveInventoryAction {
        public function execute(OrderItem $orderItem): Collection {
            $requiredQuantity = $orderItem->quantity;

            if ($requiredQuantity <= 0) {
                throw new BusinessRuleException('مقدار سفارش باید بیشتر از صفر باشد.');
            }

            $batches = InventoryBatch::query()->where('product_id', $orderItem->product_id)->where('quantity', '>', 0)
                ->whereColumn('reserved_quantity', '<', 'quantity')->where(function ($query) {
                    $query->whereNull('expire_date')->orWhereDate('expire_date', '>=', today());
                })->orderByRaw('expire_date IS NULL ASC')->orderBy('expire_date')->lockForUpdate()->get();

            $availableQuantity = $batches->sum(fn(InventoryBatch $batch): int => $batch->available_quantity);

            if ($availableQuantity < $requiredQuantity) {
                throw new BusinessRuleException('موجودی قابل رزرو برای محصول کافی نیست.');
            }

            $remaining = $requiredQuantity;

            $allocations = collect();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $available = $batch->available_quantity;

                if ($available <= 0) {
                    continue;
                }

                $reserveQuantity = min($batch->available_quantity, $remaining);

                $batch->reserved_quantity += $reserveQuantity;

                $batch->save();

                $allocations->push([
                    'inventory_batch_id' => $batch->id,
                    'quantity' => $reserveQuantity,
                ]);

                $remaining -= $reserveQuantity;
            }

            if ($remaining > 0) {
                throw new BusinessRuleException('رزرو کامل موجودی سفارش انجام نشد.');
            }

            return $allocations;
        }
    }
