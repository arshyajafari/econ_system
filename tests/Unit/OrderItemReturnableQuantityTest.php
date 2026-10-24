<?php

namespace Tests\Unit;

use App\Models\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderItemReturnableQuantityTest extends TestCase
{
    public function test_paid_and_free_units_have_separate_return_limits(): void
    {
        $item = new OrderItem([
            'quantity' => 10,
            'offer_type' => OrderItem::OFFER_TYPE_BUY_X_GET_Y,
            'offer_buy_quantity' => 10,
            'offer_free_quantity' => 2,
        ]);

        self::assertSame(
            ['paid' => 8, 'free' => 1],
            $item->remainingReturnableQuantities(
                returnedPaidQuantity: 2,
                returnedFreeQuantity: 1,
            ),
        );
    }

    public function test_returnable_quantities_never_become_negative(): void
    {
        $item = new OrderItem([
            'quantity' => 3,
            'offer_type' => OrderItem::OFFER_TYPE_NONE,
            'offer_buy_quantity' => 0,
            'offer_free_quantity' => 0,
        ]);

        self::assertSame(
            ['paid' => 0, 'free' => 0],
            $item->remainingReturnableQuantities(
                returnedPaidQuantity: 9,
                returnedFreeQuantity: 4,
            ),
        );
    }
}
