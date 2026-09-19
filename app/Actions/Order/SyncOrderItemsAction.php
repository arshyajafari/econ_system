<?php

namespace App\Actions\Order;

use App\Enums\ProductStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class SyncOrderItemsAction {
    public function execute(Order $order, array $items): void {
        if (empty($items)) throw new BusinessRuleException('سفارش باید حداقل یک آیتم داشته باشد.');

        $order->items()->delete();

        foreach ($items as $itemData) {
            $product = Product::query()->where('public_id', $itemData['product_id'])->firstOrFail();
            if ($product->status !== ProductStatus::ACTIVE) throw new BusinessRuleException('محصول انتخاب‌شده غیرفعال است.');

            $quantity = (int) $itemData['quantity'];
            $unitPrice = (float) $itemData['unit_price'];
            $discountType = $itemData['discount_type'] ?? OrderItem::DISCOUNT_TYPE_NONE;
            $discountValue = (float) ($itemData['discount_value'] ?? 0);
            $offerType = $itemData['offer_type'] ?? OrderItem::OFFER_TYPE_NONE;
            $offerBuy = (int) ($itemData['offer_buy_quantity'] ?? 0);
            $offerFreePerCycle = (int) ($itemData['offer_free_quantity'] ?? 0);

            if ($quantity <= 0) throw new BusinessRuleException('تعداد خرید باید بیشتر از صفر باشد.');
            if ($unitPrice < 0) throw new BusinessRuleException('قیمت واحد نمی‌تواند منفی باشد.');
            if (!in_array($discountType, OrderItem::DISCOUNT_TYPES, true)) throw new BusinessRuleException('نوع تخفیف آیتم نامعتبر است.');
            if ($discountType === OrderItem::DISCOUNT_TYPE_PERCENTAGE && ($discountValue < 0 || $discountValue > 100)) throw new BusinessRuleException('درصد تخفیف آیتم باید بین صفر تا صد باشد.');
            if ($discountType === OrderItem::DISCOUNT_TYPE_FIXED && ($discountValue < 0 || $discountValue > ($quantity * $unitPrice))) throw new BusinessRuleException('مبلغ تخفیف آیتم نمی‌تواند از مبلغ آیتم بیشتر باشد.');
            if ($discountType === OrderItem::DISCOUNT_TYPE_NONE) $discountValue = 0;
            if (!in_array($offerType, OrderItem::OFFER_TYPES, true)) throw new BusinessRuleException('نوع آفر آیتم نامعتبر است.');

            if ($offerType === OrderItem::OFFER_TYPE_NONE) {
                $offerBuy = 0;
                $offerFreePerCycle = 0;
            } else {
                if ($offerBuy <= 0 || $offerFreePerCycle <= 0) throw new BusinessRuleException('برای آفر خرید X، هدیه Y باید هر دو بیشتر از صفر باشند.');
                if ($quantity < $offerBuy) throw new BusinessRuleException('تعداد خرید برای فعال شدن آفر کافی نیست.');
            }

            $subtotal = round($quantity * $unitPrice, 2);
            $discountAmount = $discountType === OrderItem::DISCOUNT_TYPE_PERCENTAGE
                ? min($subtotal, round($subtotal * ($discountValue / 100), 2))
                : ($discountType === OrderItem::DISCOUNT_TYPE_FIXED ? min($subtotal, round($discountValue, 2)) : 0.0);

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($subtotal - $discountAmount, 2),
                'description' => $itemData['description'] ?? null,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'offer_type' => $offerType,
                'offer_buy_quantity' => $offerBuy,
                'offer_free_quantity' => $offerFreePerCycle,
                'offer_title' => $itemData['offer_title'] ?? null,
            ]);
        }
    }
}
