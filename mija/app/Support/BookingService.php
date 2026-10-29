<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class BookingService
{
    /** ایجاد رزرو: فوری ← در انتظار پرداخت، درخواستی ← در انتظار تأیید میزبان */
    public static function create(User $guest, Property $p, Carbon $in, Carbon $out, int $adults, int $children, ?string $coupon, ?string $note): Booking
    {
        return DB::transaction(function () use ($guest, $p, $in, $out, $adults, $children, $coupon, $note) {
            // قفل ردیف ملک برای جلوگیری از رزرو همزمان
            Property::whereKey($p->id)->lockForUpdate()->first();

            $q = $p->quote($in, $out, $adults + $children, $coupon);
            abort_unless($q['ok'], 422, $q['error'] ?? 'رزرو ممکن نیست.');

            $instant = $p->instant_booking;
            $b = Booking::create([
                'property_id' => $p->id, 'guest_id' => $guest->id, 'host_id' => $p->host_id,
                'check_in' => $in->toDateString(), 'check_out' => $out->toDateString(), 'nights' => $q['nights'],
                'adults' => $adults, 'children' => $children,
                'subtotal' => $q['subtotal'], 'discount' => $q['coupon_discount'], 'coupon_code' => $q['coupon'],
                'service_fee' => $q['service_fee'], 'total' => $q['total'],
                'commission' => $q['commission'], 'host_payout' => $q['host_payout'],
                'breakdown' => Arr::only($q, ['lines', 'base', 'extra', 'cleaning', 'long_pct', 'long_discount', 'avg_night']),
                'status' => $instant ? 'awaiting_payment' : 'pending_host',
                'cancellation_policy' => $p->cancellation_policy,
                'guest_note' => $note,
                'respond_by' => $instant ? null : now()->addHours((int) setting('host_response_hours')),
                'pay_by' => $instant ? now()->addMinutes((int) setting('payment_window_minutes')) : null,
            ]);

            if ($q['coupon']) {
                Coupon::where('code', $q['coupon'])->increment('used_count');
            }

            $b->host->notify($instant ? 'رزرو فوری جدید' : 'درخواست رزرو جدید', "{$p->title} — {$guest->name}", route('host.bookings.show', $b));

            return $b;
        });
    }

    /** هدیه‌ی دعوت: با اولین پرداخت موفق کاربر دعوت‌شده، به دعوت‌کننده و دعوت‌شده اعتبار کیف پول داده می‌شود */
    private static function referralBonus(Booking $b): void
    {
        $guest = $b->guest;
        $bonus = (int) config('mija.referral_bonus');
        if (! $guest->referred_by || ! $bonus || $guest->bookings()->whereNotNull('paid_at')->where('id', '!=', $b->id)->exists()) {
            return;
        }
        $guest->walletChange($bonus, 'هدیه‌ی دعوت دوستان — اولین رزرو', $b);
        if ($ref = $guest->referredBy) {
            $ref->walletChange($bonus, "هدیه‌ی دعوت {$guest->name}", $b);
            $ref->notify('هدیه‌ی دعوت دریافت شد', toman($bonus), route($ref->isHost() ? 'host.wallet' : 'guest.wallet'));
        }
    }

    public static function accept(Booking $b): void
    {
        abort_unless($b->status === 'pending_host', 422);
        $b->update(['responded_at' => now(), 'status' => 'awaiting_payment', 'respond_by' => null, 'pay_by' => now()->addMinutes((int) setting('payment_window_minutes'))]);
        $b->guest->notify('درخواست شما تأیید شد', 'برای قطعی شدن رزرو، پرداخت را انجام دهید.', route('guest.bookings.show', $b));
    }

    public static function reject(Booking $b, ?string $reason = null): void
    {
        abort_unless($b->status === 'pending_host', 422);
        $b->update(['responded_at' => now(), 'status' => 'rejected', 'cancel_reason' => $reason, 'cancelled_at' => now()]);
        $b->guest->notify('درخواست شما رد شد', $reason, route('guest.bookings.show', $b));
    }

    /** پرداخت موفق: رزرو قطعی می‌شود */
    public static function markPaid(Booking $b, Payment $pay, string $refId): void
    {
        DB::transaction(function () use ($b, $pay, $refId) {
            $pay->update(['status' => 'paid', 'ref_id' => $refId, 'paid_at' => now()]);
            $b->update(['status' => 'confirmed', 'paid_at' => now(), 'pay_by' => null]);
        });
        self::referralBonus($b);
        $b->host->notify('رزرو قطعی شد', "{$b->property->title} — ".jdate($b->check_in), route('host.bookings.show', $b));
        $b->guest->notify('رزرو شما قطعی شد', "کد رزرو: {$b->code}", route('guest.bookings.show', $b));
    }

    /** لغو توسط مهمان؛ بازگشت وجه طبق سیاست لغو به کیف پول */
    public static function cancelByGuest(Booking $b): int
    {
        abort_unless(in_array($b->status, ['pending_host', 'awaiting_payment', 'confirmed']), 422);
        $refund = 0;
        DB::transaction(function () use ($b, &$refund) {
            if ($b->status === 'confirmed') {
                $pct = $b->refundPercent();
                $refund = intdiv($b->total * $pct, 100);
                if ($refund > 0) {
                    $b->guest->walletChange($refund, "بازگشت وجه رزرو {$b->code}", $b);
                }
                // سهم میزبان از مبلغ غیرقابل استرداد
                $kept = $b->total - $refund;
                $hostShare = $kept - intdiv($kept * (int) setting('commission_percent'), 100);
                if ($hostShare > 0) {
                    $b->host->walletChange($hostShare, "جبران لغو رزرو {$b->code}", $b);
                }
                $b->settled_at = now();
            }
            $b->update(['status' => 'cancelled_guest', 'cancelled_at' => now(), 'refund_amount' => $refund, 'settled_at' => $b->settled_at]);
        });
        $b->host->notify('رزرو توسط مهمان لغو شد', $b->property->title, route('host.bookings.show', $b));

        return $refund;
    }

    /** لغو توسط میزبان؛ بازگشت کامل وجه */
    public static function cancelByHost(Booking $b, ?string $reason = null): void
    {
        abort_unless(in_array($b->status, ['pending_host', 'awaiting_payment', 'confirmed']), 422);
        DB::transaction(function () use ($b, $reason) {
            $refund = 0;
            if ($b->status === 'confirmed') {
                $refund = $b->total;
                $b->guest->walletChange($refund, "بازگشت کامل وجه رزرو {$b->code} (لغو توسط میزبان)", $b);
            }
            $b->update(['status' => 'cancelled_host', 'cancelled_at' => now(), 'refund_amount' => $refund, 'cancel_reason' => $reason]);
        });
        $b->guest->notify('رزرو توسط میزبان لغو شد', 'مبلغ پرداختی به کیف پول شما بازگشت داده شد.', route('guest.bookings.show', $b));
    }

    /** اجرای دوره‌ای: انقضا، تسویه روز ورود و اتمام اقامت */
    public static function tick(): array
    {
        $r = ['expired' => 0, 'settled' => 0, 'completed' => 0];

        Booking::where('status', 'pending_host')->where('respond_by', '<', now())->get()->each(function ($b) use (&$r) {
            $b->update(['status' => 'expired']);
            $b->guest->notify('درخواست رزرو منقضی شد', 'میزبان در مهلت مقرر پاسخ نداد.', route('guest.bookings.show', $b));
            $r['expired']++;
        });
        Booking::where('status', 'awaiting_payment')->where('pay_by', '<', now())->get()->each(function ($b) use (&$r) {
            $b->update(['status' => 'expired']);
            $r['expired']++;
        });
        // تسویه با میزبان در روز ورود
        Booking::where('status', 'confirmed')->whereNull('settled_at')->whereDate('check_in', '<=', today())->get()->each(function ($b) use (&$r) {
            $b->host->walletChange($b->host_payout, "درآمد رزرو {$b->code}", $b);
            $b->update(['settled_at' => now()]);
            $b->host->notify('درآمد رزرو به کیف پول شما واریز شد', toman($b->host_payout), route('host.wallet'));
            $r['settled']++;
        });
        Booking::where('status', 'confirmed')->whereDate('check_out', '<', today())->get()->each(function ($b) use (&$r) {
            $b->update(['status' => 'completed']);
            // بازگشت وجه وفاداری بر اساس سطح مهمان
            $pct = $b->guest->level()['level']['cashback'];
            if ($pct > 0) {
                $cb = intdiv($b->total * $pct, 100);
                $b->guest->walletChange($cb, "بازگشت وجه سطح وفاداری ({$pct}٪) — رزرو {$b->code}", $b);
                $b->update(['cashback' => $cb]);
            }
            $b->guest->notify('اقامت شما به پایان رسید', 'نظر خود را درباره‌ی اقامت ثبت کنید.', route('guest.bookings.show', $b));
            $r['completed']++;
        });

        return $r;
    }
}
