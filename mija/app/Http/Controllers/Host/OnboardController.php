<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicController;
use App\Support\Iran;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** پذیرش میزبان: ۱) اطلاعات هویتی ۲) شماره کارت و شبا برای تسویه */
class OnboardController extends Controller
{
    public function show()
    {
        $u = auth()->user();
        $step = ! $u->national_code ? 1 : (! ($u->card_number && $u->sheba) ? 2 : 3);

        return view('host.onboard', ['u' => $u, 'step' => request('step', $step)]);
    }

    public function identity(Request $r)
    {
        $u = $r->user();
        $r->merge(['national_code' => Jalali::latinDigits((string) $r->national_code), 'birth_date' => Jalali::latinDigits((string) $r->birth_date)]);
        $d = $r->validate([
            'name' => 'required|string|min:5|max:60', 'national_code' => 'required|digits:10', 'birth_date' => ['required', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/'],
            'id_photo' => ($u->id_photo ? 'nullable' : 'required').'|image|max:6144',
            'selfie_photo' => 'nullable|image|max:6144',
            'ownership_doc' => 'nullable|mimes:jpg,jpeg,png,webp,pdf|max:8192',
        ], [
            'id_photo.required' => 'تصویر کارت ملی را بارگذاری کنید؛ بدون آن هویت شما بررسی نمی‌شود.', 'id_photo.image' => 'فایل کارت ملی باید تصویر (JPG یا PNG) باشد.', 'id_photo.max' => 'حجم تصویر کارت ملی حداکثر ۶ مگابایت باشد.',
            'ownership_doc.mimes' => 'مدرک مالکیت/اجاره باید تصویر یا PDF باشد.', 'ownership_doc.max' => 'حجم مدرک حداکثر ۸ مگابایت باشد.','name.min' => 'نام و نام خانوادگی را کامل و مطابق کارت ملی بنویسید.', 'birth_date.regex' => 'تاریخ تولد را به‌صورت ۱۳۶۵/۰۴/۲۱ وارد کنید.']);
        if (! Iran::nationalCode($d['national_code'])) {
            return back()->withInput()->withErrors(['national_code' => 'کد ملی معتبر نیست.']);
        }
        $bd = PublicController::parseDate($d['birth_date']);
        if (! $bd || $bd->gt(now()->subYears(18))) {
            return back()->withInput()->withErrors(['birth_date' => 'تاریخ تولد معتبر نیست (حداقل سن ۱۸ سال).']);
        }
        if (\App\Models\User::where('national_code', $d['national_code'])->where('id', '!=', $u->id)->exists()) {
            return back()->withInput()->withErrors(['national_code' => 'این کد ملی قبلاً برای حساب دیگری ثبت شده است.']);
        }
        $u->fill(['name' => $d['name'], 'national_code' => $d['national_code'], 'birth_date' => $d['birth_date'], 'id_status' => $u->id_status === 'verified' ? 'verified' : 'pending']);
        $new = false;
        foreach (['id_photo' => 'ids', 'selfie_photo' => 'ids', 'ownership_doc' => 'ids'] as $f => $dir) {
            if ($r->hasFile($f)) {
                $u->$f && Storage::disk('local')->delete($u->$f);
                $u->$f = $r->file($f)->store($dir, 'local');
                $new = true;
            }
        }
        if ($new) {
            $u->docs_submitted_at = now();
            $u->id_note = null;
            if ($u->id_status !== 'verified') {
                $u->id_status = 'pending';
            }
        }
        $u->save();
        if ($new && $u->id_status === 'pending') {
            \App\Models\User::notifyAdmins('مدارک هویتی میزبان رسید — منتظر بررسی', $u->name.' · '.fa($u->phone), route('admin.documents'), true);
        }

        return redirect()->route('host.onboard', ['step' => 2])->with('ok', 'اطلاعات هویتی ذخیره شد. حالا اطلاعات حساب بانکی را وارد کنید.');
    }

    public function bank(Request $r)
    {
        $u = $r->user();
        $clean = fn ($v) => strtoupper(preg_replace('/\s|-/', '', Jalali::latinDigits((string) $v)));
        $r->merge(['card_number' => $clean($r->card_number), 'sheba' => $clean($r->sheba)]);
        $d = $r->validate(['card_number' => 'required|digits:16', 'sheba' => 'required', 'bank_owner' => 'required|string|min:5|max:80']);
        if (! Iran::card($d['card_number'])) {
            return back()->withInput()->withErrors(['card_number' => 'شماره کارت معتبر نیست.']);
        }
        if (! Iran::sheba($d['sheba'])) {
            return back()->withInput()->withErrors(['sheba' => 'شماره شبا معتبر نیست (۲۴ رقم پس از IR).']);
        }
        $u->update($d + ['bank_name' => Iran::bank($d['card_number']), 'host_onboarded_at' => now()]);
        \App\Models\User::notifyAdmins('پرونده‌ی میزبان تکمیل شد (هویت و حساب بانکی)', $u->name.' · '.fa($u->phone), route('admin.documents'));
        $u->notify('اطلاعات شما تکمیل شد', 'اکنون می‌توانید اقامتگاه ثبت کنید.', route('host.properties.create'));

        return redirect()->route('host.properties.create')->with('ok', 'اطلاعات هویتی و بانکی شما تکمیل شد. اولین اقامتگاه خود را ثبت کنید.');
    }
}
