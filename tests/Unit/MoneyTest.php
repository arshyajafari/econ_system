<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_decimal_amounts_are_compared_in_minor_units(): void
    {
        $this->assertSame(
            30,
            Money::toMinorUnits('0.10') + Money::toMinorUnits('0.20'),
        );
        $this->assertFalse(Money::sumExceeds('0.30', '0.10', '0.20'));
        $this->assertTrue(Money::sumExceeds('0.29', '0.10', '0.20'));
    }

    public function test_add_returns_the_sum_at_currency_precision(): void
    {
        $this->assertEquals(0.30, Money::add('0.10', '0.20'));
    }
}
