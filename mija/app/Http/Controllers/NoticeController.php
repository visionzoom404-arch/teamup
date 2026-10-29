<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Message;
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

    private function related(Booking $b): Booking
    {
        $u = auth()->user();
        abort_unless($u->isAdmin() || in_array($u->id, [$b->guest_id, $b->host_id]), 404);

        return $b->load(['property.host', 'guest', 'payments']);
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

    /** فاکتور قابل چاپ */
    public function invoice(Booking $booking)
    {
        return view('invoice', ['b' => $this->related($booking)]);
    }

    /** پیام‌رسانی درون رزرو بین مهمان و میزبان */
    public function message(Request $r, Booking $booking)
    {
        $u = $r->user();
        abort_unless(in_array($u->id, [$booking->guest_id, $booking->host_id]), 404);
        $d = $r->validate(['body' => 'required|string|max:1000']);
        Message::create(['booking_id' => $booking->id, 'sender_id' => $u->id, 'body' => $d['body']]);
        $to = $u->id === $booking->guest_id ? $booking->host : $booking->guest;
        $to->notify('پیام جدید', "رزرو {$booking->code}", $u->id === $booking->guest_id ? route('host.bookings.show', $booking) : route('guest.bookings.show', $booking));

        return back()->with('ok', 'پیام ارسال شد.');
    }
}
