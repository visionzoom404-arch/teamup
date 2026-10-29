<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicController;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Review;
use App\Support\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function store(Request $r, Property $property)
    {
        abort_unless($property->status === 'approved', 404);
        abort_if($property->host_id === auth()->id(), 403, 'امکان رزرو اقامتگاه خودتان وجود ندارد.');
        $data = $r->validate([
            'check_in' => 'required', 'check_out' => 'required',
            'adults' => 'required|integer|min:1|max:30', 'children' => 'nullable|integer|min:0|max:20',
            'coupon' => 'nullable|string|max:30', 'note' => 'nullable|string|max:500',
        ]);
        $in = PublicController::parseDate($data['check_in']);
        $out = PublicController::parseDate($data['check_out']);
        if (! $in || ! $out) {
            return back()->withInput()->withErrors(['check_in' => 'تاریخ‌ها معتبر نیستند.']);
        }
        $quote = $property->quote($in, $out, $data['adults'] + ($data['children'] ?? 0), $data['coupon'] ?? null);
        if (! $quote['ok']) {
            return back()->withInput()->withErrors(['check_in' => $quote['error']]);
        }
        $b = BookingService::create(auth()->user(), $property, $in, $out, (int) $data['adults'], (int) ($data['children'] ?? 0), $data['coupon'] ?? null, $data['note'] ?? null);

        return redirect()->route('guest.bookings.show', $b)->with('ok', $property->instant_booking ? 'رزرو ثبت شد. برای قطعی شدن، پرداخت را انجام دهید.' : 'درخواست شما برای میزبان ارسال شد.');
    }

    private function mine(Booking $b): Booking
    {
        abort_unless($b->guest_id === auth()->id(), 404);

        return $b->load(['property.images', 'property.host', 'review', 'messages.sender']);
    }

    public function show(Booking $booking)
    {
        return view('guest.booking', ['b' => $this->mine($booking)]);
    }

    public function pay(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        abort_unless($b->canPay(), 422, 'مهلت پرداخت این رزرو به پایان رسیده است.');

        if ($r->input('method') === 'wallet') {
            $user = auth()->user()->fresh();
            if ($user->wallet < $b->total) {
                return back()->withErrors(['method' => 'موجودی کیف پول کافی نیست.']);
            }
            $user->walletChange(-$b->total, "پرداخت رزرو {$b->code}", $b);
            $pay = Payment::create(['user_id' => $user->id, 'booking_id' => $b->id, 'amount' => $b->total, 'gateway' => 'wallet', 'authority' => Str::random(24)]);
            BookingService::markPaid($b, $pay, 'W'.$pay->id);

            return redirect()->route('guest.bookings.show', $b)->with('ok', 'پرداخت از کیف پول انجام شد و رزرو قطعی شد.')->with('celebrate', 1);
        }

        $pay = Payment::create(['user_id' => auth()->id(), 'booking_id' => $b->id, 'amount' => $b->total, 'gateway' => config('mija.gateway'), 'authority' => Str::random(32)]);

        return redirect()->route('gateway.show', $pay->authority);
    }

    public function cancel(Booking $booking)
    {
        $b = $this->mine($booking);
        $refund = BookingService::cancelByGuest($b);

        return redirect()->route('guest.bookings.show', $b)->with('ok', $refund > 0 ? 'رزرو لغو شد و '.toman($refund).' به کیف پول شما بازگشت.' : 'رزرو لغو شد.');
    }

    public function review(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        abort_unless($b->canReview(), 422);
        $d = $r->validate([
            'r_clean' => 'required|integer|between:1,5', 'r_accuracy' => 'required|integer|between:1,5',
            'r_location' => 'required|integer|between:1,5', 'r_host' => 'required|integer|between:1,5',
            'r_value' => 'required|integer|between:1,5', 'comment' => 'nullable|string|max:1500',
        ]);
        $d['rating'] = (int) round(($d['r_clean'] + $d['r_accuracy'] + $d['r_location'] + $d['r_host'] + $d['r_value']) / 5);
        Review::create($d + ['booking_id' => $b->id, 'property_id' => $b->property_id, 'guest_id' => $b->guest_id]);

        $p = $b->property;
        $p->update(['rating_avg' => round($p->reviews()->avg('rating'), 2), 'reviews_count' => $p->reviews()->count()]);
        $b->host->notify('نظر جدید برای اقامتگاه شما', $p->title, route('host.reviews'));

        return back()->with('ok', 'نظر شما ثبت شد. سپاسگزاریم!');
    }
}
