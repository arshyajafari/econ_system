<?php

namespace Tests\Unit;

use App\Exceptions\BusinessRuleException;
use App\Models\OrderReturnItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\OrderReturnAmountCalculator;
use PHPUnit\Framework\TestCase;

class OrderReturnAmountCalculatorTest extends TestCase
{
    public function test_manual_amount_may_equal_current_value_of_paid_units(): void
    {
        $item = $this->returnItem(quantity: 3, freeQuantity: 1, unitPrice: '100.00', currentPrice: '150.00');

        (new OrderReturnAmountCalculator())->validateManualAmount([$item], 300.00);

        $this->assertTrue(true);
    }

    public function test_manual_amount_cannot_exceed_current_value_of_paid_units(): void
    {
        $item = $this->returnItem(quantity: 3, freeQuantity: 1, unitPrice: '100.00', currentPrice: '150.00');

        $this->expectException(BusinessRuleException::class);
        (new OrderReturnAmountCalculator())->validateManualAmount([$item], 300.01);
    }

    public function test_manual_amount_uses_original_unit_price_when_no_current_price_exists(): void
    {
        $item = $this->returnItem(quantity: 2, freeQuantity: 1, unitPrice: '125.00');

        (new OrderReturnAmountCalculator())->validateManualAmount([$item], 125.00);

        $this->expectException(BusinessRuleException::class);
        (new OrderReturnAmountCalculator())->validateManualAmount([$item], 125.01);
    }

    private function returnItem(
        int $quantity,
        int $freeQuantity,
        string $unitPrice,
        ?string $currentPrice = null,
    ): OrderReturnItem {
        $item = new OrderReturnItem();
        $item->setAttribute('quantity', $quantity);
        $item->setAttribute('free_quantity', $freeQuantity);
        $item->setAttribute('unit_price', $unitPrice);

        $product = new Product();
        if ($currentPrice !== null) {
            $price = new ProductPrice();
            $price->setAttribute('sale_price', $currentPrice);
            $product->setRelation('currentPrice', $price);
        } else {
            $product->setRelation('currentPrice', null);
        }

        $item->setRelation('product', $product);

        return $item;
    }
}
