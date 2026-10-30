@extends('layouts.panel')
@section('title', 'تنظیمات')
@section('content')
@php
  $tab = request('tab', 'finance');
  $tabs = ['finance' => 'مالی و زمان‌بندی', 'cash' => 'هدیه و اقساط', 'ads' => 'تبلیغات', 'sms' => 'پیامک', 'gateway' => 'درگاه و کارت‌ها', 'texts' => 'متن‌ها و تماس', 'peaks' => 'ایام پیک'];
  $num = function ($k, $l, $help = null) { echo '<div class="field"><label>'.$l.'</label><input class="input" dir="ltr" type="number" name="'.$k.'" value="'.e(setting($k)).'">'.($help ? '<div class="help">'.$help.'</div>' : '').'</div><input type="hidden" name="keys[]" value="'.$k.'">'; };
  $txt = function ($k, $l, $help = null, $dir = 'ltr') { echo '<div class="field"><label>'.$l.'</label><input class="input" dir="'.$dir.'" name="'.$k.'" value="'.e(setting($k)).'">'.($help ? '<div class="help">'.$help.'</div>' : '').'</div><input type="hidden" name="keys[]" value="'.$k.'">'; };
  $chk = function ($k, $l, $help = null) { echo '<input type="hidden" name="keys[]" value="'.$k.'"><input type="hidden" name="'.$k.'" value="0"><label class="check"><input type="checkbox" name="'.$k.'" value="1" '.((int) setting($k) ? 'checked' : '').'><div><b>'.$l.'</b>'.($help ? '<div class="xs muted" style="font-weight:500">'.$help.'</div>' : '').'</div></label>'; };
@endphp
<div class="page-h"><h1>تنظیمات سایت</h1></div>
<div class="tabs">@foreach($tabs as $k => $l)<a href="{{ route('admin.settings', ['tab' => $k]) }}" class="{{ $tab === $k ? 'on' : '' }}">{{ $l }}</a>@endforeach</div>

@if($tab === 'finance')
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="finance"><h3>کمیسیون و مهلت‌ها</h3>
  <div class="grid3">
    @php $num('commission_percent', 'کمیسیون سایت از هر رزرو (٪)', 'میزبان عدد دریافتی خود را وارد می‌کند و این درصد به قیمت نمایشی مهمان اضافه می‌شود. برای هر میزبان می‌توانید در پروفایلش مقدار اختصاصی بگذارید.') @endphp
    @php $num('host_response_minutes', 'مهلت تأیید/رد میزبان (دقیقه)') @endphp
    @php $num('payment_window_minutes', 'مهلت پرداخت مهمان (دقیقه)') @endphp
    @php $num('offer_minutes', 'مهلت پاسخ مهمان به پیشنهاد روز جایگزین (دقیقه)') @endphp
    @php $num('min_payout', 'حداقل مبلغ برداشت (تومان)') @endphp
    @php $num('payout_hours_min', 'تسویه: حداقل ساعت') @endphp
    @php $num('payout_hours_max', 'تسویه: حداکثر ساعت') @endphp
  </div><button class="btn">ذخیره</button></form>
@elseif($tab === 'cash')
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="cash"><h3><x-i3 name="p_gift" class="sm"/> هدیه‌ی نظر (اعتبار کیف پول مهمان)</h3>
  @php $chk('cashback_enabled', 'فعال باشد', 'پس از انتشار نظر مهمان، درصدی از مبلغ رزرو به‌صورت اعتبار موقت به کیف پولش اضافه می‌شود.') @endphp
  <div class="grid3 mt">@php $num('cashback_percent', 'درصد هدیه از مبلغ رزرو (٪)') @endphp @php $num('cashback_days', 'مدت اعتبار (روز)', 'پس از این مدت، اعتبار مصرف‌نشده حذف و در صندوق پنهان اقساط ذخیره می‌شود.') @endphp</div>
  <hr class="divider"><h3><x-i3 name="p_calendar" class="sm"/> رزرو اقساطی</h3>
  @php $chk('installment_enabled', 'فعال باشد', 'با رسیدن مجموعِ اعتبارهای منقضی‌شده‌ی یک مهمان به سقف زیر، رزرو اقساطی برایش باز می‌شود. مهمان و میزبان از صندوق پنهان خبر ندارند؛ به مهمان گفته می‌شود «به‌دلیل امتیاز بالا فعال شده است».') @endphp
  <div class="grid3 mt">@php $num('installment_threshold', 'سقف صندوق برای باز شدن اقساط (تومان)') @endphp @php $num('installment_parts', 'تعداد اقساط') @endphp @php $num('installment_first_pct', 'سهم قسط اول (٪)') @endphp @php $num('installment_min_days', 'حداقل روز مانده به ورود برای اقساط') @endphp</div>
  <button class="btn mt">ذخیره</button> <a class="btn btn-light mt" href="{{ route('admin.pool') }}">مشاهده‌ی صندوق پنهان</a></form>
@elseif($tab === 'ads')
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="ads"><h3>قیمت تبلیغات (تومان)</h3>
  @php $chk('ads_enabled', 'بخش تبلیغات میزبانان فعال باشد') @endphp
  <div class="grid3 mt">@php $num('ad_banner_day', 'بنری — هر روز') @endphp @php $num('ad_click_cpc', 'کلیکی — هر کلیک') @endphp @php $num('ad_click_min', 'کلیکی — حداقل بودجه') @endphp @php $num('ad_ladder', 'نردبان — هر بار (۲۴ ساعت)') @endphp @php $num('ad_sms_each', 'پیامکی — هر مخاطب') @endphp @php $num('ad_push_each', 'اعلان — هر مخاطب') @endphp</div><button class="btn mt">ذخیره</button></form>
@elseif($tab === 'sms')
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="sms"><h3><x-i3 name="p_phone" class="sm"/> پنل پیامک (ippanel)</h3>
  @include('partials.help', ['title' => 'راهنمای اتصال', 'text' => 'از پنل ippanel، «توکن دسترسی (AccessKey)» و شماره‌ی خط ارسال را بردارید و اینجا وارد کنید، سپس «ارسال فعال باشد» را بزنید. تا زمانی که خاموش یا خالی باشد، پیامک‌ها فقط در «گزارش پیامک» ثبت می‌شوند و ارسال واقعی انجام نمی‌شود. در هر پیامک لینک همان موضوع هم قرار می‌گیرد.'])
  @php $chk('sms_enabled', 'ارسال پیامک فعال باشد') @endphp
  <div class="grid2 mt">@php $txt('sms_token', 'توکن (AccessKey)') @endphp @php $txt('sms_originator', 'شماره خط ارسال', 'مثلاً +983000505') @endphp @php $txt('sms_base_url', 'آدرس API', 'پیش‌فرض: https://rest.ippanel.com/v1/messages') @endphp @php $txt('sms_provider', 'سرویس‌دهنده') @endphp</div>
  <button class="btn mt">ذخیره</button> <a class="btn btn-light mt" href="{{ route('admin.sms') }}">گزارش پیامک‌ها</a></form>
@elseif($tab === 'gateway')
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="gateway"><h3><x-i3 name="p_wallet" class="sm"/> درگاه پرداخت و کارت‌به‌کارت</h3>
  <input type="hidden" name="keys[]" value="gateway_driver">
  <div class="field"><label>درگاه آنلاین</label><select name="gateway_driver"><option value="sandbox" @selected(setting('gateway_driver') === 'sandbox')>آزمایشی (هر پرداختی موفق می‌شود — فقط برای تست)</option><option value="zibal" @selected(setting('gateway_driver') === 'zibal')>زیبال (پرداخت واقعی)</option></select></div>
  <input type="hidden" name="keys[]" value="zibal_merchants"><div class="field"><label>مرچنت‌کدهای زیبال (هر خط یک کد)</label><textarea class="input" dir="ltr" name="zibal_merchants" rows="3" placeholder="zibal">{{ setting('zibal_merchants') }}</textarea><div class="help">چند کد بنویسید تا پرداخت‌ها بین آن‌ها تقسیم شود. کد «zibal» حالت آزمایشی خود زیبال است.</div></div>
  <hr class="divider">
  @php $chk('card_to_card_enabled', 'کارت‌به‌کارت فعال باشد', 'پس از واریز مهمان، شما در «پرداخت‌ها ← کارت‌به‌کارت» تأیید می‌کنید تا رزرو قطعی شود.') @endphp
  <input type="hidden" name="keys[]" value="cards"><div class="field mt"><label>کارت‌های دریافت (هر خط: شماره کارت | نام صاحب کارت | بانک)</label><textarea class="input" dir="ltr" name="cards" rows="3" placeholder="6037997000001234 | علی رضایی | ملی">{{ setting('cards') }}</textarea><div class="help">می‌توانید چند کارت اضافه یا هر زمان تغییر دهید.</div></div>
  <button class="btn mt">ذخیره</button></form>
@elseif($tab === 'texts')
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="texts"><h3>متن‌های سایت و تماس</h3>
  <div class="grid2">@php $txt('support_phone', 'تلفن پشتیبانی') @endphp @php $txt('support_email', 'ایمیل پشتیبانی') @endphp</div>
  @php $txt('footer_about', 'متن کوتاه پایین سایت (فوتر)', null, 'rtl') @endphp
  <hr class="divider"><h3>اینماد و سئو</h3>
  <input type="hidden" name="keys[]" value="enamad_html"><div class="field"><label>کد نماد اعتماد الکترونیکی (اینماد)</label><textarea class="input" dir="ltr" name="enamad_html" rows="3" placeholder="کد HTML نماد را از پنل اینماد کپی و اینجا بچسبانید">{{ setting('enamad_html') }}</textarea><div class="help">پس از دریافت، در پایین همه‌ی صفحه‌ها نمایش داده می‌شود. (کد زرین‌پال/زیبال هم اگر نماد داشت همین‌جا اضافه شود.)</div></div>
  <div class="grid3">@php $txt('ga4_id', 'شناسه‌ی گوگل آنالیتیکس (G-XXXX)') @endphp @php $txt('google_verify', 'کد تأیید گوگل سرچ کنسول (content)') @endphp @php $txt('bing_verify', 'کد تأیید بینگ') @endphp</div>
  <button class="btn">ذخیره</button> <a class="btn btn-light" href="{{ route('admin.pages') }}">ویرایش صفحه‌ها (قوانین، درباره ما …)</a> <a class="btn btn-light" target="_blank" href="{{ url('/sitemap.xml') }}">sitemap.xml</a></form>
@else
<div class="card"><h3>ایام پیک (قیمت ویژه)</h3>
  <form method="post" action="{{ route('admin.peaks.store') }}" class="row wrap mb">@csrf<input class="input" style="max-width:200px" name="title" placeholder="عنوان (نوروز)" required><input class="input" style="max-width:150px" dir="ltr" name="from" placeholder="1406/01/01" required><input class="input" style="max-width:150px" dir="ltr" name="to" placeholder="1406/01/15" required><button class="btn btn-sm">افزودن</button></form>
  @foreach($peaks as $pk)<div class="row between" style="padding:8px 0;border-bottom:1px solid var(--line)"><div><b>{{ $pk->title }}</b> <span class="muted small">{{ jdate($pk->starts_on, 'j F Y') }} تا {{ jdate($pk->ends_on, 'j F Y') }}</span></div><form method="post" action="{{ route('admin.peaks.destroy', $pk) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-ghost"><x-icon name="trash" class="sm"/></button></form></div>@endforeach
</div>
@endif
@endsection
