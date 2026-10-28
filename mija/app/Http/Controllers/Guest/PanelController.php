<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PanelController extends Controller
{
    public function dashboard()
    {
        $u = auth()->user();
        $bookings = $u->bookings()->with('property.images');

        return view('guest.dashboard', [
            'next' => (clone $bookings)->where('status', 'confirmed')->whereDate('check_out', '>=', today())->orderBy('check_in')->first(),
            'pending' => (clone $bookings)->whereIn('status', ['pending_host', 'awaiting_payment'])->latest('id')->get(),
            'stats' => [
                'total' => $u->bookings()->count(),
                'done' => $u->bookings()->where('status', 'completed')->count(),
                'fav' => $u->favorites()->count(),
                'nights' => (int) $u->bookings()->whereIn('status', ['completed', 'confirmed'])->sum('nights'),
            ],
            'recommended' => \App\Models\Property::approved()->with('images')->orderByDesc('featured')->orderByDesc('rating_avg')->take(4)->get(),
        ]);
    }

    public function bookings(Request $r)
    {
        $tab = $r->query('tab', 'all');
        $q = auth()->user()->bookings()->with('property.images')->latest('id');
        match ($tab) {
            'upcoming' => $q->whereIn('status', ['pending_host', 'awaiting_payment', 'confirmed'])->whereDate('check_out', '>=', today()),
            'done' => $q->where('status', 'completed'),
            'cancelled' => $q->whereIn('status', ['rejected', 'expired', 'cancelled_guest', 'cancelled_host']),
            default => null,
        };

        return view('guest.bookings', ['items' => $q->paginate(10)->withQueryString(), 'tab' => $tab]);
    }

    public function favorites()
    {
        return view('guest.favorites', ['items' => auth()->user()->favorites()->with('images')->latest('favorites.created_at')->get()]);
    }

    public function wallet()
    {
        return view('guest.wallet', ['txs' => auth()->user()->transactions()->paginate(15)]);
    }

    public function topup(Request $r)
    {
        $d = $r->validate(['amount' => 'required|integer|min:10000|max:100000000']);
        $pay = Payment::create(['user_id' => auth()->id(), 'purpose' => 'topup', 'amount' => $d['amount'], 'gateway' => config('mija.gateway'), 'authority' => Str::random(32)]);

        return redirect()->route('gateway.show', $pay->authority);
    }
}
