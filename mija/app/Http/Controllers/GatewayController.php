<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Support\BitPay;
use App\Support\Gateways;
use App\Support\Payments;
use App\Support\Zibal;
use Illuminate\Http\Request;

/**
 * درگاه پرداخت: زیبال (واقعی) یا آزمایشی (sandbox) بر اساس «پنل مدیریت ← تنظیمات ← درگاه».
 */
class GatewayController extends Controller
{
    /** شروع پرداخت آنلاین یک تراکنش (بر اساس $pay->gateway) و هدایت کاربر */
    public static function begin(Payment $pay)
    {
        try {
            if ($pay->gateway === 'zibal' && Zibal::enabled()) {
                return redirect()->away(Zibal::start($pay, route('gateway.callback'), auth()->user()?->phone));
            }
            if ($pay->gateway === 'bitpay' && BitPay::enabled()) {
                return redirect()->away(BitPay::start($pay, route('gateway.bitpay'), auth()->user()?->name));
            }
        } catch (\Throwable $e) {
            $pay->update(['status' => 'failed', 'note' => mb_substr($e->getMessage(), 0, 200)]);
            report($e);
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : 'اتصال به درگاه برقرار نشد؛ لطفاً دوباره تلاش کنید یا روش دیگری انتخاب کنید.';
            if ($e instanceof \RuntimeException) {
                User::notifyAdmins('خطا در اتصال به درگاه '.Gateways::label($pay->gateway), $msg, route('admin.settings', ['tab' => 'gateway']));
            }

            return back()->withErrors(['pay' => 'اتصال به درگاه '.Gateways::label($pay->gateway).' برقرار نشد؛ روش دیگری انتخاب کنید یا چند دقیقه بعد تلاش کنید.']);
        }
        abort_unless(Gateways::on('sandbox'), 422, 'این روش پرداخت فعلاً غیرفعال است.');

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

    /** بازگشت از بیت‌پی (POST یا GET با trans_id و id_get) */
    public function bitpay(Request $r)
    {
        $idGet = (string) ($r->input('id_get') ?? $r->query('id_get'));
        $trans = (string) ($r->input('trans_id') ?? $r->query('trans_id'));
        $pay = Payment::where('track_id', $idGet)->where('gateway', 'bitpay')->firstOrFail();
        if ($pay->status === 'paid') {
            return self::back($pay, 'پرداخت قبلاً ثبت شده است.', true);
        }
        if ($trans !== '' && $pay->status === 'pending') {
            $err = null;
            if ($ref = BitPay::verify($pay, $trans, $err)) {
                Payments::complete($pay, $ref);

                return self::back($pay, 'پرداخت موفق بود'.($pay->purpose === 'topup' ? ' و کیف پول شما شارژ شد.' : ' و رزرو شما قطعی شد.'), true);
            }
            $pay->update(['status' => 'failed', 'note' => mb_substr((string) $err, 0, 200)]);
        } else {
            $pay->update(['status' => 'failed']);
        }

        return self::back($pay, 'پرداخت ناموفق بود یا لغو شد.', false);
    }

    /* ---------- درگاه آزمایشی ---------- */

    private function pending(string $authority): Payment
    {
        $p = Payment::where('authority', $authority)->where('user_id', auth()->id())->firstOrFail();
        abort_unless($p->status === 'pending' && $p->gateway === 'sandbox' && Gateways::on('sandbox'), 404);

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
