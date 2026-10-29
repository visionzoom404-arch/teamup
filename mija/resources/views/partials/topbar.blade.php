@php $u = auth()->user(); $unread = $u ? $u->notices()->whereNull('read_at')->count() : 0; @endphp
<header class="topbar">
  <div class="container">
    <a href="{{ route('home') }}" class="brand" aria-label="می‌جا"><img src="{{ asset('img/mija-mark.svg') }}" alt=""><span>می‌جا<small>MIJA</small></span></a>
    <nav class="topnav">
      <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'on' : '' }}">خانه</a>
      <a href="{{ route('search') }}" class="{{ request()->routeIs('search') && ! request('type') ? 'on' : '' }}">جستجوی اقامتگاه</a>
      <a href="{{ route('search', ['type' => ['villa']]) }}">ویلا</a>
      <a href="{{ route('search', ['type' => ['cottage', 'tiny']]) }}">کلبه</a>
      <a href="{{ route('page', 'become-host') }}" class="{{ request()->is('page/become-host') ? 'on' : '' }}">میزبان شوید</a>
    </nav>
    <div class="spacer"></div>
    <div class="top-actions">
      @auth
        <a href="{{ route('notices') }}" class="top-btn" aria-label="اعلان‌ها"><x-icon name="bell"/>@if($unread)<span class="dot">{{ fa($unread) }}</span>@endif</a>
        <a href="{{ $u->homeRoute() }}" class="top-btn solid"><img class="tb-av" src="{{ $u->avatarUrl() }}" alt=""><span class="top-hide-m">{{ \Illuminate\Support\Str::limit($u->name, 14) }}</span><small class="top-hide-m role-mini {{ $u->role }}">{{ $u->roleLabel() }}</small></a>
      @else
        <a href="{{ route('login') }}" class="top-btn top-hide-m">ورود</a>
        <a href="{{ route('register') }}" class="top-btn solid">ثبت‌نام</a>
      @endauth
    </div>
  </div>
</header>
