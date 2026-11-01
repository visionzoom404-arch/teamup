<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GatewayController;
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
            'pending' => (clone $bookings)->whereIn('status', ['pending_host', 'awaiting_payment', 'offered', 'payment_review'])->latest('id')->get(),
            'dueInstallments' => \App\Models\Installment::whereHas('booking', fn ($b) => $b->where('guest_id', $u->id)->where('status', 'confirmed'))->where('status', 'due')->with('booking.property')->orderBy('due_on')->get(),
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
            'upcoming' => $q->whereIn('status', ['pending_host', 'awaiting_payment', 'payment_review', 'offered', 'confirmed'])->whereDate('check_out', '>=', today()),
            'done' => $q->where('status', 'completed'),
            'cancelled' => $q->whereIn('status', ['rejected', 'expired', 'cancelled_guest', 'cancelled_host', 'cancelled_system']),
            default => null,
        };

        return view('guest.bookings', ['items' => $q->paginate(10)->withQueryString(), 'tab' => $tab]);
    }

    public function favorites()
    {
        $items = auth()->user()->favorites()->with(['images', 'host'])->latest('favorites.created_at')->get();

        return view('guest.favorites', ['items' => $items, 'byCity' => $items->groupBy('city')->sortKeys()]);
    }

    public function wallet()
    {
        return view('guest.wallet', ['txs' => auth()->user()->transactions()->paginate(15), 'lots' => auth()->user()->cashbackLots()->where('status', 'active')->where('remaining', '>', 0)->get()]);
    }

    public function topup(Request $r)
    {
        $d = $r->validate(['amount' => 'required|integer|min:10000|max:100000000', 'gateway' => 'nullable|in:zibal,bitpay,sandbox']);
        $gw = $d['gateway'] ?? (\App\Support\Gateways::online()[0] ?? null);
        if (! $gw || ! \App\Support\Gateways::on($gw)) {
            return back()->withErrors(['amount' => 'درگاه پرداخت آنلاین فعلاً فعال نیست.']);
        }
        $pay = Payment::create(['user_id' => auth()->id(), 'purpose' => 'topup', 'amount' => $d['amount'], 'gateway' => $gw, 'authority' => Str::random(32)]);

        return GatewayController::begin($pay);
    }
}
