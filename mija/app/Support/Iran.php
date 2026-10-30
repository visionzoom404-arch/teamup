<?php

namespace App\Support;

/** اعتبارسنجی اطلاعات ایرانی: کدملی، شماره کارت، شبا */
class Iran
{
    public static function nationalCode(string $c): bool
    {
        $c = Jalali::latinDigits($c);
        if (! preg_match('/^\d{10}$/', $c) || preg_match('/^(\d)\1{9}$/', $c)) {
            return false;
        }
        $s = 0;
        for ($i = 0; $i < 9; $i++) {
            $s += (int) $c[$i] * (10 - $i);
        }
        $r = $s % 11;

        return $r < 2 ? (int) $c[9] === $r : (int) $c[9] === 11 - $r;
    }

    public static function card(string $n): bool
    {
        $n = preg_replace('/\D/', '', Jalali::latinDigits($n));
        if (strlen($n) !== 16) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 16; $i++) {
            $d = (int) $n[$i];
            if ($i % 2 === 0) {
                $d *= 2;
                $d > 9 && $d -= 9;
            }
            $sum += $d;
        }

        return $sum % 10 === 0;
    }

    public static function sheba(string $s): bool
    {
        $s = strtoupper(preg_replace('/\s/', '', Jalali::latinDigits($s)));
        if (! preg_match('/^IR\d{24}$/', $s)) {
            return false;
        }
        $t = substr($s, 4).'1827'.substr($s, 2, 2);   // IR=1827
        $rem = 0;
        foreach (str_split($t) as $ch) {
            $rem = ($rem * 10 + (int) $ch) % 97;
        }

        return $rem === 1;
    }

    private const BINS = [
        '603799' => 'ملی', '589210' => 'سپه', '627648' => 'توسعه صادرات', '627961' => 'صنعت و معدن', '603770' => 'کشاورزی', '628023' => 'مسکن',
        '627760' => 'پست بانک', '502908' => 'توسعه تعاون', '627412' => 'اقتصاد نوین', '622106' => 'پارسیان', '639194' => 'پارسیان', '502229' => 'پاسارگاد',
        '639347' => 'پاسارگاد', '627488' => 'کارآفرین', '621986' => 'سامان', '639346' => 'سینا', '639607' => 'سرمایه', '502806' => 'شهر', '504706' => 'شهر',
        '603769' => 'صادرات', '610433' => 'ملت', '991975' => 'ملت', '589463' => 'رفاه', '627353' => 'تجارت', '585983' => 'تجارت', '639370' => 'مهر اقتصاد',
        '606373' => 'مهر ایران', '636214' => 'آینده', '502938' => 'دی', '505416' => 'گردشگری', '636949' => 'حکمت ایرانیان', '505785' => 'ایران زمین',
        '627381' => 'انصار', '504172' => 'رسالت', '636795' => 'بانک مرکزی', '606256' => 'ملل', '628157' => 'اعتباری توسعه', '505801' => 'کوثر',
    ];

    public static function bank(string $card): ?string
    {
        return self::BINS[substr(preg_replace('/\D/', '', $card), 0, 6)] ?? null;
    }
}
