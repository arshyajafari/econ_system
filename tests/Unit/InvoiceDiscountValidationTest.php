<?php

namespace Tests\Unit;

use App\Models\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceDiscountValidationTest extends TestCase
{
    public function test_discount_cannot_exceed_invoice_items_subtotal(): void
    {
        $invoice = new Invoice([
            'subtotal' => 5,
            'discount_amount' => 5.01,
        ]);

        $this->assertTrue($invoice->discountExceedsSubtotal(5.00));
    }

    public function test_discount_equal_to_invoice_items_subtotal_is_valid(): void
    {
        $invoice = new Invoice([
            'subtotal' => 5,
            'discount_amount' => 5,
        ]);

        $this->assertFalse($invoice->discountExceedsSubtotal(5.00));
    }
}
