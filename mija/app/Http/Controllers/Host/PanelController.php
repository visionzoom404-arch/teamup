<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payout;
use App\Models\GuestReview;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Rules\Clean;
use App\Support\ReviewService;
use Carbon\Carbon;
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

        // درصد اشغال ۳۰ روز آینده و سهم رزروها به تفکیک اقامتگاه
        $props = $u->properties()->where('status', 'approved')->get();
        $occ = [];
        $from = today();
        $to = today()->addDays(30);
        foreach ($props as $p) {
            $nights = 0;
            foreach ($p->bookings()->whereIn('status', ['confirmed', 'completed'])->where('check_out', '>', $from)->where('check_in', '<', $to)->get() as $b) {
                $nights += max(0, min($b->check_out->timestamp, $to->timestamp) - max($b->check_in->timestamp, $from->timestamp)) / 86400;
            }
            $occ[$p->id] = (int) round($nights / 30 * 100);
        }
        $share = $u->hostBookings()->whereIn('status', ['confirmed', 'completed'])->with('property:id,title')->get()->groupBy('property_id')->map(fn ($g) => ['t' => $g->first()->property->title, 'n' => $g->count()])->values();
        $check = $u->hostChecklist();

        return view('host.dashboard', [
            'occ' => $occ, 'props' => $props, 'share' => $share, 'check' => $check, 'percent' => (int) round(collect($check)->where(1, true)->count() / count($check) * 100), 'hs' => $u->hostStats(),
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
        BookingService::lazy($b);

        return $b->load(['property', 'guest', 'messages.sender', 'review']);
    }

    public function booking(Booking $booking)
    {
        $b = $this->mine($booking);
        $g = $b->guest;

        return view('host.booking', [
            'b' => $b->load('serviceRequests', 'guestReview', 'installments'),
            // پیشینه‌ی مهمان: فقط میزبان‌ها نظر میزبان‌های دیگر درباره‌ی او را می‌بینند
            'gstat' => ['trips' => $g->bookings()->whereIn('status', ['confirmed', 'completed'])->count(), 'rating' => $g->guestRating(), 'cancels' => $g->bookings()->where('status', 'cancelled_guest')->count(), 'since' => $g->created_at],
            'greviews' => $g->guestReviews()->with('host:id,name')->take(6)->get(),
            'offerOptions' => $b->status === 'pending_host' ? $this->offerOptions($b) : [],
        ]);
    }

    /** پنجره‌های آزاد هم‌طولِ رزرو برای پیشنهاد روز جایگزین */
    private function offerOptions(Booking $b): array
    {
        $out = [];
        for ($i = 1; $i <= 75 && count($out) < 8; $i++) {
            $in = today()->addDays($i);
            $to = $in->copy()->addDays($b->nights);
            if ($in->eq($b->check_in) || ! $b->property->isAvailable($in, $to)) {
                continue;
            }
            $out[] = ['value' => $in->toDateString().'|'.$to->toDateString(), 'label' => jdate($in, 'l j F').' تا '.jdate($to, 'j F')];
        }

        return $out;
    }

    public function accept(Booking $booking)
    {
        BookingService::accept($this->mine($booking));

        return back()->with('ok', 'درخواست تأیید شد؛ مهمان تا '.fa(setting('payment_window_minutes')).' دقیقه فرصت پرداخت دارد.');
    }

    /** رد درخواست؛ با تیک «پیشنهاد روز دیگر» به‌جای رد قطعی، روز جایگزین با تخفیف پیشنهاد می‌شود */
    public function reject(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        $d = $r->validate([
            'reason' => ['nullable', 'string', 'max:200', new Clean], 'offer' => 'nullable|boolean',
            'offer_window' => 'required_if:offer,1', 'offer_discount' => 'required_if:offer,1|nullable|integer|between:1,60',
        ]);
        $offer = null;
        if ($r->boolean('offer')) {
            [$in, $out] = array_pad(explode('|', (string) $d['offer_window']), 2, null);
            abort_unless($in && $out, 422);
            $offer = ['check_in' => Carbon::parse($in), 'check_out' => Carbon::parse($out), 'discount' => (int) $d['offer_discount']];
        }
        BookingService::reject($b, $d['reason'] ?? null, $offer);

        return back()->with('ok', $offer ? 'پیشنهاد روز جایگزین برای مهمان ارسال شد.' : 'درخواست رد شد.');
    }

    public function cancel(Request $r, Booking $booking)
    {
        BookingService::cancelByHost($this->mine($booking), $r->validate(['reason' => ['nullable', 'string', 'max:200', new Clean]])['reason'] ?? null);

        return back()->with('ok', 'رزرو لغو شد و وجه مهمان بازگردانده شد.');
    }

    /** امتیاز میزبان به مهمان (فقط برای میزبان‌های دیگر قابل مشاهده است) */
    public function rateGuest(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        abort_unless($b->status === 'completed' && ! $b->guestReview, 422);
        $d = $r->validate(['rating' => 'required|integer|between:1,5', 'tags' => 'nullable|array', 'tags.*' => 'string|max:30', 'comment' => ['nullable', 'string', 'max:400', new Clean]]);
        GuestReview::create($d + ['booking_id' => $b->id, 'host_id' => $b->host_id, 'guest_id' => $b->guest_id]);

        return back()->with('ok', 'امتیاز شما ثبت شد و فقط برای میزبان‌های دیگر نمایش داده می‌شود.');
    }

    /** پاسخ به درخواست خدمات مهمان */
    public function serviceReply(Request $r, ServiceRequest $req)
    {
        abort_unless($req->booking->host_id === auth()->id(), 404);
        $d = $r->validate(['status' => 'required|in:accepted,declined,done', 'host_reply' => ['nullable', 'string', 'max:300', new Clean]]);
        $req->update($d);
        $req->booking->guest->notify('پاسخ میزبان به درخواست شما', ['accepted' => 'میزبان می‌تواند تأمین کند', 'declined' => 'میزبان نمی‌تواند تأمین کند', 'done' => 'انجام شد'][$d['status']].($d['host_reply'] ? ' — '.$d['host_reply'] : ''), route('guest.bookings.show', $req->booking), true);

        return back()->with('ok', 'پاسخ شما برای مهمان ارسال شد.');
    }

    public function reviews()
    {
        $ids = auth()->user()->properties()->pluck('id');

        return view('host.reviews', [
            'pending' => Review::with(['guest', 'property'])->whereIn('property_id', $ids)->where('status', 'pending_host')->latest('id')->get(),
            'items' => Review::with(['guest', 'property'])->whereIn('property_id', $ids)->whereIn('status', ['published', 'host_rejected', 'removed'])->latest('id')->paginate(10),
        ]);
    }

    private function myReview(Review $review): Review
    {
        abort_unless($review->property->host_id === auth()->id(), 404);

        return $review;
    }

    public function reviewApprove(Request $r, Review $review)
    {
        ReviewService::hostApprove($this->myReview($review), $r->validate(['host_reply' => ['nullable', 'string', 'max:800', new Clean]])['host_reply'] ?? null);

        return back()->with('ok', 'نظر تأیید و در صفحه‌ی اقامتگاه منتشر شد.');
    }

    public function reviewReject(Request $r, Review $review)
    {
        ReviewService::hostReject($this->myReview($review), $r->validate(['reason' => ['required', 'string', 'max:200', new Clean]], ['reason.required' => 'دلیل رد را بنویسید؛ نظر برای بررسی نهایی به مدیر سایت می‌رود.'])['reason']);

        return back()->with('ok', 'نظر برای بررسی نهایی به مدیر سایت ارسال شد.');
    }

    public function reply(Request $r, Review $review)
    {
        $this->myReview($review);
        abort_unless($review->status === 'published', 422);
        $review->update($r->validate(['host_reply' => ['required', 'string', 'max:800', new Clean]]));

        return back()->with('ok', 'پاسخ شما ثبت شد.');
    }

    /** پروفایل مهمان برای میزبان (پیشینه و نظر میزبان‌های دیگر) */
    public function guest(\App\Models\User $user)
    {
        abort_unless($user->isGuest() && auth()->user()->hostBookings()->where('guest_id', $user->id)->exists(), 404);

        return view('host.guest', ['g' => $user, 'rating' => $user->guestRating(), 'reviews' => $user->guestReviews()->with('host:id,name')->get(), 'trips' => $user->bookings()->whereIn('status', ['confirmed', 'completed'])->count()]);
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
            'totalPaid' => (int) $u->payouts()->where('status', 'paid')->sum('amount'),
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
        \App\Models\User::notifyAdmins('درخواست برداشت جدید', $u->name.' — '.toman($d['amount']), route('admin.payouts'), true);

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
