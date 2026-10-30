<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\PeakPeriod;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class Pricing
{
    /** شب‌های پنجشنبه و جمعه قیمت آخر هفته دارند */
    public static function isWeekendNight(Carbon $d): bool
    {
        return in_array($d->dayOfWeek, [Carbon::THURSDAY, Carbon::FRIDAY], true);
    }

    /** کمیسیون مؤثر برای یک میزبان (مدیر می‌تواند برای هر میزبان جداگانه تعیین کند) */
    public static function commissionFor(?User $host): int
    {
        return (int) ($host?->commission_override ?? setting('commission_percent'));
    }

    /** مبلغ نمایشی به مهمان = مبلغ دریافتی میزبان + کمیسیون پنهان (گرد به بالا تا هزار تومان) */
    public static function gross(int|float $net, int $commission): int
    {
        if ($net <= 0) {
            return 0;
        }

        return (int) (ceil($net / (1 - min($commission, 60) / 100) / 1000) * 1000);
    }

    /** فصل شمسی (spring/summer/autumn/winter) از ماه شمسی */
    public static function season(int $jm): string
    {
        return ['spring', 'spring', 'spring', 'summer', 'summer', 'summer', 'autumn', 'autumn', 'autumn', 'winter', 'winter', 'winter'][$jm - 1];
    }

    /** شاخص روز هفته؛ شنبه = ۰ … جمعه = ۶ */
    public static function dow(Carbon $d): int
    {
        return ($d->dayOfWeek + 1) % 7;
    }

    /**
     * قیمت یک شب از دید میزبان (دریافتی خالص).
     * اولویت: قیمت دلخواه تقویم ← ایام پیک ← (حالت خودکار) ماهانه ← فصلی ← روز هفته ← آخر هفته/میان‌هفته.
     * تخفیف فوریِ همان روز در پایان اعمال می‌شود.
     */
    public static function nightPrice(Property $p, Carbon $night, $overrides, $peaks): int
    {
        $key = $night->toDateString();
        $day = $overrides[$key] ?? null;
        $auto = ($p->pricing_mode ?? 'auto') !== 'manual';
        $price = null;

        if ($day && $day->price) {
            $price = (int) $day->price;
        } elseif ($auto && $p->price_peak && $peaks->contains(fn ($pk) => $night->betweenIncluded($pk->starts_on, $pk->ends_on))) {
            $price = (int) $p->price_peak;
        } elseif ($auto) {
            [, $jm] = Jalali::parts($night);
            $price = (int) (($p->price_months[$jm] ?? null) ?: (($p->price_seasons[self::season($jm)] ?? null) ?: ($p->price_dow[self::dow($night)] ?? null)));
        }
        $price = $price ?: (int) (self::isWeekendNight($night) ? $p->price_weekend : $p->price_weekday);

        if ($day && $day->discount_pct) {
            $price = intdiv($price * (100 - (int) $day->discount_pct), 100);
        }

        return $price;
    }

    /** قیمت نمایشی (برای مهمان) یک شب */
    public static function displayPrice(Property $p, Carbon $night, $overrides, $peaks): int
    {
        return self::gross(self::nightPrice($p, $night, $overrides, $peaks), self::commissionFor($p->host));
    }

    /**
     * محاسبه‌ی کامل قیمت اقامت.
     * خروجی شامل ok=false و error در صورت نامعتبر بودن درخواست است.
     */
    public static function quote(Property $p, Carbon $in, Carbon $out, int $guests, ?string $couponCode = null): array
    {
        $in = $in->copy()->startOfDay();
        $out = $out->copy()->startOfDay();
        $nights = (int) $in->diffInDays($out);
        $err = null;

        if ($in->lt(today())) {
            $err = 'تاریخ ورود گذشته است.';
        } elseif ($nights < 1) {
            $err = 'حداقل یک شب اقامت لازم است.';
        } elseif ($nights < $p->min_nights) {
            $err = 'حداقل مدت اقامت '.fa($p->min_nights).' شب است.';
        } elseif ($nights > $p->max_nights) {
            $err = 'حداکثر مدت اقامت '.fa($p->max_nights).' شب است.';
        } elseif ($guests < 1 || $guests > $p->capacity_max) {
            $err = 'حداکثر ظرفیت '.fa($p->capacity_max).' نفر است.';
        } elseif (! $p->isAvailable($in, $out)) {
            $err = 'این اقامتگاه در بازه‌ی انتخابی آزاد نیست.';
        }
        if ($err) {
            return ['ok' => false, 'error' => $err];
        }

        $overrides = $p->days()->whereBetween('date', [$in->toDateString(), $out->copy()->subDay()->toDateString()])->get()->keyBy(fn ($d) => $d->date->toDateString());
        $peaks = PeakPeriod::where('ends_on', '>=', $in->toDateString())->where('starts_on', '<=', $out->toDateString())->get();

        $c = self::commissionFor($p->host);
        $g = fn ($net) => self::gross($net, $c);

        $lines = [];
        $base = 0;       // خالص میزبان
        $baseG = 0;      // نمایشی
        foreach (CarbonPeriod::create($in, $out->copy()->subDay()) as $night) {
            $price = self::nightPrice($p, $night, $overrides, $peaks);
            $base += $price;
            $lines[] = ['date' => $night->toDateString(), 'price' => $g($price), 'flash' => (int) ($overrides[$night->toDateString()]->discount_pct ?? 0)];
            $baseG += $g($price);
        }

        $extraGuests = max(0, $guests - $p->capacity_base);
        $extra = $extraGuests * (int) $p->extra_guest_fee * $nights;
        $cleaning = (int) $p->cleaning_fee;

        $longPct = $nights >= 30 ? (int) $p->monthly_discount : ($nights >= 7 ? (int) $p->weekly_discount : 0);
        $longDiscount = intdiv($base * $longPct, 100);

        $net = $base + $extra + $cleaning - $longDiscount;

        $couponDiscount = 0;
        $couponApplied = null;
        $couponError = null;
        if ($couponCode) {
            $cp = Coupon::where('code', strtoupper(trim($couponCode)))->first();
            if ($cp && $cp->isUsable($net, $p->host_id)) {
                $couponDiscount = $cp->discountFor($net);
                $couponApplied = $cp->code;
            } else {
                $couponError = 'کد تخفیف معتبر نیست.';
            }
        }
        $netAfter = $net - $couponDiscount;

        // مبالغ نمایشی (کمیسیون در قیمت‌ها ادغام شده و جدا نمایش داده نمی‌شود)
        $extraG = $g($extra);
        $cleaningG = $g($cleaning);
        $longG = $g($longDiscount);
        $couponG = $g($couponDiscount);
        $subtotalG = $baseG + $extraG + $cleaningG - $longG;
        $total = max(0, $subtotalG - $couponG);

        return [
            'ok' => true,
            'nights' => $nights,
            'guests' => $guests,
            'lines' => $lines,
            'base' => $baseG,
            'avg_night' => intdiv($baseG, $nights),
            'extra_guests' => $extraGuests,
            'extra' => $extraG,
            'cleaning' => $cleaningG,
            'long_pct' => $longPct,
            'long_discount' => $longG,
            'subtotal' => $subtotalG,
            'coupon' => $couponApplied,
            'coupon_error' => $couponError,
            'coupon_discount' => $couponG,
            'service_fee' => 0,
            'total' => $total,
            'commission' => max(0, $total - $netAfter),
            'host_payout' => $netAfter,
        ];
    }

    /** اعمال تخفیف پیشنهادی میزبان (روز جایگزین): تخفیف از سهم میزبان کسر می‌شود */
    public static function applyOffer(array $q, int $pct): array
    {
        $q['offer_pct'] = $pct;
        $q['total_before'] = $q['total'];
        $q['total'] = intdiv($q['total'] * (100 - $pct), 100);
        $q['host_payout'] = intdiv($q['host_payout'] * (100 - $pct), 100);
        $q['commission'] = max(0, $q['total'] - $q['host_payout']);

        return $q;
    }
}
