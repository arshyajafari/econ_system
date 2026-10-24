<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\OrderReturnItem;
use App\Support\Money;

class OrderReturnAmountCalculator
{
    /**
     * Validate a manual return credit against the current value of the paid
     * units being returned. Promotional free units do not generate credit.
     *
     * @param iterable<OrderReturnItem> $items
     */
    public function validateManualAmount(iterable $items, float $manualAmount): float
    {
        if ($manualAmount <= 0) {
            throw new BusinessRuleException('مبلغ دستی مرجوعی باید بیشتر از صفر باشد.');
        }

        $maximumMinorUnits = 0;

        foreach ($items as $item) {
            $paidQuantity = max(0, (int) $item->quantity - (int) $item->free_quantity);
            if ($paidQuantity === 0) {
                continue;
            }

            $currentPrice = $item->product?->currentPrice?->sale_price;
            $unitPrice = $currentPrice !== null
                ? $currentPrice
                : $item->unit_price;

            $maximumMinorUnits += Money::toMinorUnits($unitPrice) * $paidQuantity;
        }

        $maximumAmount = $maximumMinorUnits / 100;

        if (Money::sumExceeds($maximumAmount, $manualAmount)) {
            throw new BusinessRuleException(
                'مبلغ دستی مرجوعی نمی‌تواند از ارزش روز اقلام پولی مرجوعی بیشتر باشد.'
            );
        }

        return $maximumAmount;
    }
}
