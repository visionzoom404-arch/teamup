<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Rules\Clean;
use App\Support\Ads;
use Illuminate\Http\Request;

/** بخش «تبلیغات» پنل میزبان: بنری، کلیکی، نردبان، پیامکی، اعلان */
class AdController extends Controller
{
    public function index()
    {
        $u = auth()->user();

        return view('host.ads', ['ads' => $u->ads()->with('property')->get(), 'props' => $u->properties()->where('status', 'approved')->get(), 'on' => Ads::enabled()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'type' => 'required|in:banner,click,ladder,sms,push', 'property_id' => 'required|integer',
            'units' => 'nullable|integer|min:1|max:5000000', 'title' => ['nullable', 'string', 'max:80', new Clean], 'text' => ['nullable', 'string', 'max:200', new Clean],
            'audience' => 'nullable|string|max:20', 'starts_on' => 'nullable|date',
        ]);
        $ad = Ads::submit($r->user(), $d);

        return redirect()->route('host.ads')->with('ok', $ad->type === 'ladder' ? 'اقامتگاه شما به بالای فهرست آمد (۲۴ ساعت).' : 'تبلیغ ثبت شد و پس از تأیید مدیر فعال می‌شود.');
    }

    public function stop(Ad $ad)
    {
        abort_unless($ad->host_id === auth()->id() && in_array($ad->status, ['pending', 'active']), 404);
        Ads::stop($ad);

        return back()->with('ok', 'تبلیغ متوقف و مبلغ باقی‌مانده به کیف پول بازگشت.');
    }
}
