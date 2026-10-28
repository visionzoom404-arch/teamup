<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Carbon\Carbon;

/** تبدیل تاریخ میلادی ⇄ شمسی (الگوریتم جلالی) */
class Jalali
{
    public const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    public const WEEKDAYS = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه']; // Carbon dayOfWeek 0=Sunday

    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gDm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gDm[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $jm = $days < 186 ? 1 + intdiv($days, 31) : 7 + intdiv($days - 186, 30);
        $jd = 1 + ($days < 186 ? $days % 31 : ($days - 186) % 30);

        return [$jy, $jm, $jd];
    }

    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * intdiv($days, 146097);
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
        $sal = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || $gy % 400 == 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($gm = 0; $gm < 13 && $gd > $sal[$gm]; $gm++) {
            $gd -= $sal[$gm];
        }

        return [$gy, $gm, $gd];
    }

    public static function isLeap(int $jy): bool
    {
        return in_array($jy % 33, [1, 5, 9, 13, 17, 22, 26, 30], true);
    }

    public static function monthLength(int $jy, int $jm): int
    {
        return $jm <= 6 ? 31 : ($jm < 12 ? 30 : (self::isLeap($jy) ? 30 : 29));
    }

    public static function carbon(int $jy, int $jm, int $jd): Carbon
    {
        [$y, $m, $d] = self::toGregorian($jy, $jm, $jd);

        return Carbon::create($y, $m, $d)->startOfDay();
    }

    public static function parts(CarbonInterface|string $date): array
    {
        $c = Carbon::parse($date);

        return self::toJalali($c->year, $c->month, $c->day);
    }

    /** فرمت: Y/m/d  — j (روز بدون صفر)، F (نام ماه)، l (نام روز) */
    public static function format(CarbonInterface|string|null $date, string $fmt = 'Y/m/d'): string
    {
        if (! $date) {
            return '';
        }
        $c = Carbon::parse($date);
        [$jy, $jm, $jd] = self::parts($c);
        $map = [
            'Y' => (string) $jy,
            'y' => substr((string) $jy, -2),
            'm' => sprintf('%02d', $jm),
            'n' => (string) $jm,
            'd' => sprintf('%02d', $jd),
            'j' => (string) $jd,
            'F' => self::MONTHS[$jm - 1],
            'l' => self::WEEKDAYS[$c->dayOfWeek],
            'H' => $c->format('H'),
            'i' => $c->format('i'),
        ];
        $out = '';
        foreach (str_split($fmt) as $ch) {
            $out .= $map[$ch] ?? $ch;
        }

        return self::digits($out);
    }

    public static function digits(string|int|float|null $v): string
    {
        return strtr((string) $v, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    public static function latinDigits(string $v): string
    {
        return strtr($v, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }
}
