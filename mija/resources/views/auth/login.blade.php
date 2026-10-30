@extends('layouts.auth')
@section('title', 'ورود')
@section('card')
<h1 data-a>خوش برگشتی</h1>
<p class="lead" data-a>برای ادامه وارد حساب‌ات شو.</p>
@if(session('ok'))<div class="alert ok" data-a><x-icon name="check"/>{{ session('ok') }}</div>@endif
@php $otp = session('otp_phone'); $mode = request('m') === 'sms' || $otp ? 'sms' : 'pw'; @endphp
<div class="role-seg lg-seg" data-r="{{ $mode === 'sms' ? 'host' : 'guest' }}" id="login-seg" data-a>
  <span class="pill"></span>
  <label><input type="radio" name="lm" value="pw" @checked($mode === 'pw')><x-i3 name="p_lock"/><b>رمز عبور</b></label>
  <label><input type="radio" name="lm" value="sms" @checked($mode === 'sms')><x-i3 name="p_phone"/><b>کد پیامکی</b></label>
</div>
<div id="pane-pw" @if($mode === 'sms') hidden @endif>
<form method="post" action="{{ route('login') }}" id="auth-form" novalidate>
  @csrf
  <div class="fl {{ $errors->has('phone') ? 'has-err' : '' }}" data-a>
    <span class="ico"><x-icon name="phone"/></span>
    <input id="phone" name="phone" dir="ltr" inputmode="numeric" autocomplete="username" placeholder=" " value="{{ old('phone') }}" data-phone required>
    <label for="phone">شماره موبایل</label>
    @error('phone')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <div class="fl {{ $errors->has('password') ? 'has-err' : '' }}" data-a>
    <span class="ico"><x-icon name="lock"/></span>
    <input id="password" name="password" type="password" dir="ltr" autocomplete="current-password" placeholder=" " required>
    <label for="password">رمز عبور</label>
    <button type="button" class="eye" data-eye aria-label="نمایش رمز"><x-icon name="eye"/></button>
    @error('password')<div class="msg-e">{{ $message }}</div>@enderror
  </div>
  <label class="switch" data-a><input type="checkbox" name="remember" value="1"><span class="tr"></span>مرا به خاطر بسپار</label>
  <button class="btn btn-auth btn-lg" data-a><span class="spin"></span><span class="lbl">ورود به حساب</span><x-icon name="chev-l"/></button>
</form>
</div>
<div id="pane-sms" @if($mode === 'pw') hidden @endif>
  @if($otp)
  <p class="lead" style="margin-top:0">کد ۵ رقمی که به <b class="ltr">{{ fa($otp) }}</b> پیامک شد را وارد کنید.</p>
  <form method="post" action="{{ route('login.sms.verify') }}" novalidate id="otp-form">@csrf
    <input type="hidden" name="phone" value="{{ $otp }}">
    <div class="fl {{ $errors->has('code') ? 'has-err' : '' }}" data-a>
      <span class="ico"><x-icon name="shield"/></span>
      <input id="code" name="code" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="5" placeholder=" " required autofocus style="letter-spacing:.5em;font-size:20px;text-align:center">
      <label for="code">کد تأیید</label>
      @error('code')<div class="msg-e">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-auth btn-lg" data-a><span class="spin"></span><span class="lbl">تأیید و ورود</span><x-icon name="chev-l"/></button>
  </form>
  <div class="otp-meta"><span id="otp-t" data-s="60">ارسال دوباره تا ۶۰ ثانیه دیگر</span>
    <form method="post" action="{{ route('login.sms') }}" id="otp-resend" hidden>@csrf<input type="hidden" name="phone" value="{{ $otp }}"><button class="linkbtn">ارسال دوباره‌ی کد</button></form>
    <a href="{{ route('login', ['m' => 'sms']) }}" class="linkbtn">تغییر شماره</a></div>
  @else
  <form method="post" action="{{ route('login.sms') }}" novalidate id="otp-req">@csrf
    <div class="fl {{ $errors->has('phone') ? 'has-err' : '' }}" data-a>
      <span class="ico"><x-icon name="phone"/></span>
      <input id="phone2" name="phone" dir="ltr" inputmode="numeric" autocomplete="tel" placeholder=" " value="{{ old('phone') }}" data-phone required>
      <label for="phone2">شماره موبایل</label>
      @error('phone')<div class="msg-e">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-auth btn-lg" data-a><span class="spin"></span><span class="lbl">دریافت کد ورود</span><x-icon name="chev-l"/></button>
    <p class="xs muted" style="margin:10px 0 0">بدون نیاز به رمز عبور؛ کد ۵ رقمی برایتان پیامک می‌شود.</p>
  </form>
  @endif
</div>
<script>
(function(){var seg=document.getElementById('login-seg'),pw=document.getElementById('pane-pw'),sm=document.getElementById('pane-sms');
seg.querySelectorAll('input').forEach(function(i){i.addEventListener('change',function(){var s=i.value==='sms';pw.hidden=s;sm.hidden=!s;seg.setAttribute('data-r',s?'host':'guest')})});
var t=document.getElementById('otp-t');if(t){var n=60,f=document.getElementById('otp-resend'),fa=function(x){return String(x).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})};
var iv=setInterval(function(){n--;if(n<=0){clearInterval(iv);t.hidden=true;f.hidden=false}else t.textContent='ارسال دوباره تا '+fa(n)+' ثانیه دیگر'},1000)}})();
</script>
<div class="divider-t" data-a>حساب نداری؟</div>
<div class="alt-link" data-a>
  <a class="btn btn-light" href="{{ route('register') }}"><x-i3 name="p_suitcase" class="sm"/>ثبت‌نام مهمان</a>
  <a class="btn btn-light" href="{{ route('register', ['as' => 'host']) }}"><x-i3 name="p_key" class="sm"/>میزبان شو</a>
</div>
@endsection
