<?php

    namespace App\Actions\Order;

    use App\Enums\ProductStatus;
    use App\Exceptions\BusinessRuleException;
    use App\Models\Order;
    use App\Models\Product;

    class SyncOrderItemsAction {
        public function execute(Order $order, array $items): void {
            if (empty($items)) {
                throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');
            }

            $order->items()->delete();

            foreach ($items as $itemData) {
                $product = Product::query()->where('public_id', $itemData['product_id'])->firstOrFail();

                if ($product->status !== ProductStatus::ACTIVE) {
                    throw new BusinessRuleException('محصول انتخاب‌شده غیرفعال است.');
                }

                $quantity = (int)$itemData['quantity'];
                $unitPrice = (float)$itemData['unit_price'];

                if ($quantity <= 0) {
                    throw new BusinessRuleException('تعداد محصول باید بیشتر از صفر باشد.');
                }

                if ($unitPrice < 0) {
                    throw new BusinessRuleException('قیمت واحد نمی‌تواند منفی باشد.');
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                    'description' => $itemData['description'] ?? null,
                ]);
            }
        }
    }
