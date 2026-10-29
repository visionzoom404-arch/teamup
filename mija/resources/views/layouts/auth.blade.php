<!doctype html>
<html lang="fa" dir="rtl">
<head>@include('partials.head')</head>
<body class="auth-body">
<div class="auth-wrap">
  <main class="auth-side">
    <div class="auth-box">
      <div class="auth-top">
        <a href="{{ route('home') }}" class="home"><x-icon name="arrow-r" class="sm"/> بازگشت به سایت</a>
        <span class="muted xs"><x-icon name="lock" class="sm"/> اتصال امن</span>
      </div>
      <section class="auth-card" id="auth-card">
        <div class="auth-logo"><img src="{{ asset('img/mija-mark.svg') }}" alt="می‌جا"><div><div style="font-family:var(--font-display);font-weight:800;font-size:22px;line-height:1.2">می‌جا</div><div class="xs" style="letter-spacing:.34em;font-weight:700;color:var(--brand)">MIJA</div></div></div>
        @yield('card')
      </section>
    </div>
  </main>
  <aside class="auth-scene" id="auth-scene" data-parallax>
    @if($lg = site_image('login'))<img class="stage-photo" src="{{ $lg }}" alt="">@else @include('partials.hero-art') @endif
    <div class="scene-copy"><div class="mk">MIJA · جای خوب، سفر خوب</div><p class="q">اقامتی که فقط یک‌بار تجربه نمی‌کنی؛ هر بار دوباره می‌خواهی‌اش.</p><small>ویلا، کلبه و اقامتگاه‌های تأییدشده؛ مستقیم از میزبان.</small></div>
  </aside>
</div>
<script src="{{ asset('vendor/gsap.min.js') }}"></script>
<script src="{{ asset('js/jalali.js') }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
</body>
</html>
