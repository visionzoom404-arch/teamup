<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public static function windowMinutes(): int { return (int) setting('payment_window_minutes'); }

    /** ایجاد رزرو: فوری ← در انتظار پرداخت، درخواستی ← در انتظار تأیید میزبان (۳۰ دقیقه) */
    public static function create(User $guest, Property $p, Carbon $in, Carbon $out, int $adults, int $children, ?string $coupon, ?string $note): Booking
    {
        $b = DB::transaction(function () use ($guest, $p, $in, $out, $adults, $children, $coupon, $note) {
            // قفل ردیف ملک برای جلوگیری از رزرو همزمان
            Property::whereKey($p->id)->lockForUpdate()->first();

            $q = $p->quote($in, $out, $adults + $children, $coupon);
            abort_unless($q['ok'], 422, $q['error'] ?? 'رزرو ممکن نیست.');

            $instant = $p->instant_booking;
            $b = Booking::create(self::fields($q) + [
                'property_id' => $p->id, 'guest_id' => $guest->id, 'host_id' => $p->host_id,
                'check_in' => $in->toDateString(), 'check_out' => $out->toDateString(), 'nights' => $q['nights'],
                'adults' => $adults, 'children' => $children,
                'coupon_code' => $q['coupon'],
                'status' => $instant ? 'awaiting_payment' : 'pending_host',
                'cancellation_policy' => $p->cancellation_policy,
                'guest_note' => $note,
                'respond_by' => $instant ? null : now()->addMinutes((int) setting('host_response_minutes')),
                'pay_by' => $instant ? now()->addMinutes(self::windowMinutes()) : null,
            ]);

            if ($q['coupon']) {
                Coupon::where('code', $q['coupon'])->increment('used_count');
            }

            return $b;
        });

        $when = jdate($in, 'j F').' تا '.jdate($out, 'j F');
        if ($b->status === 'pending_host') {
            $b->host->notify('درخواست رزرو جدید — '.fa(setting('host_response_minutes')).' دقیقه فرصت پاسخ', "{$p->title} · {$when} · {$guest->name}", route('host.bookings.show', $b), true, event: 'booking_request_host', vars: ['property' => $p->title, 'dates' => $when, 'guest' => $guest->name, 'minutes' => fa(setting('host_response_minutes'))]);
            $guest->notify('درخواست شما برای میزبان ارسال شد', 'میزبان تا '.fa(setting('host_response_minutes')).' دقیقه پاسخ می‌دهد.', route('guest.bookings.show', $b), true, event: 'booking_request_guest', vars: ['property' => $p->title, 'minutes' => fa(setting('host_response_minutes'))]);
        } else {
            $b->host->notify('رزرو فوری جدید', "{$p->title} · {$when} · {$guest->name}", route('host.bookings.show', $b), true, event: 'booking_instant_host', vars: ['property' => $p->title, 'dates' => $when, 'guest' => $guest->name]);
        }

        return $b;
    }

    private static function fields(array $q): array
    {
        return [
            'subtotal' => $q['subtotal'], 'discount' => $q['coupon_discount'], 'service_fee' => 0, 'total' => $q['total'],
            'commission' => $q['commission'], 'host_payout' => $q['host_payout'],
            'breakdown' => Arr::only($q, ['lines', 'base', 'extra', 'cleaning', 'long_pct', 'long_discount', 'avg_night', 'offer_pct', 'total_before']),
        ];
    }

    public static function accept(Booking $b): void
    {
        abort_unless($b->status === 'pending_host', 422);
        $b->update(['responded_at' => now(), 'status' => 'awaiting_payment', 'respond_by' => null, 'pay_by' => now()->addMinutes(self::windowMinutes())]);
        $b->guest->notify('درخواست شما تأیید شد؛ برای پرداخت '.fa(self::windowMinutes()).' دقیقه فرصت دارید', $b->property->title, route('guest.bookings.show', $b), true, event: 'booking_accepted_guest', vars: ['property' => $b->property->title, 'minutes' => fa(self::windowMinutes())]);
    }

    /**
     * رد درخواست. اگر $offer شامل [check_in, check_out, discount] باشد، به‌جای رد قطعی،
     * یک روز جایگزین با تخفیف به مهمان پیشنهاد می‌شود.
     */
    public static function reject(Booking $b, ?string $reason = null, ?array $offer = null): void
    {
        abort_unless($b->status === 'pending_host', 422);
        if ($offer) {
            $q = $b->property->quote($offer['check_in'], $offer['check_out'], $b->guestsCount());
            if (! $q['ok']) {
                throw ValidationException::withMessages(['offer_check_in' => 'روز پیشنهادی در دسترس نیست: '.$q['error']]);
            }
            $b->update([
                'responded_at' => now(), 'status' => 'offered', 'respond_by' => null, 'cancel_reason' => $reason,
                'offer_status' => 'offered', 'offer_check_in' => $offer['check_in']->toDateString(), 'offer_check_out' => $offer['check_out']->toDateString(),
                'offer_discount' => $offer['discount'], 'offer_expires_at' => now()->addMinutes((int) setting('offer_minutes')),
            ]);
            $b->guest->notify('میزبان روز دیگری با '.fa($offer['discount']).'٪ تخفیف پیشنهاد داد', 'برای پذیرش یا رد پیشنهاد '.fa(setting('offer_minutes')).' دقیقه فرصت دارید.', route('guest.bookings.show', $b), true, event: 'booking_offer_guest', vars: ['property' => $b->property->title, 'discount' => fa($offer['discount'])]);

            return;
        }
        $b->update(['responded_at' => now(), 'status' => 'rejected', 'cancel_reason' => $reason, 'cancelled_at' => now()]);
        $b->guest->notify('درخواست شما رد شد', $reason ?: 'می‌توانید اقامتگاه دیگری را رزرو کنید.', route('search'), true, event: 'booking_rejected_guest', vars: ['property' => $b->property->title]);
    }

    /** پاسخ مهمان به پیشنهاد روز جایگزین */
    public static function respondOffer(Booking $b, bool $accept): void
    {
        abort_unless($b->canRespondOffer(), 422, 'مهلت پاسخ به پیشنهاد به پایان رسیده است.');
        if (! $accept) {
            $b->update(['status' => 'rejected', 'offer_status' => 'declined', 'cancelled_at' => now()]);
            $b->host->notify('مهمان پیشنهاد روز جایگزین را نپذیرفت', $b->property->title, route('host.bookings.show', $b));

            return;
        }
        DB::transaction(function () use ($b) {
            Property::whereKey($b->property_id)->lockForUpdate()->first();
            $q = $b->property->quote($b->offer_check_in, $b->offer_check_out, $b->guestsCount());
            abort_unless($q['ok'], 422, 'روز پیشنهادی دیگر آزاد نیست.');
            $q = Pricing::applyOffer($q, (int) $b->offer_discount);
            $b->update(self::fields($q) + [
                'check_in' => $b->offer_check_in->toDateString(), 'check_out' => $b->offer_check_out->toDateString(), 'nights' => $q['nights'],
                'coupon_code' => null, 'status' => 'awaiting_payment', 'offer_status' => 'accepted', 'offer_expires_at' => null,
                'pay_by' => now()->addMinutes(self::windowMinutes()),
            ]);
        });
        $b->host->notify('مهمان پیشنهاد شما را پذیرفت', 'منتظر پرداخت مهمان هستیم.', route('host.bookings.show', $b), true, event: 'booking_offer_accepted_host', vars: ['property' => $b->property->title]);
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

    /** پرداخت کامل موفق: رزرو قطعی می‌شود و مشخصات دقیق دو طرف باز می‌شود */
    public static function markPaid(Booking $b, Payment $pay, string $refId): void
    {
        DB::transaction(function () use ($b, $pay, $refId) {
            $pay->update(['status' => 'paid', 'ref_id' => $refId, 'paid_at' => now()]);
            $b->update(['status' => 'confirmed', 'paid_at' => now(), 'pay_by' => null, 'payment_method' => $pay->gateway]);
        });
        self::referralBonus($b);
        self::confirmedNotices($b);
    }

    private static function confirmedNotices(Booking $b): void
    {
        $p = $b->property;
        $b->host->notify('رزرو قطعی شد — مشخصات مهمان را ببینید', "{$p->title} · ".jdate($b->check_in), route('host.bookings.show', $b), true, event: 'booking_confirmed_host', vars: ['property' => $p->title, 'dates' => jdate($b->check_in), 'guest' => $b->guest->name]);
        $b->guest->notify('رزرو شما قطعی شد — آدرس و تلفن میزبان در لینک', "کد رزرو {$b->code}", route('guest.bookings.show', $b), true, event: 'booking_confirmed_guest', vars: ['property' => $p->title, 'code' => $b->code]);
    }

    /* ---------- اقساط ---------- */

    /** ساخت برنامه‌ی اقساط: قسط اول همین حالا، بقیه تا ۳ روز مانده به ورود */
    public static function startInstallments(Booking $b): void
    {
        abort_unless($b->guest->canInstallment() && $b->status === 'awaiting_payment', 422);
        $min = (int) setting('installment_min_days');
        abort_if(now()->diffInDays($b->check_in, false) < $min, 422, 'رزرو اقساطی فقط برای سفرهایی است که حداقل '.fa($min).' روز دیگر شروع می‌شوند.');
        if ($b->installments()->exists()) {
            return;
        }
        $parts = max(2, (int) setting('installment_parts'));
        $first = intdiv($b->total * (int) setting('installment_first_pct'), 100);
        $rest = $b->total - $first;
        $each = intdiv($rest, $parts - 1);
        $last = $b->check_in->copy()->subDays(3);
        $span = max(1, (int) now()->startOfDay()->diffInDays($last, false));
        DB::transaction(function () use ($b, $parts, $first, $each, $rest, $last, $span) {
            for ($i = 1; $i <= $parts; $i++) {
                $amount = $i === 1 ? $first : ($i === $parts ? $rest - $each * ($parts - 2) : $each);
                $due = $i === 1 ? today() : ($i === $parts ? $last : today()->addDays((int) round($span * ($i - 1) / ($parts - 1))));
                Installment::create(['booking_id' => $b->id, 'seq' => $i, 'amount' => $amount, 'due_on' => $due]);
            }
            $b->update(['is_installment' => true]);
        });
    }

    public static function markInstallmentPaid(Installment $in, Payment $pay, string $refId): void
    {
        $b = $in->booking;
        DB::transaction(function () use ($in, $pay, $refId, $b) {
            $pay->update(['status' => 'paid', 'ref_id' => $refId, 'paid_at' => now()]);
            $in->update(['status' => 'paid', 'paid_at' => now()]);
            if ($in->seq === 1) {
                $b->update(['status' => 'confirmed', 'paid_at' => now(), 'pay_by' => null, 'payment_method' => 'installment']);
            }
        });
        if ($in->seq === 1) {
            self::referralBonus($b);
            self::confirmedNotices($b);
        } else {
            $b->host->notify('قسط رزرو پرداخت شد', "{$b->code} — قسط ".fa($in->seq), route('host.bookings.show', $b));
        }
    }

    public static function paidAmount(Booking $b): int
    {
        return $b->is_installment ? (int) $b->installments()->where('status', 'paid')->sum('amount') : ($b->isPaid() ? $b->total : 0);
    }

    public static function fullyPaid(Booking $b): bool
    {
        return ! $b->is_installment || ! $b->installments()->where('status', '!=', 'paid')->exists();
    }

    /* ---------- لغو ---------- */

    /** لغو توسط مهمان؛ بازگشت وجه طبق سیاست لغو به کیف پول */
    public static function cancelByGuest(Booking $b): int
    {
        abort_unless(in_array($b->status, ['pending_host', 'awaiting_payment', 'payment_review', 'offered', 'confirmed']), 422);
        $refund = 0;
        DB::transaction(function () use ($b, &$refund) {
            if ($b->status === 'confirmed') {
                $paid = self::paidAmount($b);
                $pct = $b->refundPercent();
                $refund = intdiv($paid * $pct, 100);
                if ($refund > 0) {
                    $b->guest->walletChange($refund, "بازگشت وجه رزرو {$b->code}", $b);
                }
                // سهم میزبان از مبلغ غیرقابل استرداد
                $hostShare = intdiv($b->host_payout * ($paid - $refund), max(1, $b->total));
                if ($hostShare > 0) {
                    $b->host->walletChange($hostShare, "جبران لغو رزرو {$b->code}", $b);
                }
                $b->settled_at = now();
            }
            $b->installments()->where('status', 'due')->update(['status' => 'late']);
            $b->update(['status' => 'cancelled_guest', 'cancelled_at' => now(), 'refund_amount' => $refund, 'settled_at' => $b->settled_at]);
        });
        $b->host->notify('رزرو توسط مهمان لغو شد', $b->property->title, route('host.bookings.show', $b), true, event: 'booking_cancelled_host', vars: ['property' => $b->property->title]);

        return $refund;
    }

    /** لغو توسط میزبان؛ بازگشت کامل وجه */
    public static function cancelByHost(Booking $b, ?string $reason = null): void
    {
        abort_unless(in_array($b->status, ['pending_host', 'awaiting_payment', 'payment_review', 'confirmed']), 422);
        DB::transaction(function () use ($b, $reason) {
            $refund = 0;
            if ($b->status === 'confirmed') {
                $refund = self::paidAmount($b);
                $b->guest->walletChange($refund, "بازگشت کامل وجه رزرو {$b->code} (لغو توسط میزبان)", $b);
            }
            $b->update(['status' => 'cancelled_host', 'cancelled_at' => now(), 'refund_amount' => $refund, 'cancel_reason' => $reason]);
        });
        $b->guest->notify('رزرو توسط میزبان لغو شد', 'مبلغ پرداختی به کیف پول شما بازگشت داده شد.', route('guest.bookings.show', $b), true, event: 'booking_cancelled_guest', vars: ['property' => $b->property->title]);
    }

    /** انقضای لحظه‌ای هنگام مشاهده‌ی رزرو (بدون انتظار برای cron) */
    public static function lazy(Booking $b): Booking
    {
        $now = now();
        if ($b->status === 'pending_host' && $b->respond_by && $b->respond_by->lt($now)) {
            $b->update(['status' => 'expired']);
        } elseif ($b->status === 'awaiting_payment' && $b->pay_by && $b->pay_by->lt($now)) {
            $b->update(['status' => 'expired']);
        } elseif ($b->status === 'offered' && $b->offer_expires_at && $b->offer_expires_at->lt($now)) {
            $b->update(['status' => 'expired', 'offer_status' => 'declined']);
        }

        return $b;
    }

    /* ---------- اجرای دوره‌ای ---------- */

    /** انقضا، یادآوری، تسویه روز ورود، اتمام اقامت، نظرسنجی پیامکی، اعتبار هدیه و اقساط */
    public static function tick(): array
    {
        $r = ['expired' => 0, 'settled' => 0, 'completed' => 0, 'reminded' => 0, 'cashback_expired' => 0, 'late' => 0, 'ads' => 0];

        Booking::where('status', 'pending_host')->where('respond_by', '<', now())->get()->each(function ($b) use (&$r) {
            $b->update(['status' => 'expired']);
            $b->guest->notify('درخواست رزرو منقضی شد', 'میزبان در مهلت مقرر پاسخ نداد؛ اقامتگاه‌های مشابه را ببینید.', route('property', $b->property), true, event: 'booking_expired_guest', vars: ['property' => $b->property->title]);
            $r['expired']++;
        });
        Booking::where('status', 'awaiting_payment')->where('pay_by', '<', now())->get()->each(function ($b) use (&$r) {
            $b->update(['status' => 'expired']);
            $b->guest->notify('مهلت پرداخت رزرو تمام شد', $b->code, route('guest.bookings.show', $b));
            $r['expired']++;
        });
        Booking::where('status', 'offered')->where('offer_expires_at', '<', now())->get()->each(function ($b) use (&$r) {
            $b->update(['status' => 'expired', 'offer_status' => 'declined']);
            $r['expired']++;
        });

        // اقساط معوق: لغو خودکار و بازگشت کامل مبلغ پرداخت‌شده به کیف پول
        Booking::where('status', 'confirmed')->where('is_installment', true)->whereHas('installments', fn ($q) => $q->where('status', 'due')->where('due_on', '<', today()->subDay()))->get()->each(function ($b) use (&$r) {
            $paid = self::paidAmount($b);
            DB::transaction(function () use ($b, $paid) {
                if ($paid > 0) {
                    $b->guest->walletChange($paid, "بازگشت مبلغ پرداختی رزرو {$b->code} (عدم پرداخت قسط)", $b);
                }
                $b->installments()->where('status', 'due')->update(['status' => 'late']);
                $b->update(['status' => 'cancelled_system', 'cancelled_at' => now(), 'refund_amount' => $paid]);
            });
            $b->guest->notify('رزرو به‌دلیل عدم پرداخت قسط لغو شد', 'مبلغ پرداختی به کیف پول بازگشت.', route('guest.bookings.show', $b), true, event: 'booking_cancelled_guest', vars: ['property' => $b->property->title]);
            $b->host->notify('رزرو اقساطی لغو شد', $b->code, route('host.bookings.show', $b));
            $r['late']++;
        });

        // یادآوری یک روز قبل از ورود (با آدرس دقیق)
        Booking::where('status', 'confirmed')->whereNull('reminded_at')->whereDate('check_in', '<=', today()->addDay())->whereDate('check_in', '>=', today())->get()->each(function ($b) use (&$r) {
            $b->update(['reminded_at' => now()]);
            $b->guest->notify('فردا سفر شماست! ', "{$b->property->title} — ورود ساعت {$b->property->check_in_time}", route('guest.bookings.show', $b), true, event: 'booking_reminder_guest', vars: ['property' => $b->property->title, 'time' => fa($b->property->check_in_time)]);
            $r['reminded']++;
        });

        // تسویه با میزبان در روز ورود (پس از پرداخت کامل)
        Booking::where('status', 'confirmed')->whereNull('settled_at')->whereDate('check_in', '<=', today())->get()->each(function ($b) use (&$r) {
            if (! self::fullyPaid($b)) {
                return;
            }
            $b->host->walletChange($b->host_payout, "درآمد رزرو {$b->code}", $b);
            $b->update(['settled_at' => now()]);
            $b->host->notify('درآمد رزرو به کیف پول شما واریز شد', toman($b->host_payout), route('host.wallet'));
            $r['settled']++;
        });

        // پایان اقامت + نظرسنجی پیامکی
        Booking::where('status', 'confirmed')->whereDate('check_out', '<', today())->get()->each(function ($b) use (&$r) {
            $b->update(['status' => 'completed', 'survey_sent_at' => now()]);
            $b->guest->notify('اقامت شما چطور بود؟ نظرتان را بنویسید و هدیه بگیرید', $b->property->title, route('guest.bookings.show', $b).'#review', true, event: 'review_request_guest', vars: ['property' => $b->property->title]);
            $b->host->notify('اقامت مهمان تمام شد؛ به مهمان امتیاز دهید', $b->guest->name, route('host.bookings.show', $b));
            $r['completed']++;
        });

        $r['cashback_expired'] = Cashback::expireDue();
        $r['ads'] = Ads::tick();

        return $r;
    }
}
