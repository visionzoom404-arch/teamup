<!doctype html>
<html lang="fa" dir="rtl">
<head>@include('partials.head')<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}"></head>
<body class="auth-body">
@include('partials.i3-defs')
<div class="auth-wrap">
  <main class="auth-side">
    <i class="blob b1"></i><i class="blob b2"></i><i class="blob b3"></i>
    <div class="auth-box">
      <div class="auth-top">
        <a href="{{ route('home') }}" class="home"><x-icon name="chev-r" class="sm"/> بازگشت به سایت</a>
        <span class="badge gold"><x-icon name="shield" class="sm"/> اتصال امن</span>
      </div>
      <section class="auth-card" id="auth-card">
        <div class="auth-logo"><img src="{{ asset('img/mija-mark.svg') }}" alt="می‌جا"><div><div style="font-weight:800;font-size:22px;line-height:1.2">می‌جا</div><div class="xs muted" style="letter-spacing:3px;font-weight:700">MIJA</div></div></div>
        @yield('card')
      </section>
    </div>
  </main>
  <aside class="auth-scene" id="auth-scene">@include('partials.auth-scene')</aside>
</div>
<script src="{{ asset('vendor/gsap.min.js') }}"></script>
<script src="{{ asset('js/jalali.js') }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
</body>
</html>
