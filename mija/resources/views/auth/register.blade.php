@extends('layouts.auth')
@section('title', 'ثبت‌نام')
@section('card')
@php $r = old('role', $role) === 'host' ? 'host' : 'guest'; @endphp
<h1 data-a>ساخت حساب جدید ✨</h1>
<p class="lead" data-a>کمتر از یک دقیقه طول می‌کشد.</p>
<form method="post" action="{{ route('register') }}" id="auth-form" novalidate>
  @csrf
  <div class="role-seg" data-r="{{ $r }}" id="role-seg" data-a>
    <span class="pill"></span>
    <label><input type="radio" name="role" value="guest" @checked($r === 'guest')>🧳 مهمان<small>می‌خواهم رزرو کنم</small></label>
    <label><input type="radio" name="role" value="host" @checked($r === 'host')>🔑 میزبان<small>ملک دارم</small></label>
  </div>
  <ul class="perks" id="perks" data-a>
    <li data-for="guest"><i>🏡</i>هزاران ویلا و کلبه با قیمت شفاف</li>
    <li data-for="guest"><i>🛡️</i>پرداخت امن و ضمانت بازگشت وجه</li>
    <li data-for="host" hidden><i>💰</i>ثبت رایگان؛ فقط پس از رزرو موفق کمیسیون کسر می‌شود</li>
    <li data-for="host" hidden><i>📅</i>تقویم، قیمت و رزرو فوری را خودت مدیریت کن</li>
  </ul>
  <div class="fl {{ $errors->has('name') ? 'has-err' : '' }}" data-a>
    <input id="name" name="name" autocomplete="name" placeholder=" " value="{{ old('name') }}" required>
    <label for="name">نام و نام خانوادگی</label><span class="ico"><x-icon name="user"/></span>
    @error('name')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <div class="fl {{ $errors->has('phone') ? 'has-err' : '' }}" data-a>
    <input id="phone" name="phone" dir="ltr" inputmode="numeric" autocomplete="username" placeholder=" " value="{{ old('phone') }}" data-phone required>
    <label for="phone">شماره موبایل</label><span class="ico"><x-icon name="phone"/></span>
    @error('phone')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <div class="fl {{ $errors->has('password') ? 'has-err' : '' }}" data-a>
    <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password" placeholder=" " minlength="6" required data-strength>
    <label for="password">رمز عبور (حداقل ۶ کاراکتر)</label><span class="ico"><x-icon name="lock"/></span>
    <button type="button" class="eye" data-eye aria-label="نمایش رمز"><x-icon name="eye"/></button>
    @error('password')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <div class="strength" id="strength" data-s="0" data-a><i></i><i></i><i></i><i></i><span></span></div>
  <div class="fl" data-a>
    <input id="pc" name="password_confirmation" type="password" dir="ltr" autocomplete="new-password" placeholder=" " required>
    <label for="pc">تکرار رمز عبور</label><span class="ico"><x-icon name="shield"/></span>
  </div>
  <button class="btn btn-auth btn-lg" data-a><span class="spin"></span><span class="lbl">ساخت حساب</span><x-icon name="chev-l"/></button>
</form>
<div class="divider-t" data-a>قبلاً ثبت‌نام کرده‌ای؟</div>
<div class="alt-link" data-a><a class="btn btn-light" href="{{ route('login') }}">ورود به حساب</a></div>
@endsection
