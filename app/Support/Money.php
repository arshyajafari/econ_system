<?php

namespace App\Support;

final class Money
{
    public static function toMinorUnits(float|int|string|null $amount): int
    {
        return (int) round((float) ($amount ?? 0) * 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function add(float|int|string|null ...$amounts): float
    {
        $minorUnits = array_sum(array_map(
            self::toMinorUnits(...),
            $amounts,
        ));

        return $minorUnits / 100;
    }

    public static function sumExceeds(
        float|int|string|null $limit,
        float|int|string|null ...$amounts,
    ): bool {
        $sumMinorUnits = array_sum(array_map(
            self::toMinorUnits(...),
            $amounts,
        ));

        return $sumMinorUnits > self::toMinorUnits($limit);
    }
}
