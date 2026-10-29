@extends('layouts.panel')
@section('title', 'پروفایل')
@section('content')
@php $r = $u->role; @endphp
<section class="prof-hero {{ $r }}">
  <div class="ph-art" aria-hidden="true"><x-i3 name="khatam" class="xl"/><x-i3 name="khatam" class="xl"/></div>
  <div class="ph-main">
    <label class="ph-avatar" for="avatar-input" title="تغییر تصویر"><img src="{{ $u->avatarUrl() }}" alt=""><span><x-icon name="edit" class="sm"/></span></label>
    <div class="grow">
      <div class="row wrap" style="gap:8px"><h1>{{ $u->name }}</h1><span class="role-pill {{ $u->roleColor() }}">@if($r === 'guest')<x-i3 name="user" class="sm"/>@elseif($r === 'host')<x-i3 name="p_key" class="sm"/>@else<x-i3 name="p_shield" class="sm"/>@endif {{ $u->roleLabel() }}</span>
        @if($r === 'host' && $u->host_verified)<span class="badge green"><x-icon name="shield" class="sm"/> هویت تأییدشده</span>@endif
        @if($r === 'host' && $hs['superhost'])<span class="badge gold"><x-i3 name="p_crown" class="sm"/> میزبان برتر</span>@endif</div>
      <div class="ph-meta"><span><x-icon name="phone" class="sm"/> <span class="ltr">{{ fa($u->phone) }}</span></span>@if($u->city)<span><x-icon name="pin" class="sm"/> {{ $u->city }}</span>@endif<span><x-icon name="calendar" class="sm"/> عضو از {{ jdate($u->created_at, 'F Y') }}</span></div>
      @if($u->bio)<p class="ph-bio">{{ $u->bio }}</p>@endif
    </div>
    @if($r === 'host')<a class="btn btn-light btn-sm" href="{{ route('host.public', $u) }}" target="_blank"><x-icon name="eye" class="sm"/> صفحه‌ی عمومی من</a>@endif
  </div>
</section>

{{-- ===================== مهمان ===================== --}}
@if($r === 'guest')
<div class="grid2" style="gap:18px;align-items:start;margin-bottom:18px">
  <div class="card lvl" style="--lc:{{ $lv['level']['color'] }}">
    <div class="row" style="gap:16px"><div class="lvl-ic"><x-i3 name="p_trophy" class="lg"/></div><div class="grow"><div class="muted small">سطح وفاداری شما</div><h2 style="margin:0">{{ $lv['level']['name'] }}</h2></div><div class="ring-p" style="--p:{{ $lv['progress'] }}"><span>{{ fa($lv['progress']) }}٪</span></div></div>
    <div class="lvl-bar"><i style="width:{{ $lv['progress'] }}%"></i></div>
    <p class="small muted" style="margin:10px 0 0">{{ fa($lv['nights']) }} شب اقامت‌ انجام‌شده @if($lv['next']) — {{ fa($lv['next']['nights'] - $lv['nights']) }} شب تا سطح «{{ $lv['next']['name'] }}» @else — شما در بالاترین سطح هستید @endif</p>
    <div class="lvl-steps">@foreach(config('mija.levels') as $l)<div class="{{ $lv['nights'] >= $l['nights'] ? 'on' : '' }}" style="--c:{{ $l['color'] }}"><i></i><b>{{ $l['name'] }}</b><small>{{ $l['cashback'] ? fa($l['cashback']).'٪ بازگشت وجه' : 'شروع' }}</small></div>@endforeach</div>
  </div>
  <div class="tiles" style="grid-template-columns:1fr 1fr;margin:0">
    <div class="tile card"><div class="ic3"><x-i3 name="p_calendar"/></div><b>{{ fa($stats['trips']) }}</b><span>سفر قطعی</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="p_star"/></div><b>{{ fa($stats['reviews']) }}</b><span>نظر ثبت‌شده</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="p_heart"/></div><b>{{ fa($stats['favorites']) }}</b><span>علاقه‌مندی</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="p_wallet"/></div><b>{{ toman($u->wallet, false) }}</b><span>موجودی کیف پول</span></div>
  </div>
</div>
@endif

{{-- ===================== میزبان ===================== --}}
@if($r === 'host')
<div class="tiles">
  <div class="tile card"><div class="ic3"><x-i3 name="p_star"/></div><b>{{ $hs['rating'] ? fa(number_format($hs['rating'], 1)) : '—' }}</b><span>امتیاز ({{ fa($hs['reviews']) }} نظر)</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="check"/></div><b>{{ fa($hs['completed']) }}</b><span>اقامت انجام‌شده</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_chat"/></div><b>{{ $hs['response_rate'] !== null ? fa($hs['response_rate']).'٪' : '—' }}</b><span>نرخ پاسخ‌گویی</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_hourglass"/></div><b>{{ $hs['response_hours'] !== null ? fa($hs['response_hours']).' ساعت' : '—' }}</b><span>میانگین زمان پاسخ</span></div>
</div>
<div class="grid2" style="gap:18px;align-items:start;margin-bottom:18px">
  <div class="card"><div class="row" style="gap:16px;margin-bottom:10px"><div class="ring-p" style="--p:{{ $percent }}"><span>{{ fa($percent) }}٪</span></div><div><h3 style="margin:0">پیشرفت راه‌اندازی</h3><div class="small muted">با تکمیل مراحل، رزرو بیشتری می‌گیرید</div></div></div>
    @foreach($check as [$t, $done, $url])<a href="{{ $url }}" class="chk {{ $done ? 'done' : '' }}"><i>@if($done)<x-icon name="check" class="sm"/>@endif</i>{{ $t }}</a>@endforeach</div>
  <div class="card"><h3><x-i3 name="p_crown" class="sm"/> چطور «میزبان برتر» شوم؟</h3>
    <ul class="req"><li class="{{ $hs['rating'] >= 4.7 ? 'ok' : '' }}">میانگین امتیاز حداقل ۴٫۷ <small>({{ $hs['rating'] ? fa(number_format($hs['rating'], 1)) : 'هنوز نظری نیست' }})</small></li><li class="{{ $hs['completed'] >= 3 ? 'ok' : '' }}">حداقل ۳ اقامت انجام‌شده <small>({{ fa($hs['completed']) }})</small></li><li class="{{ ($hs['accept_rate'] ?? 100) >= 80 ? 'ok' : '' }}">نرخ پذیرش درخواست‌ها حداقل ۸۰٪ <small>({{ $hs['accept_rate'] !== null ? fa($hs['accept_rate']).'٪' : '—' }})</small></li></ul>
    <p class="small muted" style="margin:10px 0 0">نشان «میزبان برتر» در نتایج جستجو و صفحه‌ی اقامتگاه شما نمایش داده می‌شود و اعتماد مهمانان را بالا می‌برد.</p></div>
</div>
@endif

{{-- ===================== مدیر ===================== --}}
@if($r === 'admin')
<div class="tiles">
  <div class="tile card"><div class="ic3"><x-i3 name="family"/></div><b>{{ fa($adm['users']) }}</b><span>کل کاربران</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_house"/></div><b>{{ fa($adm['properties']) }}</b><span>اقامتگاه</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_calendar"/></div><b>{{ fa($adm['bookings']) }}</b><span>رزرو</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_bell"/></div><b>{{ fa($adm['pending_props'] + $adm['pending_payouts']) }}</b><span>نیازمند اقدام</span></div>
</div>
<div class="grid2" style="gap:18px;align-items:start;margin-bottom:18px">
  <div class="card"><h3><x-i3 name="p_lock" class="sm"/> امنیت حساب</h3>
    <div class="qrow"><span>آخرین ورود</span><b>{{ $u->last_login_at ? jdate($u->last_login_at, 'j F Y — H:i') : '—' }}</b></div>
    <div class="qrow"><span>آی‌پی آخرین ورود</span><b class="ltr">{{ $u->last_login_ip ?? '—' }}</b></div>
    <div class="qrow"><span>نقش دسترسی</span><b>مدیر کل سیستم</b></div>
    <p class="xs muted" style="margin:10px 0 0">اگر ورودی را نمی‌شناسید، فوراً رمز عبور را تغییر دهید.</p></div>
  <div class="card"><h3><x-i3 name="p_shield" class="sm"/> مدیران سایت</h3>
    @foreach($adm['admins'] as $a)<div class="row" style="padding:8px 0;border-bottom:1px solid var(--line)"><img class="avatar sm" src="{{ $a->avatarUrl() }}" alt=""><div class="grow"><b>{{ $a->name }}</b><div class="xs muted ltr">{{ fa($a->phone) }}</div></div>@if($a->id === $u->id)<span class="badge dark">شما</span>@endif</div>@endforeach
    <div class="row wrap mt" style="gap:8px"><a class="btn btn-sm btn-light" href="{{ route('admin.settings') }}">تنظیمات</a><a class="btn btn-sm btn-light" href="{{ route('admin.users') }}">کاربران</a></div></div>
</div>
@endif

{{-- ===================== دعوت دوستان (مهمان و میزبان) ===================== --}}
@if($r !== 'admin')
<div class="card invite mb">
  <div class="row wrap" style="gap:18px"><x-i3 name="p_gift" class="xl"/><div class="grow"><h3 style="margin:0">دعوت دوستان، هدیه‌ی نقدی</h3><p class="small" style="margin:2px 0 8px">با هر دوست که با لینک شما ثبت‌نام و اولین رزرو را انجام دهد، شما و او هر کدام <b>{{ toman(config('mija.referral_bonus')) }}</b> اعتبار می‌گیرید. ({{ fa($invited) }} دعوت موفق تاکنون)</p>
    <div class="copybox"><span class="ltr" id="inv">{{ $invite }}</span><button type="button" class="btn btn-sm" data-copy="#inv"><x-icon name="share" class="sm"/> کپی</button><a class="btn btn-sm btn-green" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode('بیا با می‌جا اقامتگاه رزرو کن و هدیه بگیر: '.$invite) }}">واتس‌اپ</a><a class="btn btn-sm btn-blue" target="_blank" rel="noopener" href="https://t.me/share/url?url={{ urlencode($invite) }}&text={{ urlencode('رزرو ویلا و کلبه با می‌جا + هدیه‌ی ثبت‌نام') }}">تلگرام</a></div></div></div>
</div>
@endif

{{-- ===================== فرم ویرایش ===================== --}}
<form class="card" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
  @csrf
  <h3>اطلاعات {{ $r === 'admin' ? 'مدیر' : ($r === 'host' ? 'میزبان' : 'مهمان') }}</h3>
  <input id="avatar-input" class="sr" type="file" name="avatar" accept="image/*" onchange="this.closest('form').querySelector('.fname').textContent=this.files[0]?.name">
  <div class="grid2">
    <div class="field"><label>نام و نام خانوادگی</label><input class="input" name="name" value="{{ old('name', $u->name) }}" required></div>
    <div class="field"><label>شماره موبایل (غیرقابل تغییر)</label><input class="input" dir="ltr" value="{{ $u->phone }}" disabled></div>
    <div class="field"><label>ایمیل</label><input class="input" dir="ltr" type="email" name="email" value="{{ old('email', $u->email) }}"></div>
    <div class="field"><label>شهر</label><input class="input" name="city" value="{{ old('city', $u->city) }}"></div>
  </div>
  <div class="field"><label>{{ $r === 'host' ? 'معرفی میزبان (در صفحه‌ی عمومی نمایش داده می‌شود)' : 'درباره من' }}</label><textarea class="input" name="bio" placeholder="{{ $r === 'host' ? 'مثلاً: ۸ سال است در شمال میزبان مسافران هستم…' : '' }}">{{ old('bio', $u->bio) }}</textarea></div>
  <div class="small muted" style="margin-bottom:12px">تصویر پروفایل: روی عکس بالای صفحه بزنید <span class="fname" style="color:var(--red-d);font-weight:700"></span></div>
  @if($r === 'host')
    <hr class="divider" style="margin:6px 0 16px"><h3><x-i3 name="p_wallet" class="sm"/> اطلاعات حساب بانکی (دریافت درآمد)</h3>
    <div class="grid2">
      <div class="field"><label>کد ملی</label><input class="input" dir="ltr" name="national_id" value="{{ old('national_id', $u->national_id) }}" maxlength="10"></div>
      <div class="field"><label>نام صاحب حساب</label><input class="input" name="bank_owner" value="{{ old('bank_owner', $u->bank_owner) }}"></div>
    </div>
    <div class="field"><label>شماره شبا</label><input class="input" dir="ltr" name="sheba" value="{{ old('sheba', $u->sheba) }}" placeholder="IR000000000000000000000000" maxlength="26"><div class="help">شبا باید به نام خودتان باشد.</div></div>
  @endif
  <button class="btn">ذخیره تغییرات</button>
</form>
<form class="card mt" method="post" action="{{ route('profile.password') }}">
  @csrf <h3><x-i3 name="p_lock" class="sm"/> تغییر رمز عبور</h3>
  <div class="grid3"><div class="field"><label>رمز فعلی</label><input class="input" dir="ltr" type="password" name="current" required></div><div class="field"><label>رمز جدید</label><input class="input" dir="ltr" type="password" name="password" required></div><div class="field"><label>تکرار رمز جدید</label><input class="input" dir="ltr" type="password" name="password_confirmation" required></div></div>
  <button class="btn btn-dark">تغییر رمز</button>
</form>
@endsection
