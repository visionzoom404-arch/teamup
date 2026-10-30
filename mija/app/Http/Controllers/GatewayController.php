<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\Payments;
use App\Support\Zibal;
use Illuminate\Http\Request;

/**
 * درگاه پرداخت: زیبال (واقعی) یا آزمایشی (sandbox) بر اساس «پنل مدیریت ← تنظیمات ← درگاه».
 */
class GatewayController extends Controller
{
    /** شروع پرداخت آنلاین یک تراکنش و هدایت کاربر */
    public static function begin(Payment $pay)
    {
        if (Zibal::enabled()) {
            try {
                return redirect()->away(Zibal::start($pay, route('gateway.callback'), auth()->user()?->phone));
            } catch (\Throwable $e) {
                $pay->update(['status' => 'failed', 'note' => mb_substr($e->getMessage(), 0, 200)]);

                return back()->withErrors(['pay' => 'اتصال به درگاه برقرار نشد؛ لطفاً دوباره تلاش کنید یا روش دیگری انتخاب کنید.']);
            }
        }

        return redirect()->route('gateway.show', $pay->authority);
    }

    private static function back(Payment $pay, string $msg, bool $ok)
    {
        $to = $pay->booking_id ? route('guest.bookings.show', $pay->booking) : route('guest.wallet');

        return $ok ? redirect($to)->with('ok', $msg)->with('celebrate', $pay->purpose !== 'topup' ? 1 : null) : redirect($to)->withErrors(['pay' => $msg]);
    }

    /** بازگشت از زیبال */
    public function callback(Request $r)
    {
        $pay = Payment::where('track_id', (string) $r->query('trackId'))->where('gateway', 'zibal')->firstOrFail();
        if ($pay->status === 'paid') {
            return self::back($pay, 'پرداخت قبلاً ثبت شده است.', true);
        }
        if ($r->query('success') == 1 && $pay->status === 'pending') {
            $ref = Zibal::verify($pay);
            if ($ref) {
                Payments::complete($pay, $ref);

                return self::back($pay, 'پرداخت موفق بود'.($pay->purpose === 'topup' ? ' و کیف پول شما شارژ شد.' : ' و رزرو شما قطعی شد.'), true);
            }
        }
        $pay->update(['status' => 'failed']);

        return self::back($pay, 'پرداخت ناموفق بود یا لغو شد.', false);
    }

    /* ---------- درگاه آزمایشی ---------- */

    private function pending(string $authority): Payment
    {
        $p = Payment::where('authority', $authority)->where('user_id', auth()->id())->firstOrFail();
        abort_unless($p->status === 'pending' && ! Zibal::enabled(), 404);

        return $p;
    }

    public function show(string $authority)
    {
        return view('gateway', ['pay' => $this->pending($authority)->load('booking.property')]);
    }

    public function result(Request $r, string $authority)
    {
        $pay = $this->pending($authority);
        if ($r->input('status') !== 'ok') {
            $pay->update(['status' => 'failed']);

            return self::back($pay, 'پرداخت ناموفق بود یا لغو شد.', false);
        }
        if ($pay->booking_id && ! $pay->installment_id) {
            abort_unless($pay->booking->canPay(), 422, 'مهلت پرداخت به پایان رسیده است.');
        }
        Payments::complete($pay, 'S'.random_int(100000, 999999));

        return self::back($pay, 'پرداخت موفق بود'.($pay->purpose === 'topup' ? ' و کیف پول شما شارژ شد.' : ' و رزرو شما قطعی شد.'), true);
    }
}
