@extends('layouts.app')
@section('title', 'ثبت‌نام')
@section('nofooter', '1')
@section('bare')
<div class="auth">
  <div class="auth-card card">
    <div class="center" style="margin-bottom:14px"><img src="{{ asset('img/mija-logo-stacked.svg') }}" width="130" alt="می‌جا"></div>
    <h2 class="center" style="font-size:22px">ساخت حساب کاربری</h2>
    @include('partials.flash')
    <form method="post" action="{{ route('register') }}">
      @csrf
      <div class="seg">
        <label><input type="radio" name="role" value="guest" @checked(old('role', $role) !== 'host')>🧳 مهمان هستم</label>
        <label><input type="radio" name="role" value="host" @checked(old('role', $role) === 'host')>🔑 میزبان هستم</label>
      </div>
      <div class="field"><label>نام و نام خانوادگی</label><input class="input" name="name" value="{{ old('name') }}" required></div>
      <div class="field"><label>شماره موبایل</label><input class="input" dir="ltr" name="phone" value="{{ old('phone') }}" inputmode="numeric" placeholder="09123456789" required></div>
      <div class="grid2"><div class="field"><label>رمز عبور</label><input class="input" dir="ltr" type="password" name="password" minlength="6" required></div><div class="field"><label>تکرار رمز</label><input class="input" dir="ltr" type="password" name="password_confirmation" required></div></div>
      <button class="btn btn-lg btn-block">ثبت‌نام</button>
    </form>
    <p class="center small muted" style="margin:18px 0 0">قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ route('login') }}" style="color:var(--red-d);font-weight:700">ورود</a></p>
  </div>
</div>
@endsection
