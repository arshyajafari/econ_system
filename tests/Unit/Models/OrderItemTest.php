<?php

namespace Tests\Unit\Models;

use App\Models\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase {
    public function test_percentage_item_discount_is_calculated_from_paid_quantity(): void {
        $item = new OrderItem([
            'quantity' => 6,
            'unit_price' => 100,
            'discount_type' => OrderItem::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
        ]);

        $this->assertSame(60.0, $item->calculatedDiscountAmount());
        $this->assertSame(6, $item->fulfillmentQuantity());
    }

    public function test_buy_six_get_one_is_seven_units_for_fulfillment_but_only_six_units_are_billed(): void {
        $item = new OrderItem([
            'quantity' => 6,
            'unit_price' => 100,
            'total_price' => 600,
            'offer_type' => OrderItem::OFFER_TYPE_BUY_X_GET_Y,
            'offer_buy_quantity' => 6,
            'offer_free_quantity' => 1,
        ]);

        $this->assertSame(7, $item->fulfillmentQuantity());
        $this->assertSame(600.0, (float) $item->total_price);
        $this->assertTrue($item->isOfferValid());
    }

    public function test_buy_twelve_get_one_per_six_can_fulfill_fourteen_units_only_when_rule_is_materialized(): void {
        $item = new OrderItem([
            'quantity' => 12,
            'offer_type' => OrderItem::OFFER_TYPE_BUY_X_GET_Y,
            'offer_buy_quantity' => 6,
            'offer_free_quantity' => 2,
        ]);

        $this->assertSame(14, $item->fulfillmentQuantity());
    }
}
