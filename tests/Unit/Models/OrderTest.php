<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase {
    private function orderWithSubtotal(float $subtotal): Order {
        $order = new Order([
            'discount_type' => Order::DISCOUNT_TYPE_NONE,
            'discount_value' => 0,
        ]);

        $order->setRelation('items', new Collection([
            new OrderItem(['total_price' => $subtotal]),
        ]));

        return $order;
    }

    public function test_percentage_discount_is_calculated_from_order_subtotal(): void {
        $order = $this->orderWithSubtotal(1000);
        $order->discount_type = Order::DISCOUNT_TYPE_PERCENTAGE;
        $order->discount_value = 10;

        $this->assertSame(100.0, $order->calculatedDiscountAmount());
        $this->assertSame(900.0, $order->finalAmount());
    }

    public function test_fixed_discount_cannot_reduce_order_below_zero(): void {
        $order = $this->orderWithSubtotal(1000);
        $order->discount_type = Order::DISCOUNT_TYPE_FIXED;
        $order->discount_value = 1200;

        $this->assertSame(1000.0, $order->calculatedDiscountAmount());
        $this->assertSame(0.0, $order->finalAmount());
    }

    public function test_no_discount_keeps_subtotal_as_final_amount(): void {
        $order = $this->orderWithSubtotal(1250.50);

        $this->assertSame(1250.50, $order->itemsSubtotal());
        $this->assertSame(0.0, $order->calculatedDiscountAmount());
        $this->assertSame(1250.50, $order->finalAmount());
    }
}
