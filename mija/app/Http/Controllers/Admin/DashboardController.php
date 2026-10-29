<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Property;
use App\Models\User;
use App\Support\BookingService;
use App\Support\Jalali;

class DashboardController extends Controller
{
    public function index()
    {
        $paid = fn () => Booking::whereIn('status', ['confirmed', 'completed']);

        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $days[] = ['label' => Jalali::format($d, 'j'), 'count' => Booking::whereDate('created_at', $d)->count(), 'gmv' => (int) $paid()->whereDate('paid_at', $d)->sum('total')];
        }

        return view('admin.dashboard', [
            'kpi' => [
                'gmv' => (int) $paid()->sum('total'),
                'commission' => (int) $paid()->sum('commission'),
                'bookings' => Booking::count(),
                'users' => User::where('role', 'guest')->count(),
                'hosts' => User::where('role', 'host')->count(),
                'properties' => Property::where('status', 'approved')->count(),
            ],
            'queue' => [
                'properties' => Property::where('status', 'pending')->count(),
                'payouts' => Payout::where('status', 'pending')->count(),
                'unverified' => User::where('role', 'host')->where('host_verified', false)->count(),
            ],
            'pendingProps' => Property::with('host')->where('status', 'pending')->latest('id')->take(5)->get(),
            'recent' => Booking::with(['property', 'guest'])->latest('id')->take(8)->get(),
            'days' => $days,
            'propStatus' => Property::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function tick()
    {
        $r = BookingService::tick();

        return back()->with('ok', "اجرا شد — منقضی: {$r['expired']}، تسویه: {$r['settled']}، اتمام: {$r['completed']}");
    }
}
