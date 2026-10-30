<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Message;
use App\Rules\Clean;
use App\Support\BookingService;
use App\Support\Payments;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index()
    {
        $u = auth()->user();
        $items = $u->notices()->paginate(20);
        $u->notices()->whereNull('read_at')->update(['read_at' => now()]);

        return view('notices', ['items' => $items]);
    }

    /** برای سایت و اپ: اعلان‌های جدیدتر از after (نظرسنجی هر چند ثانیه). مرورگر با کوکی، اپ با کوکی WebView */
    public function feed(Request $r)
    {
        $u = $r->user();
        $after = (int) $r->query('after', 0);
        $q = $u->notices()->where('id', '>', $after)->orderBy('id')->limit(20);
        $items = $q->get()->map(fn ($n) => ['id' => $n->id, 'title' => $n->title, 'body' => $n->body, 'url' => $n->url, 'kind' => $n->kind, 'at' => $n->created_at?->toIso8601String()]);

        return response()->json([
            'ok' => true, 'uid' => $u->id, 'unread' => $u->notices()->whereNull('read_at')->count(),
            'last_id' => (int) ($items->last()['id'] ?? max($after, (int) $u->notices()->max('id'))),
            'items' => $items,
        ])->header('Cache-Control', 'no-store');
    }

    private function related(Booking $b): Booking
    {
        $u = auth()->user();
        abort_unless($u->isAdmin() || in_array($u->id, [$b->guest_id, $b->host_id]), 404);

        BookingService::lazy($b);

        return $b->load(['property.host', 'guest', 'payments', 'installments']);
    }

    /** فایل تقویم (.ics) برای افزودن سفر به تقویم گوشی */
    public function ics(Booking $booking)
    {
        $b = $this->related($booking);
        abort_unless(in_array($b->status, ['confirmed', 'completed', 'awaiting_payment']), 404);
        $esc = fn ($t) => str_replace(["\\", ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $t);
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Mija//FA\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\n"
            ."UID:{$b->code}@mija\r\nDTSTAMP:".now()->utc()->format('Ymd\THis\Z')."\r\n"
            .'DTSTART;VALUE=DATE:'.$b->check_in->format('Ymd')."\r\nDTEND;VALUE=DATE:".$b->check_out->format('Ymd')."\r\n"
            .'SUMMARY:'.$esc('اقامت در '.$b->property->title)."\r\n"
            .'LOCATION:'.$esc($b->status === 'confirmed' ? ($b->property->address ?: $b->property->city) : $b->property->city)."\r\n"
            .'DESCRIPTION:'.$esc("کد رزرو {$b->code} — ورود ".$b->property->check_in_time.' — میزبان: '.$b->property->host->name)."\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        return response($ics, 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => "attachment; filename=mija-{$b->code}.ics"]);
    }

    /** رسید واریز تسویه (میزبان صاحب رسید یا مدیر) */
    public function payoutReceipt(\App\Models\Payout $payout)
    {
        $u = auth()->user();
        abort_unless($u->isAdmin() || $payout->host_id === $u->id, 404);

        return view('payout-receipt', ['po' => $payout->load('host')]);
    }

    /** فاکتور قابل چاپ */
    public function invoice(Booking $booking)
    {
        $b = $this->related($booking);
        $isGuest = auth()->id() === $b->guest_id;

        return view('invoice', ['b' => $b, 'isGuest' => $isGuest, 'cards' => Payments::cards(), 'cardOn' => Payments::cardEnabled(), 'online' => \App\Support\Zibal::enabled() ? 'زیبال' : 'درگاه پرداخت']);
    }

    /** پیام‌رسانی درون رزرو بین مهمان و میزبان */
    public function message(Request $r, Booking $booking)
    {
        $u = $r->user();
        abort_unless(in_array($u->id, [$booking->guest_id, $booking->host_id]), 404);
        $d = $r->validate(['body' => ['required', 'string', 'max:1000', new Clean]]);
        Message::create(['booking_id' => $booking->id, 'sender_id' => $u->id, 'body' => $d['body']]);
        $to = $u->id === $booking->guest_id ? $booking->host : $booking->guest;
        $to->notify('پیام جدید', "رزرو {$booking->code}", $u->id === $booking->guest_id ? route('host.bookings.show', $booking) : route('guest.bookings.show', $booking));

        return back()->with('ok', 'پیام ارسال شد.');
    }
}
