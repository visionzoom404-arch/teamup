<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Support\Ads;
use App\Support\Audit;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    public function index(Request $r)
    {
        $q = Ad::with(['host', 'property'])->latest('id');
        $r->filled('status') && $q->where('status', $r->status);
        $r->filled('type') && $q->where('type', $r->type);

        return view('admin.ads', ['items' => $q->paginate(20)->withQueryString(), 'income' => (int) Ad::whereIn('status', ['active', 'finished'])->sum('spent')]);
    }

    public function approve(Ad $ad)
    {
        Ads::approve($ad);
        Audit::log('ad.approve', "#{$ad->id}");

        return back()->with('ok', in_array($ad->type, ['sms', 'push']) ? 'ارسال انجام شد.' : 'تبلیغ فعال شد.');
    }

    public function reject(Request $r, Ad $ad)
    {
        Ads::reject($ad, $r->validate(['note' => 'nullable|string|max:200'])['note'] ?? null);
        Audit::log('ad.reject', "#{$ad->id}");

        return back()->with('ok', 'تبلیغ رد و هزینه به میزبان بازگردانده شد.');
    }
}
