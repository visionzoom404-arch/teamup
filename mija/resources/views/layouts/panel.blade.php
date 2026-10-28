@php
$u = auth()->user();
$pending = $u->isHost() ? $u->hostBookings()->where('status', 'pending_host')->count() : ($u->isAdmin() ? \App\Models\Property::where('status', 'pending')->count() : 0);
$menus = [
 'guest' => [
  ['guest.dashboard', 'داشبورد', 'home'], ['guest.bookings', 'سفرهای من', 'calendar'], ['guest.favorites', 'علاقه‌مندی‌ها', 'heart'],
  ['guest.wallet', 'کیف پول', 'wallet'], ['notices', 'اعلان‌ها', 'bell'], ['profile', 'پروفایل', 'user'],
 ],
 'host' => [
  ['host.dashboard', 'داشبورد', 'chart'], ['host.properties.index', 'اقامتگاه‌های من', 'home'], ['host.bookings', 'رزروها', 'calendar', $pending],
  ['host.wallet', 'کیف پول و برداشت', 'wallet'], ['host.reviews', 'نظرات مهمانان', 'star'], ['host.coupons', 'کد تخفیف', 'tag'],
  ['notices', 'اعلان‌ها', 'bell'], ['profile', 'پروفایل و حساب بانکی', 'user'],
 ],
 'admin' => [
  ['admin.dashboard', 'داشبورد', 'chart'], ['admin.properties', 'اقامتگاه‌ها', 'home', $pending], ['admin.bookings', 'رزروها', 'calendar'],
  ['admin.users', 'کاربران', 'users'], ['admin.payments', 'پرداخت‌ها', 'coins'], ['admin.payouts', 'برداشت‌ها', 'wallet'],
  ['admin.reviews', 'نظرات', 'star'], ['admin.amenities', 'امکانات', 'list'], ['admin.coupons', 'کد تخفیف', 'tag'],
  ['admin.settings', 'تنظیمات و کمیسیون', 'settings'], ['profile', 'پروفایل', 'user'],
 ],
][$u->role];
$roleName = ['guest' => 'مهمان', 'host' => 'میزبان', 'admin' => 'مدیر سایت'][$u->role];
$isOn = function ($r) {
    if (in_array($r, ['host.dashboard', 'guest.dashboard', 'admin.dashboard', 'notices', 'profile'])) {
        return request()->routeIs($r);
    }
    $pat = preg_replace('/\.index$/', '', $r).'*';

    return request()->routeIs($pat) || ($r === 'host.properties.index' && request()->routeIs('host.calendar*'));
};
$quick = array_slice($menus, 0, 4);
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>@include('partials.head')@stack('head')</head>
<body>
@include('partials.topbar')
<div class="container">
  <div class="panel">
    <aside class="sidebar card">
      <div class="me">
        <img class="avatar" src="{{ $u->avatarUrl() }}" alt="">
        <div class="grow"><b>{{ $u->name }}</b><div class="xs muted">{{ $roleName }} @if($u->isHost() && $u->host_verified)<span class="badge green" style="padding:0 7px">تأییدشده</span>@endif</div></div>
      </div>
      @foreach($menus as $m)
        <a href="{{ route($m[0]) }}" class="item {{ $isOn($m[0]) ? 'on' : '' }}"><x-icon name="{{ $m[2] }}"/>{{ $m[1] }}@if(!empty($m[3]))<span class="badge red">{{ fa($m[3]) }}</span>@endif</a>
      @endforeach
      <hr class="divider" style="margin:10px 0">
      <a href="{{ route('home') }}" class="item"><x-icon name="search"/>مشاهده سایت</a>
      <form method="post" action="{{ route('logout') }}">@csrf<button class="item" style="width:100%;background:none;border:0;font:inherit;cursor:pointer;display:flex;align-items:center;gap:12px;padding:11px 14px;font-weight:600;color:var(--red-d)"><x-icon name="logout"/>خروج</button></form>
    </aside>
    <section>
      @include('partials.flash')
      @yield('content')
    </section>
  </div>
</div>

<nav class="bottomnav" aria-label="منوی پنل">
  <ul>
    @foreach($quick as $m)
      <li><a href="{{ route($m[0]) }}" class="{{ $isOn($m[0]) ? 'on' : '' }}"><x-icon name="{{ $m[2] }}"/>{{ \Illuminate\Support\Str::before($m[1], ' ') }}@if(!empty($m[3]))<span class="dot">{{ fa($m[3]) }}</span>@endif</a></li>
    @endforeach
    <li><button type="button" id="more-btn"><x-icon name="grid"/>بیشتر</button></li>
  </ul>
</nav>
<div class="sheet-back" id="sheet-back"></div>
<div class="sheet" id="sheet" role="dialog" aria-label="منو">
  <div class="grip"></div>
  <div class="row" style="margin-bottom:14px"><img class="avatar" src="{{ $u->avatarUrl() }}" alt=""><div class="grow"><b>{{ $u->name }}</b><div class="xs muted">{{ $roleName }}</div></div></div>
  <div class="sheet-grid">
    @foreach($menus as $m)<a href="{{ route($m[0]) }}"><x-icon name="{{ $m[2] }}"/>{{ $m[1] }}</a>@endforeach
    <a href="{{ route('home') }}"><x-icon name="search"/>مشاهده سایت</a>
    <form method="post" action="{{ route('logout') }}" style="display:contents">@csrf<button><x-icon name="logout"/>خروج</button></form>
  </div>
</div>
@include('partials.scripts')
</body>
</html>
