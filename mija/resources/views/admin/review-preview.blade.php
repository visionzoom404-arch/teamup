@extends('layouts.panel')
@section('title', 'پیش‌نمایش نظرسنجی')
@section('content')
<div class="page-h"><div><a href="{{ route('admin.reviews') }}" class="small muted">← نظرات</a><h1>پیامک و صفحه‌ی نظرسنجی</h1><div class="muted">وقتی مهمان اقامتش تمام شد، این پیامک می‌رود و او به صفحه‌ی ثبت نظر می‌رسد</div></div></div>

<div class="card mb">
  <h3><x-i3 name="p_phone" class="sm"/> متن پیامک نظرسنجی</h3>
  <div class="rv-sms"><div class="rv-bub">{{ $sms }}</div><small class="muted">فرستنده: خط پیامک سایت — زمان ارسال: {{ $when }}</small></div>
  <div class="field mt"><label>لینک صفحه‌ی نظرسنجی (برای همان رزرو)</label><div class="copybox"><span class="ltr small" id="rvl">{{ $link }}</span><button type="button" class="btn btn-sm" data-copy="#rvl"><x-icon name="share" class="sm"/> کپی</button></div></div>
  <p class="xs muted" style="line-height:2">پیامک‌ها طبق تنظیمات «بدون لینک» می‌روند؛ بنابراین متن بالا لینک ندارد و مهمان صفحه را از «پنل می‌جا ← سفرهای من ← رزرو» (یا اعلان داخل برنامه که لینک مستقیم دارد) باز می‌کند. اگر در «تنظیمات ← پیامک» گزینه‌ی «لینک کوتاه» را روشن کنید، لینک بالا به‌صورت کوتاه (mija.click/s/…) به پایان پیامک اضافه می‌شود. متن پترن برای ippanel: <b dir="ltr">{{ $patternText }}</b></p>
  <a class="btn btn-sm btn-light" href="{{ route('admin.settings', ['tab' => 'sms']) }}">تنظیمات پیامک</a>
</div>

<div class="alert warn"><x-icon name="eye"/><div>پایین، همان فرمی است که مهمان می‌بیند (فقط پیش‌نمایش؛ ثبت نمی‌شود)@if($b) — رزرو {{ $b->code }}@endif.</div></div>
<div class="rv-prev">@if($b)@include('guest._review-form', ['b' => $b, 'preview' => true])@else<div class="card empty"><p>هنوز رزروی نیست که فرم بر اساس آن ساخته شود.</p></div>@endif</div>
@endsection
