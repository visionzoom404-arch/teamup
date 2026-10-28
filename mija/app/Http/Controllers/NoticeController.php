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
