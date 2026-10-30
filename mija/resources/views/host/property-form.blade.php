@extends('layouts.panel')
@section('title', $p->exists ? 'ویرایش اقامتگاه' : 'ثبت اقامتگاه')
@push('head')<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">@endpush
@push('vendor')<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>@endpush
@section('content')
@php
 $pre = auth()->user()->isAdmin() ? 'admin.' : 'host.';
 $v = fn ($k, $d = null) => old($k, $p->$k ?? $d);
 $selAm = old('amenities', $p->exists ? $p->amenities->pluck('id')->all() : []);
 $groups = ['feature' => 'امکانات ویژه', 'basic' => 'امکانات پایه', 'service' => 'خدمات', 'rules' => 'قوانین و پذیرش'];
@endphp
<div class="page-h"><h1>{{ $p->exists ? 'ویرایش: '.$p->title : 'ثبت اقامتگاه جدید' }}</h1>
  @if($p->exists)<div class="row wrap"><a class="btn btn-sm btn-blue" href="{{ route($pre.'pricing', $p) }}"><x-icon name="tag" class="sm"/> سیاست نرخ‌گذاری</a>@if(! auth()->user()->isAdmin())<a class="btn btn-sm btn-blue" href="{{ route('host.calendar', $p) }}"><x-icon name="calendar" class="sm"/> تقویم</a>@if(in_array($p->status, ['draft','rejected']))<form method="post" action="{{ route('host.properties.submit', $p) }}">@csrf<button class="btn btn-sm btn-green">ارسال برای بررسی</button></form>@endif @endif</div>@endif</div>
@if($p->exists && $p->reject_reason)<div class="alert bad"><x-icon name="info"/>دلیل عدم تأیید: {{ $p->reject_reason }}</div>@endif

<div class="pf-steps" aria-hidden="true"><i class="on"></i><i></i><i></i><i></i><i></i><i></i></div>
<form id="pform" method="post" enctype="multipart/form-data" action="{{ $p->exists ? route($pre.'properties.update', $p) : route('host.properties.store') }}" class="stack">
  @csrf
  <div class="card pf-step on"><h3>۱. اطلاعات اصلی</h3>
    <div class="field"><label>عنوان آگهی</label><input class="input" name="title" value="{{ $v('title') }}" required placeholder="مثلاً: ویلای استخردار با ویوی جنگل در نوشهر"></div>
    <div class="grid3">
      <div class="field"><label>نوع اقامتگاه</label><select name="type">@foreach(config('mija.property_types') as $k => $n)<option value="{{ $k }}" @selected($v('type') === $k)>{{ $n }}</option>@endforeach</select></div>
      <div class="field"><label>استان</label><select name="province" id="province">@foreach(config('mija.provinces') as $pv => $cs)<option @selected($v('province') === $pv)>{{ $pv }}</option>@endforeach</select></div>
      <div class="field"><label>شهر / منطقه</label><input class="input" name="city" id="city" list="cities" value="{{ $v('city') }}" required><datalist id="cities">@foreach(config('mija.provinces') as $cs)@foreach($cs as $c)<option value="{{ $c }}">@endforeach @endforeach</datalist></div>
    </div>
    <div class="field"><label>آدرس (فقط پس از قطعی شدن رزرو نمایش داده می‌شود)</label><input class="input" name="address" value="{{ $v('address') }}"></div>
    <div class="field"><label>موقعیت دقیق روی نقشه</label>
      <div class="map-pick" data-map-pick></div>
      <div class="row wrap" style="gap:8px;margin-top:8px"><button type="button" class="btn btn-sm btn-light" id="map-me"><x-icon name="pin" class="sm"/> موقعیت فعلی من</button><span class="xs muted">روی نقشه بزنید یا نشانگر را بکشید. این موقعیت فقط پس از پرداخت مهمان دقیق نمایش داده می‌شود؛ پیش از آن، محدوده‌ای تقریبی دیده می‌شود.</span></div>
      <input type="hidden" name="lat" id="lat" value="{{ $v('lat') }}"><input type="hidden" name="lng" id="lng" value="{{ $v('lng') }}">
      @error('lat')<div class="msg-e">موقعیت را روی نقشه مشخص کنید.</div>@enderror</div>
    <div class="field"><label>توضیحات</label><textarea class="input" name="description" required minlength="30" style="min-height:150px">{{ $v('description') }}</textarea></div>
  </div>

  <div class="card pf-step"><h3>۲. ظرفیت و فضا</h3>
    <div class="grid4">
      <div class="field"><label>ظرفیت استاندارد</label><input class="input" type="number" min="1" name="capacity_base" value="{{ $v('capacity_base') }}" required></div>
      <div class="field"><label>حداکثر ظرفیت</label><input class="input" type="number" min="1" name="capacity_max" value="{{ $v('capacity_max') }}" required></div>
      <div class="field"><label>اتاق خواب</label><input class="input" type="number" min="0" name="bedrooms" value="{{ $v('bedrooms') }}" required></div>
      <div class="field"><label>تعداد تخت</label><input class="input" type="number" min="1" name="beds" value="{{ $v('beds') }}" required></div>
      <div class="field"><label>سرویس بهداشتی</label><input class="input" type="number" min="0" name="bathrooms" value="{{ $v('bathrooms') }}" required></div>
    </div>
  </div>

  <div class="card pf-step"><h3>۳. قیمت‌گذاری (تومان، هر شب)</h3>
    <div class="alert info"><x-icon name="info"/><div><b>عددی که وارد می‌کنید، دریافتی خالص شماست.</b> می‌جا کمیسیون خود را به قیمتِ نمایشی مهمان اضافه می‌کند. زیر هر کادر می‌بینید مهمان چه قیمتی را می‌بیند. قیمت روزهای هفته، ماهانه و فصلی را بعد از ذخیره در «سیاست نرخ‌گذاری» تنظیم کنید.</div></div>
    <div class="grid3">
      <div class="field"><label>میان‌هفته (شنبه تا چهارشنبه)</label><input class="input" dir="ltr" type="number" name="price_weekday" value="{{ $v('price_weekday') }}" required data-money></div>
      <div class="field"><label>آخر هفته (پنجشنبه و جمعه)</label><input class="input" dir="ltr" type="number" name="price_weekend" value="{{ $v('price_weekend') }}" required data-money></div>
      <div class="field"><label>ایام پیک (نوروز و تعطیلات)</label><input class="input" dir="ltr" type="number" name="price_peak" value="{{ $v('price_peak') }}" data-money><div class="help">اختیاری؛ بازه‌های پیک را مدیر سایت تعیین می‌کند.</div></div>
      <div class="field"><label>هزینه‌ی هر نفر اضافه (در شب)</label><input class="input" dir="ltr" type="number" name="extra_guest_fee" value="{{ $v('extra_guest_fee', 0) }}" data-money></div>
      <div class="field"><label>هزینه‌ی نظافت (یک‌بار)</label><input class="input" dir="ltr" type="number" name="cleaning_fee" value="{{ $v('cleaning_fee', 0) }}" data-money></div>
      <div></div>
      <div class="field"><label>تخفیف هفتگی (٪) — ۷ شب به بالا</label><input class="input" dir="ltr" type="number" min="0" max="60" name="weekly_discount" value="{{ $v('weekly_discount', 0) }}"></div>
      <div class="field"><label>تخفیف ماهانه (٪) — ۳۰ شب به بالا</label><input class="input" dir="ltr" type="number" min="0" max="70" name="monthly_discount" value="{{ $v('monthly_discount', 0) }}"></div>
    </div>
  </div>

  <div class="card pf-step"><h3>۴. قوانین رزرو</h3>
    <div class="grid4">
      <div class="field"><label>حداقل شب</label><input class="input" type="number" min="1" name="min_nights" value="{{ $v('min_nights') }}" required></div>
      <div class="field"><label>حداکثر شب</label><input class="input" type="number" min="1" name="max_nights" value="{{ $v('max_nights') }}" required></div>
      <div class="field"><label>ساعت ورود</label><input class="input" dir="ltr" type="time" name="check_in_time" value="{{ $v('check_in_time', '14:00') }}" required></div>
      <div class="field"><label>ساعت خروج</label><input class="input" dir="ltr" type="time" name="check_out_time" value="{{ $v('check_out_time', '12:00') }}" required></div>
    </div>
    <div class="field"><label>سیاست لغو</label>
      <div class="stack">@foreach(config('mija.cancellation_policies') as $k => $pl)<label class="check" style="align-items:flex-start"><input type="radio" name="cancellation_policy" value="{{ $k }}" @checked($v('cancellation_policy') === $k)><div><b>{{ $pl['name'] }}</b><div class="xs muted" style="font-weight:500">{{ $pl['desc'] }}</div></div></label>@endforeach</div></div>
    <label class="check"><input type="checkbox" name="instant_booking" value="1" @checked($v('instant_booking'))><div><b><x-icon name="bolt" class="sm"/> رزرو فوری</b><div class="xs muted" style="font-weight:500">مهمان بدون انتظار برای تأیید شما رزرو و پرداخت می‌کند (نرخ رزرو بیشتر).</div></div></label>
    <h4 class="muted" style="margin:16px 0 8px">قوانین اقامت (مهمان پیش از رزرو می‌بیند)</h4>
    <div class="rules-edit">@foreach(config('mija.house_rules') as [$rk, $rl, $rd])@php $cur = old('house_rules.'.$rk, ($p->house_rules[$rk] ?? $rd) ? 1 : 0); @endphp<div class="rule-row"><span>{{ $rl }}</span><div class="seg2"><label><input type="radio" name="house_rules[{{ $rk }}]" value="1" @checked($cur == 1)><i class="ok">مجاز</i></label><label><input type="radio" name="house_rules[{{ $rk }}]" value="0" @checked($cur == 0)><i class="no">ممنوع</i></label></div></div>@endforeach</div>
    <p class="xs muted">رعایت قوانین جمهوری اسلامی ایران و ثبت مشخصات مسافران برای همه‌ی اقامتگاه‌ها الزامی است.</p>
    <div class="field mt"><label>راهنمای ورود (فقط پس از قطعی شدن رزرو به مهمان نمایش داده می‌شود)</label><textarea class="input" name="guide" placeholder="آدرس دقیق، مسیر، کد درب، رمز وای‌فای، نحوه تحویل کلید…">{{ $v('guide') }}</textarea></div>
    <div class="field mt"><label>قوانین خاص اقامتگاه</label><textarea class="input" name="rules" placeholder="مثلاً: استعمال دخانیات در داخل ساختمان ممنوع…">{{ $v('rules') }}</textarea></div>
  </div>

  <div class="card pf-step"><h3>۵. امکانات</h3>
    @foreach($groups as $gk => $gl)<h4 class="muted" style="margin:12px 0 8px">{{ $gl }}</h4><div class="amen-grid">@foreach($amenities->where('grp', $gk) as $a)<label class="check"><input type="checkbox" name="amenities[]" value="{{ $a->id }}" @checked(in_array($a->id, $selAm))><x-i3 :name="$a->i3()" class="sm"/> {{ $a->name }}</label>@endforeach</div>@endforeach
  </div>

  <div class="card pf-step"><h3>۶. عکس‌های واقعی اقامتگاه</h3><div class="alert info"><x-icon name="info"/>حداقل ۳ عکس واقعی که خودتان از اقامتگاه گرفته‌اید بارگذاری کنید؛ بدون عکس، اقامتگاه برای بررسی ارسال نمی‌شود.</div>
    @if($p->exists && $p->images->count())<div class="imgs" style="margin-bottom:14px">@foreach($p->images as $i => $im)<div class="im"><img src="{{ $im->url() }}" alt="">
      <button class="btn btn-sm btn-icon btn-dark x" type="submit" form="del-{{ $im->id }}" aria-label="حذف"><x-icon name="trash" class="sm"/></button>
      @if($i > 0)<button class="btn btn-sm btn-light c" type="submit" form="cov-{{ $im->id }}">عکس شاخص</button>@else<span class="badge gold c" style="justify-content:center">شاخص</span>@endif</div>@endforeach</div>@endif
    <label class="dropzone" for="imgs"><x-icon name="image" class="lg"/><div><b>انتخاب عکس‌ها</b></div><div class="xs muted">JPG, PNG, WebP — حداکثر ۶ مگابایت هر عکس</div></label>
    <input id="imgs" class="sr" type="file" name="images[]" multiple accept="image/*" data-preview="#preview"><div id="preview" class="row wrap" style="margin-top:12px"></div>
  </div>

  <div class="row between wrap pf-nav glass">
    <button type="button" class="btn btn-light pf-only-m" id="pf-prev" style="visibility:hidden"><x-icon name="chev-r" class="sm"/> قبلی</button>
    <button type="button" class="btn pf-only-m" id="pf-next">بعدی <x-icon name="chev-l" class="sm"/></button>
    <button class="btn btn-lg" id="pf-save">{{ $p->exists ? 'ذخیره تغییرات' : 'ذخیره و ادامه' }}</button>
    @if($p->exists && $p->status === 'approved')<label class="check"><input type="checkbox" name="resubmit" value="1"> ارسال مجدد برای بررسی (در صورت تغییرات مهم)</label>@endif
  </div>
</form>
@if($p->exists)
  @foreach($p->images as $im)
    <form id="del-{{ $im->id }}" method="post" action="{{ route($pre.'properties.images.destroy', [$p, $im]) }}">@csrf @method('DELETE')</form>
    <form id="cov-{{ $im->id }}" method="post" action="{{ route($pre.'properties.images.cover', [$p, $im]) }}">@csrf</form>
  @endforeach
  <form method="post" action="{{ route($pre.'properties.destroy', $p) }}" class="mt2" onsubmit="return confirm('اقامتگاه برای همیشه حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost"><x-icon name="trash" class="sm"/> حذف اقامتگاه</button></form>
@endif
@endsection
