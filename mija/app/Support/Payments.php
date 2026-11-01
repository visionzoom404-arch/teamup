<?php

namespace App\Support;

use App\Models\Installment;
use App\Models\Payment;
use App\Models\User;

/** تکمیل/رد پرداخت‌ها: درگاه، کارت‌به‌کارت، کیف پول */
class Payments
{
    /** کارت‌های مقصد کارت‌به‌کارت از تنظیمات مدیر (هر خط: شماره|نام صاحب کارت|بانک) */
    public static function cards(): array
    {
        $out = [];
        foreach (preg_split('/\R/u', (string) setting('cards')) as $line) {
            $p = array_map('trim', explode('|', $line));
            if (preg_match('/^\d{16}$/', preg_replace('/\D/', '', $p[0] ?? ''))) {
                $out[] = ['number' => preg_replace('/\D/', '', $p[0]), 'holder' => $p[1] ?? '', 'bank' => $p[2] ?? ''];
            }
        }

        return $out;
    }

    public static function cardEnabled(): bool { return (bool) (int) setting('card_to_card_enabled') && self::cards() !== []; }

    public static function fmtCard(string $n): string { return fa(trim(chunk_split($n, 4, ' '))); }

    /** پرداخت موفق (هر روش) */
    public static function complete(Payment $pay, string $ref): void
    {
        if ($pay->status === 'paid') {
            return;
        }
        if ($pay->purpose === 'topup') {
            $pay->update(['status' => 'paid', 'ref_id' => $ref, 'paid_at' => now()]);
            $pay->user->walletChange($pay->amount, 'شارژ کیف پول');

            return;
        }
        if ($pay->installment_id && ($in = Installment::find($pay->installment_id))) {
            BookingService::markInstallmentPaid($in, $pay, $ref);

            return;
        }
        BookingService::markPaid($pay->booking, $pay, $ref);
    }

    /** تأیید کارت‌به‌کارت توسط مدیر */
    public static function approveCard(Payment $pay): void
    {
        abort_unless($pay->status === 'review', 422);
        self::complete($pay, 'C'.$pay->id);
    }

    /** رد کارت‌به‌کارت: رزرو دوباره برای پرداخت باز می‌شود */
    public static function rejectCard(Payment $pay, ?string $note = null): void
    {
        abort_unless($pay->status === 'review', 422);
        $pay->update(['status' => 'failed', 'note' => $note]);
        if ($b = $pay->booking) {
            if ($b->status === 'payment_review') {
                $free = $b->property->isAvailable($b->check_in, $b->check_out);
                $b->update(['status' => $free ? 'awaiting_payment' : 'expired', 'pay_by' => now()->addMinutes(BookingService::windowMinutes())]);
            }
            $b->guest->notify('پرداخت کارت‌به‌کارت تأیید نشد', $note ?: 'لطفاً دوباره پرداخت کنید.', route('guest.bookings.show', $b), true, event: 'card_rejected_guest', vars: ['code' => $b->code]);
        } else {
            $pay->user->notify('پرداخت تأیید نشد', $note);
        }
    }

    public static function notifyAdmins(string $title, ?string $body, string $url): void
    {
        User::notifyAdmins($title, $body, $url, true);
    }
}
