<!doctype html>
<html lang="fa" dir="rtl">
<head>@include('partials.head')@stack('head')</head>
<body>
@include('partials.i3-defs')
@include('partials.topbar')
<main>
  @hasSection('bare')@yield('bare')@else<div class="container" style="padding-top:20px">@include('partials.flash')</div>@yield('content')@endif
</main>
@unless(View::hasSection('nofooter'))
<footer class="footer">
  <div class="container">
    <div class="cols">
      <div><div class="row" style="gap:12px"><img src="{{ asset('img/mija-mark.svg') }}" alt="" width="52" height="52"><div style="color:#fff"><b style="font-size:22px;line-height:1.2;display:block">می‌جا</b><span style="letter-spacing:4px;font-size:11px;font-weight:700;color:var(--gold-l)">MIJA</span></div></div><p class="small" style="margin-top:10px"><b style="color:var(--gold-l)">{{ config('mija.tagline') }}.</b> از ویلای شمال تا کلبه‌ی جنگلی و بوم‌گردی کویر؛ مستقیم از میزبان، با قیمت شفاف، پرداخت امن و ضمانت بازگشت وجه.</p></div>
      <div><h4>می‌جا</h4><a href="{{ route('page', 'about') }}">درباره ما</a><a href="{{ route('page', 'rules') }}">قوانین و سیاست‌های لغو</a><a href="{{ route('page', 'contact') }}">تماس با ما</a></div>
      <div><h4>مهمانان</h4><a href="{{ route('search') }}">جستجوی اقامتگاه</a><a href="{{ route('register') }}">ثبت‌نام مهمان</a><a href="{{ route('login') }}">ورود</a></div>
      <div><h4>میزبانان</h4><a href="{{ route('page', 'become-host') }}">میزبان شوید</a><a href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه</a></div>
    </div>
    <div class="copy">© {{ fa(jdate(now(), 'Y')) }} می‌جا — تمامی حقوق محفوظ است.</div>
  </div>
</footer>
@endunless

@php $role = auth()->user()?->role; @endphp
<nav class="bottomnav" aria-label="منوی اصلی">
  <ul>
    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'on' : '' }}"><x-icon name="home"/>خانه</a></li>
    <li><a href="{{ route('search') }}" class="{{ request()->routeIs('search') ? 'on' : '' }}"><x-icon name="search"/>جستجو</a></li>
    @if($role === 'guest')
      <li><a href="{{ route('guest.bookings') }}"><x-icon name="calendar"/>سفرها</a></li>
      <li><a href="{{ route('guest.favorites') }}"><x-icon name="heart"/>نشان‌شده</a></li>
    @else
      <li><a href="{{ route('page', 'become-host') }}"><x-icon name="key"/>میزبان شوید</a></li>
    @endif
    <li><a href="{{ auth()->check() ? auth()->user()->homeRoute() : route('login') }}" class="{{ request()->routeIs('login','register') ? 'on' : '' }}"><x-icon name="user"/>{{ auth()->check() ? 'پنل من' : 'ورود' }}</a></li>
  </ul>
</nav>
@include('partials.scripts')
</body>
</html>
