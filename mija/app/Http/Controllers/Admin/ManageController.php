<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\PeakPeriod;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Property;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Support\Audit;
use App\Support\Jalali;
use App\Support\Payments;
use App\Support\ReviewService;
use Illuminate\Http\Request;

class ManageController extends Controller
{
    /* ---------- اقامتگاه‌ها ---------- */
    public function properties(Request $r)
    {
        $q = Property::with(['host', 'images'])->latest('id');
        $r->filled('status') && $q->where('status', $r->status);
        $r->filled('q') && $q->where(fn ($w) => $w->where('title', 'like', "%{$r->q}%")->orWhere('city', 'like', "%{$r->q}%"));

        return view('admin.properties', ['items' => $q->paginate(15)->withQueryString()]);
    }

    public function property(Property $property)
    {
        return view('admin.property', ['p' => $property->load(['host', 'images', 'amenities'])]);
    }

    public function propertyStatus(Request $r, Property $property)
    {
        $d = $r->validate(['status' => 'required|in:approved,rejected,suspended,pending', 'reason' => 'nullable|string|max:200']);
        $property->update(['status' => $d['status'], 'reject_reason' => $d['status'] === 'approved' ? null : ($d['reason'] ?? null)]);
        $label = ['approved' => 'تأیید شد', 'rejected' => 'نیاز به اصلاح دارد', 'suspended' => 'تعلیق شد', 'pending' => 'در انتظار بررسی'][$d['status']];
        $property->host->notify("اقامتگاه «{$property->title}» $label", $d['reason'] ?? null, route('host.properties.edit', $property));

        return back()->with('ok', 'وضعیت اقامتگاه به‌روز شد.');
    }

    public function propertyFeature(Property $property)
    {
        $property->update(['featured' => ! $property->featured]);

        return back()->with('ok', $property->featured ? 'در ویترین صفحه اول قرار گرفت.' : 'از ویترین حذف شد.');
    }

    /* ---------- کاربران ---------- */
    public function users(Request $r)
    {
        $q = User::latest('id');
        $r->filled('role') && $q->where('role', $r->role);
        $r->filled('q') && $q->where(fn ($w) => $w->where('name', 'like', "%{$r->q}%")->orWhere('phone', 'like', "%{$r->q}%"));

        return view('admin.users', ['items' => $q->paginate(20)->withQueryString()]);
    }

    public function userToggle(User $user)
    {
        abort_if($user->isAdmin(), 403);
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('ok', $user->is_active ? 'کاربر فعال شد.' : 'کاربر مسدود شد.');
    }

    public function userVerify(User $user)
    {
        abort_unless($user->isHost(), 422);
        $user->update(['host_verified' => ! $user->host_verified]);

        return back()->with('ok', $user->host_verified ? 'میزبان تأیید هویت شد.' : 'تأیید هویت لغو شد.');
    }

    /* ---------- رزروها و پرداخت‌ها ---------- */
    public function bookings(Request $r)
    {
        $q = Booking::with(['property', 'guest', 'host'])->latest('id');
        $r->filled('status') && $q->where('status', $r->status);
        $r->filled('q') && $q->where('code', 'like', '%'.strtoupper($r->q).'%');

        return view('admin.bookings', ['items' => $q->paginate(20)->withQueryString()]);
    }

    public function booking(Booking $booking)
    {
        return view('admin.booking', ['b' => $booking->load(['property', 'guest', 'host', 'messages.sender', 'payments', 'review'])]);
    }

    public function bookingUpdate(Request $r, Booking $booking)
    {
        $d = $r->validate(['status' => 'required|in:'.implode(',', array_keys(config('mija.booking_statuses'))), 'cancel_reason' => 'nullable|string|max:200', 'guest_note' => 'nullable|string|max:500']);
        $booking->update($d);
        Audit::log('booking.update', $booking->code, $d);

        return back()->with('ok', 'رزرو به‌روزرسانی شد.');
    }

    public function bookingDestroy(Booking $booking)
    {
        Audit::log('booking.delete', $booking->code);
        $booking->delete();

        return redirect()->route('admin.bookings')->with('ok', 'رزرو حذف شد.');
    }

    public function payments(Request $r)
    {
        $tab = $r->query('tab', 'all');
        $q = Payment::with(['user', 'booking'])->latest('id');
        $tab === 'review' && $q->where('status', 'review');

        return view('admin.payments', ['items' => $q->paginate(25)->withQueryString(), 'tab' => $tab, 'reviewCount' => Payment::where('status', 'review')->count(), 'cards' => Payments::cards()]);
    }

    public function cardApprove(Payment $payment)
    {
        Payments::approveCard($payment);
        Audit::log('card.approve', "#{$payment->id}");

        return back()->with('ok', 'پرداخت تأیید و رزرو قطعی شد.');
    }

    public function cardReject(Request $r, Payment $payment)
    {
        Payments::rejectCard($payment, $r->validate(['note' => 'nullable|string|max:200'])['note'] ?? null);
        Audit::log('card.reject', "#{$payment->id}");

        return back()->with('ok', 'پرداخت رد شد.');
    }

    /* ---------- تسویه‌ها ---------- */
    public function payouts()
    {
        return view('admin.payouts', ['items' => Payout::with('host')->latest('id')->paginate(20)]);
    }

    public function payoutUpdate(Request $r, Payout $payout)
    {
        abort_unless($payout->status === 'pending', 422);
        $d = $r->validate(['action' => 'required|in:paid,rejected', 'tracking_code' => 'nullable|string|max:40', 'note' => 'nullable|string|max:200', 'receipt' => 'nullable|image|max:4096']);
        if ($d['action'] === 'paid' && ! $r->hasFile('receipt') && empty($d['tracking_code'])) {
            return back()->withErrors(['receipt' => 'برای ثبت واریز، رسید را بارگذاری کنید یا کد پیگیری را وارد کنید.']);
        }
        $payout->update(['status' => $d['action'], 'tracking_code' => $d['tracking_code'] ?? null, 'note' => $d['note'] ?? null, 'processed_at' => now(), 'receipt' => $r->file('receipt')?->store('payouts', 'public')]);
        Audit::log('payout.'.$d['action'], "#{$payout->id}");
        if ($d['action'] === 'rejected') {
            $payout->host->walletChange($payout->amount, 'بازگشت درخواست برداشت ردشده');
        }
        $payout->host->notify($d['action'] === 'paid' ? 'برداشت شما واریز شد' : 'درخواست برداشت رد شد', toman($payout->amount), route('host.wallet'));

        return back()->with('ok', 'درخواست برداشت به‌روز شد.');
    }

    /* ---------- نظرات ---------- */
    public function reviews(Request $r)
    {
        $tab = $r->query('tab', 'flagged');
        $q = Review::with(['guest', 'property.host'])->latest('id');
        match ($tab) {
            'flagged' => $q->where('status', 'host_rejected'),
            'pending' => $q->where('status', 'pending_host'),
            'published' => $q->where('status', 'published'),
            'removed' => $q->where('status', 'removed'),
            default => null,
        };

        return view('admin.reviews', ['items' => $q->paginate(20)->withQueryString(), 'tab' => $tab, 'counts' => Review::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')]);
    }

    public function reviewApprove(Review $review)
    {
        ReviewService::adminApprove($review);
        Audit::log('review.approve', "#{$review->id}");

        return back()->with('ok', 'نظر منتشر شد.');
    }

    public function reviewRemove(Request $r, Review $review)
    {
        ReviewService::adminRemove($review, $r->validate(['note' => 'nullable|string|max:200'])['note'] ?? null);
        Audit::log('review.remove', "#{$review->id}");

        return back()->with('ok', 'نظر حذف شد (از دید مهمان و میزبان).');
    }

    public function reviewDestroy(Review $review)
    {
        $p = $review->property;
        $review->delete();
        $p->update(['rating_avg' => round((float) $p->reviews()->avg('rating'), 2), 'reviews_count' => $p->reviews()->count()]);
        Audit::log('review.delete', "#{$review->id}");

        return back()->with('ok', 'نظر برای همیشه حذف شد.');
    }

    /* ---------- امکانات ---------- */
    public function amenities()
    {
        return view('admin.amenities', ['items' => Amenity::orderBy('sort')->get()]);
    }

    public function amenityStore(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:60', 'icon' => 'nullable|string|max:20', 'grp' => 'required|in:feature,basic,rules,service']);
        Amenity::create($d + ['key' => 'c'.time().random_int(10, 99), 'sort' => (int) Amenity::max('sort') + 1, 'icon' => $d['icon'] ?? 'sparkle']);

        return back()->with('ok', 'امکانات اضافه شد.');
    }

    public function amenityUpdate(Request $r, Amenity $amenity)
    {
        $amenity->update($r->validate(['name' => 'required|string|max:60', 'grp' => 'required|in:feature,basic,rules,service']));

        return back()->with('ok', 'امکانات ویرایش شد.');
    }

    public function amenityDestroy(Amenity $amenity)
    {
        $amenity->delete();

        return back();
    }

    /* ---------- کد تخفیف سراسری ---------- */
    public function coupons()
    {
        return view('admin.coupons', ['items' => Coupon::with('host')->latest('id')->get()]);
    }

    public function couponStore(Request $r)
    {
        $d = $r->validate([
            'code' => ['required', 'regex:/^[A-Za-z0-9]{3,20}$/', 'unique:coupons,code'],
            'type' => 'required|in:percent,fixed', 'value' => 'required|integer|min:1', 'max_discount' => 'nullable|integer|min:1',
            'min_amount' => 'nullable|integer|min:0', 'usage_limit' => 'nullable|integer|min:1', 'valid_days' => 'nullable|integer|min:1|max:365',
        ]);
        Coupon::create(collect($d)->except('valid_days')->all() + ['code' => strtoupper($d['code']), 'min_amount' => $d['min_amount'] ?? 0, 'expires_at' => ! empty($d['valid_days']) ? now()->addDays($d['valid_days']) : null]);

        return back()->with('ok', 'کد تخفیف ساخته شد.');
    }

    public function couponDestroy(Coupon $coupon)
    {
        $coupon->delete();

        return back();
    }

    public function couponToggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back();
    }

    /* ---------- ایام پیک و تنظیمات ---------- */
    public function settings()
    {
        return view('admin.settings', ['peaks' => PeakPeriod::orderBy('starts_on')->get()]);
    }

    /** گروه‌های تنظیمات و قوانین اعتبارسنجی هر کلید */
    public const SETTINGS = [
        'commission_percent' => 'required|integer|between:0,40', 'host_response_minutes' => 'required|integer|between:5,1440', 'payment_window_minutes' => 'required|integer|between:5,1440',
        'offer_minutes' => 'required|integer|between:5,1440', 'min_payout' => 'required|integer|min:0', 'payout_hours_min' => 'required|integer|between:1,240', 'payout_hours_max' => 'required|integer|between:1,240',
        'cashback_enabled' => 'boolean', 'cashback_percent' => 'required|integer|between:0,20', 'cashback_days' => 'required|integer|between:1,365',
        'installment_enabled' => 'boolean', 'installment_threshold' => 'required|integer|min:0', 'installment_parts' => 'required|integer|between:2,6', 'installment_first_pct' => 'required|integer|between:10,90', 'installment_min_days' => 'required|integer|between:3,365',
        'ads_enabled' => 'boolean', 'ad_banner_day' => 'required|integer|min:0', 'ad_click_cpc' => 'required|integer|min:0', 'ad_click_min' => 'required|integer|min:0', 'ad_ladder' => 'required|integer|min:0', 'ad_sms_each' => 'required|integer|min:0', 'ad_push_each' => 'required|integer|min:0',
        'sms_enabled' => 'boolean', 'sms_provider' => 'nullable|string|max:20', 'sms_token' => 'nullable|string|max:300', 'sms_originator' => 'nullable|string|max:30', 'sms_base_url' => 'nullable|url|max:200',
        'gateway_driver' => 'required|in:sandbox,zibal', 'zibal_merchants' => 'nullable|string|max:1000', 'cards' => 'nullable|string|max:2000', 'card_to_card_enabled' => 'boolean',
        'support_phone' => 'nullable|string|max:40', 'support_email' => 'nullable|email|max:80', 'footer_about' => 'nullable|string|max:300',
    ];

    public function settingsUpdate(Request $r)
    {
        $group = $r->input('group');
        $keys = $r->input('keys', []);
        $rules = collect(self::SETTINGS)->only($keys)->all();
        $d = $r->validate($rules);
        foreach ($rules as $k => $rule) {
            Setting::put($k, str_contains($rule, 'boolean') ? (int) $r->boolean($k) : ($d[$k] ?? ''));
        }
        Audit::log('settings.update', $group, array_keys($rules));

        return back()->with('ok', 'تنظیمات ذخیره شد.');
    }

    public function peakStore(Request $r)
    {
        $d = $r->validate(['title' => 'required|string|max:60', 'from' => 'required|string', 'to' => 'required|string']);
        $a = \App\Http\Controllers\PublicController::parseDate($d['from']);
        $b = \App\Http\Controllers\PublicController::parseDate($d['to']);
        if (! $a || ! $b || $b->lt($a)) {
            return back()->withErrors(['from' => 'تاریخ‌ها را به‌صورت ۱۴۰۶/۰۱/۰۱ وارد کنید.']);
        }
        PeakPeriod::create(['title' => $d['title'], 'starts_on' => $a, 'ends_on' => $b]);

        return back()->with('ok', 'بازه‌ی پیک ثبت شد.');
    }

    public function peakDestroy(PeakPeriod $peak)
    {
        $peak->delete();

        return back();
    }
}
