<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\PublicController;
use App\Models\Booking;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Property;
use App\Models\ServiceRequest;
use App\Rules\Clean;
use App\Support\BookingService;
use App\Support\Cashback;
use App\Support\Gateways;
use App\Support\Payments;
use App\Support\ReviewService;
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
            'coupon' => 'nullable|string|max:30', 'note' => ['nullable', 'string', 'max:500', new Clean],
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

        return redirect()->route('guest.bookings.show', $b)->with('ok', $property->instant_booking ? 'رزرو ثبت شد. برای قطعی شدن، پرداخت را انجام دهید.' : 'درخواست شما برای میزبان ارسال شد؛ ۳۰ دقیقه فرصت پاسخ دارد.');
    }

    private function mine(Booking $b): Booking
    {
        abort_unless($b->guest_id === auth()->id(), 404);
        BookingService::lazy($b);

        return $b->load(['property.images', 'property.host', 'review', 'messages.sender', 'installments', 'serviceRequests', 'payments']);
    }

    public function show(Booking $booking)
    {
        return view('guest.booking', ['b' => $this->mine($booking)]);
    }

    /** پرداخت رزرو: طرح (کامل/اقساطی) × روش (آنلاین/کیف پول/کارت‌به‌کارت) */
    public function pay(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        $d = $r->validate(['plan' => 'nullable|in:full,installment', 'method' => 'required|in:zibal,bitpay,sandbox,online,wallet,card']);
        $inst = null;
        if (($d['plan'] ?? 'full') === 'installment') {
            abort_unless($b->canPay() || $b->is_installment, 422);
            BookingService::startInstallments($b);
            $inst = $b->installments()->where('status', 'due')->orderBy('seq')->first();
            abort_unless($inst, 422);
        } else {
            abort_unless($b->canPay(), 422, 'مهلت پرداخت این رزرو به پایان رسیده است.');
        }

        return $this->charge($r, $b, $inst ? $inst->amount : $b->total, $d['method'], $inst);
    }

    /** پرداخت اقساط بعدی (پس از قطعی شدن رزرو) */
    public function payInstallment(Request $r, Installment $installment)
    {
        $b = $this->mine($installment->booking);
        abort_unless($b->status === 'confirmed' && $installment->status === 'due', 422);
        $d = $r->validate(['method' => 'required|in:zibal,bitpay,sandbox,online,wallet,card']);

        return $this->charge($r, $b, $installment->amount, $d['method'], $installment);
    }

    private function charge(Request $r, Booking $b, int $amount, string $method, ?Installment $inst)
    {
        $user = auth()->user()->fresh();
        $base = ['user_id' => $user->id, 'booking_id' => $b->id, 'amount' => $amount, 'installment_id' => $inst?->id, 'authority' => Str::random(32)];

        if ($method === 'online') {   // سازگاری با فرم‌های قدیمی: اولین درگاه فعال
            $method = Gateways::online()[0] ?? 'none';
        }
        if (! Gateways::on($method)) {
            return back()->withErrors(['method' => 'این روش پرداخت فعلاً غیرفعال است؛ روش دیگری انتخاب کنید.']);
        }
        if ($method === 'wallet') {
            if ($user->wallet < $amount) {
                return back()->withErrors(['method' => 'موجودی کیف پول کافی نیست.']);
            }
            $user->walletChange(-$amount, ($inst ? "پرداخت قسط {$inst->seq} رزرو " : 'پرداخت رزرو ').$b->code, $b);
            Cashback::consume($user, $amount);
            $pay = Payment::create($base + ['gateway' => 'wallet']);
            Payments::complete($pay, 'W'.$pay->id);

            return redirect()->route('guest.bookings.show', $b)->with('ok', 'پرداخت از کیف پول انجام شد.')->with('celebrate', 1);
        }

        if ($method === 'card') {
            abort_unless(Payments::cardEnabled(), 422);
            $cd = $r->validate([
                'tracking_code' => 'required|string|min:4|max:30', 'card_to' => 'required|string', 'receipt' => 'nullable|image|max:4096',
            ], ['tracking_code.required' => 'کد پیگیری یا ۴ رقم آخر کارت مبدأ را وارد کنید.']);
            $pay = Payment::create($base + [
                'gateway' => 'card', 'status' => 'review', 'tracking_code' => $cd['tracking_code'], 'card_to' => $cd['card_to'],
                'receipt' => $r->file('receipt')?->store('receipts', 'public'),
            ]);
            if (in_array($b->status, ['awaiting_payment']) && (! $inst || $inst->seq === 1)) {
                $b->update(['status' => 'payment_review', 'pay_by' => null]);
            }
            Payments::notifyAdmins('پرداخت کارت‌به‌کارت جدید — نیاز به تأیید', toman($amount).' — '.$b->code, route('admin.payments', ['tab' => 'review']));

            return redirect()->route('guest.bookings.show', $b)->with('ok', 'پرداخت شما ثبت شد؛ پس از تأیید مدیر (معمولاً چند ساعت) رزرو قطعی می‌شود.');
        }

        $pay = Payment::create($base + ['gateway' => $method]);

        return GatewayController::begin($pay);
    }

    public function cancel(Booking $booking)
    {
        $b = $this->mine($booking);
        $refund = BookingService::cancelByGuest($b);

        return redirect()->route('guest.bookings.show', $b)->with('ok', $refund > 0 ? 'رزرو لغو شد و '.toman($refund).' به کیف پول شما بازگشت.' : 'رزرو لغو شد.');
    }

    /** پذیرش یا رد پیشنهاد روز جایگزین میزبان */
    public function offer(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        BookingService::respondOffer($b, $r->boolean('accept'));

        return redirect()->route('guest.bookings.show', $b)->with('ok', $r->boolean('accept') ? 'پیشنهاد پذیرفته شد؛ برای قطعی شدن پرداخت کنید.' : 'پیشنهاد رد شد.');
    }

    /** درخواست خدمات اضافه (غذا، نوشیدنی، …) پس از قطعی شدن رزرو؛ حساب‌وکتاب با خود میزبان */
    public function request(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        abort_unless($b->detailsUnlocked(), 422, 'پس از قطعی شدن رزرو می‌توانید درخواست ثبت کنید.');
        $d = $r->validate(['category' => 'required|in:'.implode(',', array_keys(config('mija.service_categories'))), 'text' => ['required', 'string', 'min:3', 'max:400', new Clean]]);
        ServiceRequest::create($d + ['booking_id' => $b->id, 'user_id' => auth()->id()]);
        $b->host->notify('درخواست جدید از مهمان', config('mija.service_categories')[$d['category']].' — '.$d['text'], route('host.bookings.show', $b), true, event: 'service_request_host', vars: ['category' => config('mija.service_categories')[$d['category']], 'text' => $d['text']]);

        return back()->with('ok', 'درخواست شما برای میزبان ارسال شد. هزینه‌ی آن را مستقیماً با میزبان تسویه می‌کنید.');
    }

    public function review(Request $r, Booking $booking)
    {
        $b = $this->mine($booking);
        abort_unless($b->canReview(), 422);
        $d = $r->validate([
            'r_clean' => 'required|integer|between:1,5', 'r_accuracy' => 'required|integer|between:1,5',
            'r_location' => 'required|integer|between:1,5', 'r_host' => 'required|integer|between:1,5',
            'r_value' => 'required|integer|between:1,5', 'comment' => ['required', 'string', 'min:10', 'max:1500', new Clean],
            'recommend' => 'nullable|boolean', 'photos' => 'nullable|array|max:4', 'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ], ['comment.required' => 'لطفاً تجربه‌ی خود را بنویسید.', 'comment.min' => 'نظر باید حداقل ۱۰ کاراکتر باشد.']);
        $d['rating'] = (int) round(($d['r_clean'] + $d['r_accuracy'] + $d['r_location'] + $d['r_host'] + $d['r_value']) / 5);
        $d['recommend'] = $r->boolean('recommend');
        ReviewService::submit($b, collect($d)->except('photos')->all(), $r->file('photos', []));

        return back()->with('ok', 'نظر شما برای میزبان ارسال شد و پس از تأیید منتشر می‌شود؛ سپس هدیه‌ی نظر به کیف پول شما اضافه می‌شود.');
    }
}
