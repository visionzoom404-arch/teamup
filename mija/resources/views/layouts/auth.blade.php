<!doctype html>
<html lang="fa" dir="rtl">
<head>@include('partials.head')</head>
<body class="auth-body">
@include('partials.i3-defs')
<div class="aurora" aria-hidden="true"><i></i><i></i><i></i></div>
<div class="au">
  <div class="au-top">
    <a href="{{ route('home') }}" class="au-back"><x-icon name="arrow-r" class="sm"/> بازگشت به سایت</a>
    <span class="au-sec"><x-icon name="lock" class="sm"/> اتصال امن</span>
  </div>
  <div class="au-shell" id="auth-card">
    <section class="au-form">
      <div class="auth-logo"><img src="{{ asset('img/mija-mark.svg') }}" alt="می‌جا"><div><b>می‌جا</b><small>MIJA</small></div></div>
      @yield('card')
    </section>
    <aside class="au-show" aria-hidden="true">
      <div class="au-orb"></div>
      <img class="au-logo" src="{{ asset('img/mija-logo-stacked.svg') }}" alt="">
      <div class="au-copy"><h2>جای خوب، سفر خوب.</h2><p>ویلا، کلبه و اقامتگاه‌های تأییدشده؛ مستقیم از میزبان.</p></div>
      <div class="au-chip c1"><x-i3 name="p_bolt" class="sm"/><span><b>رزرو فوری</b><small>بدون انتظار تأیید</small></span></div>
      <div class="au-chip c2"><x-i3 name="p_shield" class="sm"/><span><b>پرداخت امن</b><small>ضمانت بازگشت وجه</small></span></div>
      <div class="au-chip c3"><x-i3 name="p_star" class="sm"/><span><b>۴٫۸ از ۵</b><small>رضایت مهمانان</small></span></div>
      <div class="au-stats"><div><b>+{{ fa(\App\Models\Property::approved()->count()) }}</b><small>اقامتگاه</small></div><div><b>{{ fa(count(config('mija.regions'))) }}</b><small>منطقه</small></div><div><b>۱۰٪</b><small>کارمزد شفاف</small></div></div>
    </aside>
  </div>
</div>
<script src="{{ asset('vendor/gsap.min.js') }}"></script>
<script src="{{ asset('js/jalali.js') }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
</body>
</html>
