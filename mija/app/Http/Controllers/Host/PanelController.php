<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payout;
use App\Models\Review;
use App\Support\BookingService;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    public function dashboard()
    {
        $u = auth()->user();
        $b = $u->hostBookings();
        $paid = fn () => $u->hostBookings()->whereIn('status', ['confirmed', 'completed']);

        // درآمد ۶ ماه اخیر (بر اساس ماه شمسی ورود)
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            [$jy, $jm] = Jalali::parts(today()->subMonthsNoOverflow($i));
            $from = Jalali::carbon($jy, $jm, 1);
            $to = Jalali::carbon($jy, $jm, Jalali::monthLength($jy, $jm));
            $months[] = ['label' => Jalali::MONTHS[$jm - 1], 'sum' => (int) $paid()->whereBetween('check_in', [$from->toDateString(), $to->toDateString()])->sum('host_payout')];
        }

        return view('host.dashboard', [
            'u' => $u,
            'pendingRequests' => $u->hostBookings()->with(['property', 'guest'])->where('status', 'pending_host')->orderBy('respond_by')->get(),
            'upcoming' => $u->hostBookings()->with(['property', 'guest'])->where('status', 'confirmed')->whereDate('check_out', '>=', today())->orderBy('check_in')->take(5)->get(),
            'months' => $months,
            'stats' => [
                'properties' => $u->properties()->count(),
                'active' => $u->properties()->where('status', 'approved')->count(),
                'bookings' => $paid()->count(),
                'income' => (int) $paid()->sum('host_payout'),
                'rating' => round((float) $u->properties()->where('reviews_count', '>', 0)->avg('rating_avg'), 1),
            ],
        ]);
    }

    public function bookings(Request $r)
    {
        $q = auth()->user()->hostBookings()->with(['property', 'guest'])->latest('id');
        $r->filled('status') && $q->where('status', $r->status);
        $r->filled('property') && $q->where('property_id', $r->property);

        return view('host.bookings', ['items' => $q->paginate(12)->withQueryString(), 'properties' => auth()->user()->properties()->get(['id', 'title'])]);
    }

    private function mine(Booking $b): Booking
    {
        abort_unless($b->host_id === auth()->id(), 404);

        return $b->load(['property', 'guest', 'messages.sender', 'review']);
    }

    public function booking(Booking $booking) { return view('host.booking', ['b' => $this->mine($booking)]); }

    public function accept(Booking $booking)
    {
        BookingService::accept($this->mine($booking));

        return back()->with('ok', 'درخواست تأیید شد. منتظر پرداخت مهمان باشید.');
    }

    public function reject(Request $r, Booking $booking)
    {
        BookingService::reject($this->mine($booking), $r->validate(['reason' => 'nullable|string|max:200'])['reason'] ?? null);

        return back()->with('ok', 'درخواست رد شد.');
    }

    public function cancel(Request $r, Booking $booking)
    {
        BookingService::cancelByHost($this->mine($booking), $r->validate(['reason' => 'nullable|string|max:200'])['reason'] ?? null);

        return back()->with('ok', 'رزرو لغو شد و وجه مهمان بازگردانده شد.');
    }

    public function reviews()
    {
        return view('host.reviews', ['items' => Review::with(['guest', 'property'])->whereIn('property_id', auth()->user()->properties()->pluck('id'))->latest('id')->paginate(10)]);
    }

    public function reply(Request $r, Review $review)
    {
        abort_unless($review->property->host_id === auth()->id(), 404);
        $review->update($r->validate(['host_reply' => 'required|string|max:800']));

        return back()->with('ok', 'پاسخ شما ثبت شد.');
    }

    /* ---------- کیف پول و برداشت ---------- */

    public function wallet()
    {
        $u = auth()->user();

        return view('host.wallet', [
            'txs' => $u->transactions()->paginate(12, ['*'], 'tx'),
            'payouts' => $u->payouts()->take(10)->get(),
            'pendingIncome' => (int) $u->hostBookings()->where('status', 'confirmed')->whereNull('settled_at')->sum('host_payout'),
            'locked' => (int) $u->payouts()->where('status', 'pending')->sum('amount'),
        ]);
    }

    public function payout(Request $r)
    {
        $u = $r->user();
        $min = (int) setting('min_payout');
        $d = $r->validate(['amount' => "required|integer|min:$min"]);
        if (! $u->sheba) {
            return back()->withErrors(['amount' => 'ابتدا شماره شبا را در پروفایل تکمیل کنید.']);
        }
        DB::transaction(function () use ($u, $d) {
            $fresh = $u->fresh();
            abort_if($fresh->wallet < $d['amount'], 422, 'موجودی کافی نیست.');
            $fresh->walletChange(-$d['amount'], 'درخواست برداشت');
            Payout::create(['host_id' => $u->id, 'amount' => $d['amount'], 'sheba' => $u->sheba, 'bank_owner' => $u->bank_owner]);
        });

        return back()->with('ok', 'درخواست برداشت ثبت شد؛ پس از بررسی به حساب شما واریز می‌شود.');
    }

    /* ---------- کد تخفیف ---------- */

    public function coupons()
    {
        return view('host.coupons', ['items' => Coupon::where('host_id', auth()->id())->latest('id')->get()]);
    }

    public function couponStore(Request $r)
    {
        $d = $r->validate([
            'code' => ['required', 'regex:/^[A-Za-z0-9]{3,20}$/', 'unique:coupons,code'],
            'type' => 'required|in:percent,fixed', 'value' => 'required|integer|min:1',
            'usage_limit' => 'nullable|integer|min:1', 'valid_days' => 'nullable|integer|min:1|max:365',
        ]);
        abort_if($d['type'] === 'percent' && $d['value'] > 90, 422, 'حداکثر تخفیف درصدی ۹۰٪ است.');
        Coupon::create(collect($d)->except('valid_days')->all() + ['host_id' => auth()->id(), 'code' => strtoupper($d['code']), 'expires_at' => ! empty($d['valid_days']) ? now()->addDays($d['valid_days']) : null]);

        return back()->with('ok', 'کد تخفیف ساخته شد.');
    }

    public function couponDestroy(Coupon $coupon)
    {
        abort_unless($coupon->host_id === auth()->id(), 404);
        $coupon->delete();

        return back();
    }
}
