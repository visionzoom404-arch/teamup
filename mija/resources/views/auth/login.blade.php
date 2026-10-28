@extends('layouts.app')
@section('title', 'ورود')
@section('nofooter', '1')
@section('bare')
<div class="auth">
  <div class="auth-card card">
    <div class="center" style="margin-bottom:18px"><img src="{{ asset('img/mija-logo-stacked.svg') }}" width="150" alt="می‌جا"></div>
    <h2 class="center" style="font-size:22px">ورود به حساب کاربری</h2>
    @include('partials.flash')
    <form method="post" action="{{ route('login') }}">
      @csrf
      <div class="field"><label for="phone">شماره موبایل</label><input id="phone" class="input" dir="ltr" name="phone" value="{{ old('phone') }}" inputmode="numeric" placeholder="09123456789" autocomplete="username" required autofocus></div>
      <div class="field"><label for="password">رمز عبور</label><input id="password" class="input" dir="ltr" type="password" name="password" autocomplete="current-password" required></div>
      <label class="check" style="margin-bottom:16px"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
      <button class="btn btn-lg btn-block">ورود</button>
    </form>
    <p class="center small muted" style="margin:18px 0 0">حساب ندارید؟ <a href="{{ route('register') }}" style="color:var(--red-d);font-weight:700">ثبت‌نام</a> · <a href="{{ route('register', ['as' => 'host']) }}" style="color:var(--red-d);font-weight:700">میزبان شوید</a></p>
  </div>
</div>
@endsection
