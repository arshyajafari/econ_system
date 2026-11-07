<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class JalaliDateTest extends TestCase
{
    public function test_gregorian_date_is_converted_to_jalali_and_retention_starts_on_third_day_of_next_month(): void
    {
        $date = CarbonImmutable::parse('2026-11-07', 'Asia/Tehran');

        $this->assertSame([1405, 8, 16], JalaliDate::fromGregorian($date));
        $this->assertSame('2026-11-24', JalaliDate::retentionDate('2026-11-07'));
    }

    public function test_jalali_to_gregorian_conversion_is_consistent(): void
    {
        $this->assertSame('2026-11-24', JalaliDate::toGregorian(1405, 9, 3)->toDateString());
    }
}
