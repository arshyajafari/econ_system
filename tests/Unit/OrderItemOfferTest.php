<?php

namespace Tests\Unit;

use App\Models\OrderItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderItemOfferTest extends TestCase
{
    #[DataProvider('fixedGiftOfferCases')]
    public function test_fulfillment_quantity_includes_the_explicit_gift_quantity(
        int $purchaseQuantity,
        int $giftQuantity,
        int $expectedFulfillmentQuantity,
    ): void {
        $item = new OrderItem([
            'quantity' => $purchaseQuantity,
            'offer_type' => $giftQuantity > 0
                ? OrderItem::OFFER_TYPE_BUY_X_GET_Y
                : OrderItem::OFFER_TYPE_NONE,
            // The order form represents a fixed gift quantity for this order.
            'offer_buy_quantity' => $giftQuantity > 0 ? $purchaseQuantity : 0,
            'offer_free_quantity' => $giftQuantity,
        ]);

        self::assertSame($giftQuantity, $item->effectiveFreeQuantity());
        self::assertSame($expectedFulfillmentQuantity, $item->fulfillmentQuantity());
    }

    public static function fixedGiftOfferCases(): array
    {
        return [
            '10 purchased plus 1 gift' => [10, 1, 11],
            '100 purchased plus 50 gifts' => [100, 50, 150],
            'purchase without a gift' => [100, 0, 100],
        ];
    }
}
