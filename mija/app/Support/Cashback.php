<?php

namespace App\Support;

use App\Models\CashbackLot;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * هدیه‌ی نظر: درصدی از مبلغ رزرو پس از انتشار نظر به کیف پول مهمان اضافه می‌شود (اعتبار موقت).
 * اعتبارِ مصرف‌نشده پس از مهلت حذف و در «صندوق پنهان» همان مهمان جمع می‌شود؛ با رسیدن به سقف، رزرو اقساطی برایش باز می‌شود.
 * مهمان و میزبان از وجود این صندوق خبر ندارند؛ فقط مدیر می‌بیند.
 */
class Cashback
{
    public static function award(Review $r): void
    {
        $b = $r->booking;
        if (! (int) setting('cashback_enabled') || $b->cashback > 0) {
            return;
        }
        $amount = intdiv($b->total * (int) setting('cashback_percent'), 100);
        if ($amount < 1000) {
            return;
        }
        $days = (int) setting('cashback_days');
        DB::transaction(function () use ($b, $amount, $days) {
            CashbackLot::create(['user_id' => $b->guest_id, 'booking_id' => $b->id, 'amount' => $amount, 'remaining' => $amount, 'expires_at' => now()->addDays($days)]);
            $b->guest->walletChange($amount, "هدیه‌ی ثبت نظر — اعتبار تا {$days} روز", $b);
            $b->update(['cashback' => $amount]);
        });
        $b->guest->notify('هدیه‌ی نظر شما به کیف پول اضافه شد', toman($amount).' — تا '.fa($days).' روز برای رزرو بعدی قابل استفاده است.', route('guest.wallet'), true, event: 'cashback_guest', vars: ['amount' => toman($amount), 'days' => fa($days)]);
    }

    /** مصرف اعتبار هدیه به‌ترتیب انقضا هنگام برداشت از کیف پول */
    public static function consume(User $u, int $amount): void
    {
        foreach (CashbackLot::where('user_id', $u->id)->where('status', 'active')->where('remaining', '>', 0)->orderBy('expires_at')->get() as $lot) {
            if ($amount <= 0) {
                break;
            }
            $take = min($lot->remaining, $amount);
            $lot->update(['remaining' => $lot->remaining - $take, 'status' => $lot->remaining - $take <= 0 ? 'used' : 'active']);
            $amount -= $take;
        }
    }

    /** حذف اعتبارهای منقضی، افزودن به صندوق پنهان و بازکردن رزرو اقساطی */
    public static function expireDue(): int
    {
        $n = 0;
        foreach (CashbackLot::where('status', 'active')->where('remaining', '>', 0)->where('expires_at', '<', now())->get() as $lot) {
            $u = $lot->user()->first();
            $take = min($lot->remaining, max(0, (int) $u->wallet));
            DB::transaction(function () use ($lot, $u, $take) {
                if ($take > 0) {
                    $u->walletChange(-$take, 'انقضای اعتبار هدیه');
                }
                $lot->update(['status' => 'expired', 'remaining' => 0]);
                $u->increment('pool_amount', $take);
            });
            $u->refresh();
            if (! $u->installment_unlocked && (int) setting('installment_enabled') && $u->pool_amount >= (int) setting('installment_threshold')) {
                $u->update(['installment_unlocked' => true]);
                $u->notify('رزرو اقساطی برای شما فعال شد', 'به‌دلیل امتیاز بالای شما در می‌جا، اکنون می‌توانید رزرو را قسطی پرداخت کنید.', route('guest.dashboard'), true);
            }
            $n++;
        }

        return $n;
    }
}
