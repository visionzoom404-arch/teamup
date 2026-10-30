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
            'banner_mode' => 'nullable|in:upload,support', 'support_text' => ['nullable', 'string', 'max:500', new Clean],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072|dimensions:min_width=900,min_height=280',
        ], [
            'image.dimensions' => 'اندازه‌ی بنر کوچک است؛ حداقل ۹۰۰×۲۸۰ پیکسل (پیشنهادی: ۱۲۰۰×۴۲۰) بارگذاری کنید.',
            'image.max' => 'حجم بنر حداکثر ۳ مگابایت باشد.', 'image.image' => 'فایل باید تصویر باشد (JPG، PNG یا WebP).', 'image.mimes' => 'فرمت بنر باید JPG، PNG یا WebP باشد.',
        ]);
        if ($d['type'] === 'banner') {
            if (($d['banner_mode'] ?? 'upload') === 'support') {
                if (trim((string) ($d['support_text'] ?? '')) === '') {
                    return back()->withInput()->withErrors(['support_text' => 'متن و توضیح بنر را بنویسید تا پشتیبانی برایتان طراحی کند.']);
                }
                $d['support_request'] = true;
            } elseif (! $r->hasFile('image')) {
                return back()->withInput()->withErrors(['image' => 'تصویر بنر را بارگذاری کنید (۱۲۰۰×۴۲۰ پیکسل) یا گزینه‌ی «سفارش طراحی توسط پشتیبانی» را بزنید.']);
            }
            $r->hasFile('image') && ($d['image'] = $r->file('image')->store('ads', 'public'));
        }
        $ad = Ads::submit($r->user(), $d);

        return redirect()->route('host.ads')->with('ok', $ad->type === 'ladder' ? 'اقامتگاه شما به بالای فهرست آمد (۲۴ ساعت).' : ($ad->support_request ? 'سفارش بنر ثبت شد؛ پشتیبانی بنر را می‌سازد و به شما اطلاع می‌دهد.' : 'تبلیغ ثبت شد و پس از تأیید مدیر فعال می‌شود.'));
    }

    public function stop(Ad $ad)
    {
        abort_unless($ad->host_id === auth()->id() && in_array($ad->status, ['pending', 'active']), 404);
        Ads::stop($ad);

        return back()->with('ok', 'تبلیغ متوقف و مبلغ باقی‌مانده به کیف پول بازگشت.');
    }
}
