<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class JalaliDate
{
    public static function fromGregorian(CarbonImmutable $date): array
    {
        $gy = (int) $date->format('Y');
        $gm = (int) $date->format('n');
        $gd = (int) $date->format('j');
        $monthDays = [0,31,59,90,120,151,181,212,243,273,304,334];

        $jy = $gy > 1600 ? 979 : 0;
        $gy -= $gy > 1600 ? 1600 : 621;
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 365 * $gy
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            - 80 + $gd + $monthDays[$gm - 1];

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    public static function toGregorian(int $jy, int $jm, int $jd): CarbonImmutable
    {
        if ($jm < 1 || $jm > 12 || $jd < 1 || $jd > ($jm <= 6 ? 31 : ($jm <= 11 ? 30 : 30))) {
            throw new InvalidArgumentException('Invalid Jalali date.');
        }

        $jy -= 979;
        $days = 365 * $jy
            + intdiv($jy, 33) * 8
            + intdiv(($jy % 33) + 3, 4)
            + 78 + $jd;

        $days += $jm <= 6
            ? ($jm - 1) * 31
            : 186 + ($jm - 7) * 30;

        $gy = 1600 + 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $leap = ($gy % 4 === 0 && ($gy % 100 !== 0 || $gy % 400 === 0));
        $lengths = [31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 1;

        foreach ($lengths as $length) {
            if ($gd <= $length) {
                break;
            }
            $gd -= $length;
            $gm++;
        }

        return CarbonImmutable::create($gy, $gm, $gd, 0, 0, 0, 'Asia/Tehran');
    }

    public static function retentionDate(string $gregorianDate): string
    {
        $date = CarbonImmutable::parse($gregorianDate, 'Asia/Tehran');
        [$year, $month] = self::fromGregorian($date);
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }

        // Reports remain available through the second day of the next Jalali month.
        return self::toGregorian($year, $month, 3)->toDateString();
    }
}
