<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\PeakPeriod;
use App\Models\Property;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class Pricing
{
    /** شب‌های پنجشنبه و جمعه قیمت آخر هفته دارند */
    public static function isWeekendNight(Carbon $d): bool
    {
        return in_array($d->dayOfWeek, [Carbon::THURSDAY, Carbon::FRIDAY], true);
    }

    /** قیمت یک شب: اولویت با قیمت دلخواه تقویم، سپس ایام پیک، سپس آخر هفته/میان‌هفته */
    public static function nightPrice(Property $p, Carbon $night, $overrides, $peaks): int
    {
        $key = $night->toDateString();
        if (isset($overrides[$key]) && $overrides[$key]->price) {
            return (int) $overrides[$key]->price;
        }
        if ($p->price_peak && $peaks->contains(fn ($pk) => $night->betweenIncluded($pk->starts_on, $pk->ends_on))) {
            return (int) $p->price_peak;
        }

        return (int) (self::isWeekendNight($night) ? $p->price_weekend : $p->price_weekday);
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

        $lines = [];
        $base = 0;
        foreach (CarbonPeriod::create($in, $out->copy()->subDay()) as $night) {
            $price = self::nightPrice($p, $night, $overrides, $peaks);
            $base += $price;
            $lines[] = ['date' => $night->toDateString(), 'price' => $price];
        }

        $extraGuests = max(0, $guests - $p->capacity_base);
        $extra = $extraGuests * (int) $p->extra_guest_fee * $nights;
        $cleaning = (int) $p->cleaning_fee;

        $longPct = $nights >= 30 ? (int) $p->monthly_discount : ($nights >= 7 ? (int) $p->weekly_discount : 0);
        $longDiscount = intdiv($base * $longPct, 100);

        $subtotal = $base + $extra + $cleaning - $longDiscount;

        $couponDiscount = 0;
        $couponApplied = null;
        $couponError = null;
        if ($couponCode) {
            $c = Coupon::where('code', strtoupper(trim($couponCode)))->first();
            if ($c && $c->isUsable($subtotal, $p->host_id)) {
                $couponDiscount = $c->discountFor($subtotal);
                $couponApplied = $c->code;
            } else {
                $couponError = 'کد تخفیف معتبر نیست.';
            }
        }

        $afterDiscount = $subtotal - $couponDiscount;
        $serviceFee = intdiv($afterDiscount * (int) setting('service_fee_percent'), 100);
        $total = $afterDiscount + $serviceFee;
        $commission = intdiv($afterDiscount * (int) setting('commission_percent'), 100);

        return [
            'ok' => true,
            'nights' => $nights,
            'guests' => $guests,
            'lines' => $lines,
            'base' => $base,
            'avg_night' => intdiv($base, $nights),
            'extra_guests' => $extraGuests,
            'extra' => $extra,
            'cleaning' => $cleaning,
            'long_pct' => $longPct,
            'long_discount' => $longDiscount,
            'subtotal' => $subtotal,
            'coupon' => $couponApplied,
            'coupon_error' => $couponError,
            'coupon_discount' => $couponDiscount,
            'service_fee' => $serviceFee,
            'total' => $total,
            'commission' => $commission,
            'host_payout' => $afterDiscount - $commission,
        ];
    }
}
