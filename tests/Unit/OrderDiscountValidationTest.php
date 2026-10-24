<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderDiscountValidationTest extends TestCase
{
    public function test_percentage_discount_is_not_compared_to_currency_subtotal(): void
    {
        $order = new Order([
            'discount_type' => Order::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
        ]);

        $this->assertFalse($order->discountExceedsSubtotal(5.00));
        $this->assertSame(0.5, $order->calculatedDiscountAmount(5.00));
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        $order = new Order([
            'discount_type' => Order::DISCOUNT_TYPE_FIXED,
            'discount_value' => 6,
        ]);

        $this->assertTrue($order->discountExceedsSubtotal(5.00));
    }

    public function test_fixed_discount_equal_to_subtotal_is_valid(): void
    {
        $order = new Order([
            'discount_type' => Order::DISCOUNT_TYPE_FIXED,
            'discount_value' => 5,
        ]);

        $this->assertFalse($order->discountExceedsSubtotal(5.00));
    }
}
