@extends('layouts.auth')
@section('title', 'ورود')
@section('card')
<h1 data-a>خوش آمدید.</h1>
<p class="lead" data-a>برای ادامه وارد حساب کاربری‌تان شوید.</p>
@if(session('ok'))<div class="alert ok" data-a><x-icon name="check"/>{{ session('ok') }}</div>@endif
<form method="post" action="{{ route('login') }}" id="auth-form" novalidate>
  @csrf
  <div class="fl {{ $errors->has('phone') ? 'has-err' : '' }}" data-a>
    <input id="phone" name="phone" dir="ltr" inputmode="numeric" autocomplete="username" placeholder=" " value="{{ old('phone') }}" data-phone required>
    <label for="phone">شماره موبایل</label>
    @error('phone')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <div class="fl {{ $errors->has('password') ? 'has-err' : '' }}" data-a>
    <input id="password" name="password" type="password" dir="ltr" autocomplete="current-password" placeholder=" " required>
    <label for="password">رمز عبور</label>
    <button type="button" class="eye" data-eye aria-label="نمایش رمز"><x-icon name="eye"/></button>
    @error('password')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <label class="switch" data-a><input type="checkbox" name="remember" value="1"><span class="tr"></span>مرا به خاطر بسپار</label>
  <button class="btn btn-auth btn-lg" data-a><span class="spin"></span><span class="lbl">ورود به حساب</span><x-icon name="chev-l"/></button>
</form>
<div class="divider-t" data-a>حساب ندارید؟</div>
<div class="alt-link" data-a>
  <a class="btn btn-ghost" href="{{ route('register') }}">ثبت‌نام مهمان</a>
  <a class="btn btn-ghost" href="{{ route('register', ['as' => 'host']) }}">میزبان شو</a>
</div>
@endsection
