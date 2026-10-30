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
            'id_photo' => 'nullable|image|max:4096',
        ], ['name.min' => 'نام و نام خانوادگی را کامل و مطابق کارت ملی بنویسید.', 'birth_date.regex' => 'تاریخ تولد را به‌صورت ۱۳۶۵/۰۴/۲۱ وارد کنید.']);
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
        \App\Models\User::notifyAdmins('مدارک هویتی میزبان منتظر بررسی است', $u->name, route('admin.users.edit', $u));
        $u->fill(['name' => $d['name'], 'national_code' => $d['national_code'], 'birth_date' => $d['birth_date'], 'id_status' => $u->id_status === 'verified' ? 'verified' : 'pending']);
        if ($r->hasFile('id_photo')) {
            $u->id_photo && Storage::disk('local')->delete($u->id_photo);
            $u->id_photo = $r->file('id_photo')->store('ids', 'local');
        }
        $u->save();

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
        $u->notify('اطلاعات شما تکمیل شد', 'اکنون می‌توانید اقامتگاه ثبت کنید.', route('host.properties.create'));

        return redirect()->route('host.properties.create')->with('ok', 'اطلاعات هویتی و بانکی شما تکمیل شد. اولین اقامتگاه خود را ثبت کنید.');
    }
}
