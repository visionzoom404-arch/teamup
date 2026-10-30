<!doctype html>
<html lang="fa" dir="rtl">
<head>@include('partials.head')@stack('head')</head>
<body class="@yield('bodyclass')">
@include('partials.impersonate')
@include('partials.i3-defs')
@include('partials.topbar')
<main>
  @hasSection('bare')@yield('bare')@else<div class="container" style="padding-top:20px">@include('partials.flash')</div>@yield('content')@endif
</main>
@unless(View::hasSection('nofooter'))
<footer class="footer2">
  <div class="container">
    <div class="f2-grid">
      <div class="f2-brand">
        <img class="foot-logo" src="{{ asset('img/mija-logo-h.png') }}" alt="می‌جا" width="103" height="42">
        <p>{{ setting('footer_about') }}</p>
        <div class="f2-contact">@if(setting('support_phone'))<a href="tel:{{ setting('support_phone') }}"><x-icon name="phone" class="sm"/> <span class="ltr">{{ fa(setting('support_phone')) }}</span></a>@endif @if(setting('support_email'))<a href="mailto:{{ setting('support_email') }}"><x-icon name="mail" class="sm"/> <span class="ltr">{{ setting('support_email') }}</span></a>@endif</div>
      </div>
      <div class="f2-links">
        <div><h4>مهمانان</h4><a href="{{ route('search') }}">جستجوی اقامتگاه</a><a href="{{ route('register') }}">ثبت‌نام مهمان</a><a href="{{ route('page', 'rules-guest') }}">قوانین مهمانان</a><a href="{{ route('help') }}">راهنما</a></div>
        <div><h4>میزبانان</h4><a href="{{ route('page', 'become-host') }}">میزبان شوید</a><a href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه</a><a href="{{ route('page', 'rules-host') }}">قوانین میزبانان</a><a href="{{ route('page', 'faq') }}">پرسش‌های متداول</a></div>
        <div><h4>می‌جا</h4><a href="{{ route('page', 'about') }}">درباره ما</a><a href="{{ route('page', 'rules') }}">قوانین و مقررات</a><a href="{{ route('page', 'privacy') }}">حریم خصوصی</a><a href="{{ route('page', 'contact') }}">تماس با ما</a></div>
      </div>
    </div>
    <div class="f2-copy">© {{ fa(jdate(now(), 'Y')) }} می‌جا — تمامی حقوق محفوظ است.</div>
  </div>
</footer>
@endunless

@php $role = auth()->user()?->role; @endphp
<nav class="bottomnav tabbar" aria-label="منوی اصلی">
  <ul>
    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'on' : '' }}"><x-i3 name="p_house"/><span>خانه</span></a></li>
    @if($role === 'guest')
      <li><a href="{{ route('guest.bookings') }}"><x-i3 name="p_calendar"/><span>سفرها</span></a></li>
    @else
      <li><a href="{{ route('page', 'become-host') }}"><x-i3 name="p_key"/><span>میزبان شو</span></a></li>
    @endif
    <li class="fab"><a href="{{ route('search') }}" class="{{ request()->routeIs('search') ? 'on' : '' }}" aria-label="جستجو"><span class="fab-ic"><x-i3 name="p_search"/></span><span>جستجو</span></a></li>
    @if($role)
      <li><a href="{{ route('notices') }}"><x-i3 name="p_bell"/><span>اعلان‌ها</span></a></li>
    @else
      <li><a href="{{ route('register') }}"><x-i3 name="p_gift"/><span>ثبت‌نام</span></a></li>
    @endif
    <li><a href="{{ auth()->check() ? auth()->user()->homeRoute() : route('login') }}" class="{{ request()->routeIs('login','register') ? 'on' : '' }}"><x-i3 name="user"/><span>{{ auth()->check() ? 'پنل من' : 'ورود' }}</span></a></li>
  </ul>
</nav>
<div class="sheet-back" id="sheet-back"></div>
@include('partials.scripts')
</body>
</html>
