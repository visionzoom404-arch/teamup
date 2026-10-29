@extends('layouts.auth')
@section('title', 'ثبت‌نام')
@section('card')
@php $r = old('role', $role) === 'host' ? 'host' : 'guest'; @endphp
<h1 data-a>ساخت حساب</h1>
<p class="lead" data-a>کمتر از یک دقیقه زمان می‌برد.</p>
<form method="post" action="{{ route('register') }}" id="auth-form" novalidate>
  @csrf
  <input type="hidden" name="ref" value="{{ old('ref', $ref) }}">
  @if(old('ref', $ref))<div class="alert ok" data-a><x-i3 name="p_gift" class="sm"/> با کد دعوت <b class="ltr">{{ old('ref', $ref) }}</b> ثبت‌نام می‌کنی؛ بعد از اولین رزرو هدیه می‌گیری.</div>@endif
  <div class="role-seg" data-r="{{ $r }}" id="role-seg" data-a>
    <span class="pill"></span>
    <label><input type="radio" name="role" value="guest" @checked($r === 'guest')><x-i3 name="p_suitcase"/><b>مهمان</b><small>می‌خواهم رزرو کنم</small></label>
    <label><input type="radio" name="role" value="host" @checked($r === 'host')><x-i3 name="p_key"/><b>میزبان</b><small>ملک دارم</small></label>
  </div>
  <ul class="perks" id="perks" data-a>
    <li data-for="guest"><i><x-i3 name="p_house" class="sm"/></i>هزاران ویلا و کلبه با قیمت شفاف</li>
    <li data-for="guest"><i><x-i3 name="p_shield" class="sm"/></i>پرداخت امن و ضمانت بازگشت وجه</li>
    <li data-for="host" hidden><i><x-i3 name="p_coin" class="sm"/></i>ثبت رایگان؛ فقط بعد از رزرو موفق کمیسیون کسر می‌شود</li>
    <li data-for="host" hidden><i><x-i3 name="p_calendar" class="sm"/></i>تقویم، قیمت و رزرو فوری را خودت مدیریت کن</li>
  </ul>
  <div class="fl {{ $errors->has('name') ? 'has-err' : '' }}" data-a>
    <span class="ico"><x-icon name="user"/></span>
    <input id="name" name="name" autocomplete="name" placeholder=" " value="{{ old('name') }}" required>
    <label for="name">نام و نام خانوادگی</label>
    @error('name')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <div class="fl {{ $errors->has('phone') ? 'has-err' : '' }}" data-a>
    <span class="ico"><x-icon name="phone"/></span>
    <input id="phone" name="phone" dir="ltr" inputmode="numeric" autocomplete="username" placeholder=" " value="{{ old('phone') }}" data-phone required>
    <label for="phone">شماره موبایل</label>
    @error('phone')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <div class="fl-2">
    <div class="fl {{ $errors->has('password') ? 'has-err' : '' }}" data-a>
      <span class="ico"><x-icon name="lock"/></span>
      <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password" placeholder=" " minlength="6" required data-strength>
      <label for="password">رمز عبور</label>
      <button type="button" class="eye" data-eye aria-label="نمایش رمز"><x-icon name="eye"/></button>
      @error('password')<div class="msg-e">{{ $message }}</div>@enderror
    </div>
    <div class="fl" data-a>
      <span class="ico"><x-icon name="lock"/></span>
      <input id="pc" name="password_confirmation" type="password" dir="ltr" autocomplete="new-password" placeholder=" " required>
      <label for="pc">تکرار رمز</label>
    </div>
  </div>
  <div class="strength" id="strength" data-s="0" data-a><i></i><i></i><i></i><i></i><span></span></div>
  <button class="btn btn-auth btn-lg" data-a><span class="spin"></span><span class="lbl">ساخت حساب</span><x-icon name="chev-l"/></button>
</form>
<div class="divider-t" data-a>قبلاً ثبت‌نام کرده‌ای؟</div>
<div class="alt-link" data-a><a class="btn btn-light" href="{{ route('login') }}">ورود به حساب</a></div>
@endsection
