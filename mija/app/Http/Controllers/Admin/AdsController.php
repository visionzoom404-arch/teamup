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

    /** بارگذاری بنری که پشتیبانی برای میزبان ساخته (سفارش طراحی) */
    public function image(Request $r, Ad $ad)
    {
        $r->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072'], ['image.required' => 'تصویر بنر را انتخاب کنید.']);
        $ad->update(['image' => $r->file('image')->store('ads', 'public')]);
        Audit::log('ad.image', "#{$ad->id}");
        $ad->host->notify('بنر سفارشی شما آماده شد', 'پس از تأیید نهایی مدیر در صفحه‌ی اول نمایش داده می‌شود.', route('host.ads'), true);

        return back()->with('ok', 'بنر بارگذاری شد؛ حالا می‌توانید تبلیغ را تأیید کنید.');
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
