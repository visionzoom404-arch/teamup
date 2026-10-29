<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\BookingService;
use Illuminate\Http\Request;

/**
 * درگاه پرداخت آزمایشی. برای اتصال به درگاه واقعی (زرین‌پال، پی‌پینگ، ...)
 * کافی است show() به صفحه‌ی بانک هدایت کند و result() را با callback واقعی جایگزین کنید.
 */
class GatewayController extends Controller
{
    private function pending(string $authority): Payment
    {
        $p = Payment::where('authority', $authority)->where('user_id', auth()->id())->firstOrFail();
        abort_unless($p->status === 'pending' && config('mija.gateway') === 'sandbox', 404);

        return $p;
    }

    public function show(string $authority)
    {
        return view('gateway', ['pay' => $this->pending($authority)->load('booking.property')]);
    }

    public function result(Request $r, string $authority)
    {
        $pay = $this->pending($authority);
        $ok = $r->input('status') === 'ok';

        if (! $ok) {
            $pay->update(['status' => 'failed']);

            return redirect($pay->booking_id ? route('guest.bookings.show', $pay->booking) : route('guest.wallet'))->withErrors(['pay' => 'پرداخت ناموفق بود یا لغو شد.']);
        }

        if ($pay->purpose === 'topup') {
            $pay->update(['status' => 'paid', 'ref_id' => 'T'.random_int(100000, 999999), 'paid_at' => now()]);
            $pay->user->walletChange($pay->amount, 'شارژ کیف پول');

            return redirect()->route('guest.wallet')->with('ok', 'کیف پول شما شارژ شد.');
        }

        $b = $pay->booking;
        abort_unless($b->canPay(), 422, 'مهلت پرداخت به پایان رسیده است.');
        BookingService::markPaid($b, $pay, 'S'.random_int(100000, 999999));

        return redirect()->route('guest.bookings.show', $b)->with('ok', 'پرداخت موفق بود و رزرو شما قطعی شد')->with('celebrate', 1);
    }
}
