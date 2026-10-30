@extends('layouts.panel')
@section('title', 'داشبورد میزبان')
@push('vendor')<script src="{{ asset('vendor/chart.umd.min.js') }}"></script>@endpush
@section('content')
@php
$revChart = ['type' => 'line', 'labels' => collect($months)->pluck('label')->all(), 'data' => collect($months)->pluck('sum')->all(), 'unit' => 'toman'];
$shareChart = ['labels' => $share->pluck('t')->all(), 'data' => $share->pluck('n')->all()];
@endphp
<div class="page-h"><div><h1>پنل میزبان</h1><div class="muted">خوش آمدی {{ \Illuminate\Support\Str::before($u->name, ' ') }}@if($hs['superhost']) · <span class="badge gold"><x-i3 name="p_crown" class="sm"/> میزبان برتر</span>@endif</div></div><a class="btn" href="{{ route('host.properties.create') }}"><x-icon name="plus"/> ثبت اقامتگاه جدید</a></div>

@unless($u->isOnboarded())<div class="now-card hot"><div class="grow"><h3><x-i3 name="p_shield" class="sm"/> اطلاعات هویتی و بانکی شما کامل نیست</h3><p>برای ثبت اقامتگاه و دریافت تسویه، ابتدا کد ملی و شماره کارت و شبا را ثبت کنید.</p><a class="btn" href="{{ route('host.onboard') }}">تکمیل اطلاعات</a></div></div>@endunless
@if($percent < 100)
<div class="card mb" style="background:linear-gradient(135deg,rgba(255,178,26,.18),rgba(255,255,255,.8))">
  <div class="row wrap" style="gap:18px"><div class="ring-p" style="--p:{{ $percent }}"><span>{{ fa($percent) }}٪</span></div><div class="grow"><h3 style="margin:0">راه‌اندازی اقامتگاه شما</h3><div class="small muted">چند قدم تا دریافت رزروهای بیشتر</div></div>
    <div class="row wrap" style="gap:6px">@foreach($check as [$t, $done, $url])@unless($done)<a class="qchip" href="{{ $url }}">{{ $t }}</a>@endunless @endforeach</div></div>
</div>
@endif

<div class="tiles">
  <div class="tile card"><div class="ic3"><x-i3 name="p_coin"/></div><b data-count="{{ $stats['income'] }}">{{ toman($stats['income'], false) }}</b><span>کل درآمد (تومان)</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_wallet"/></div><b>{{ toman($u->wallet, false) }}</b><span>موجودی کیف پول</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_calendar"/></div><b>{{ fa($stats['bookings']) }}</b><span>رزرو قطعی</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_house"/></div><b>{{ fa($stats['active']) }}/{{ fa($stats['properties']) }}</b><span>اقامتگاه فعال</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_star"/></div><b>{{ $hs['rating'] ? fa(number_format($hs['rating'], 1)) : '—' }}</b><span>میانگین امتیاز</span></div>
</div>

@if($pendingRequests->count())
<div class="card mb" style="border-color:rgba(255,150,20,.5)"><h3><x-i3 name="p_bell" class="sm"/> درخواست‌های در انتظار پاسخ ({{ fa($pendingRequests->count()) }})</h3>
  @foreach($pendingRequests as $b)
  <div class="item-row"><div class="grow"><b>{{ $b->property->title }}</b><div class="small muted">{{ $b->guest->name }} · {{ jdate($b->check_in, 'j F') }} تا {{ jdate($b->check_out, 'j F') }} · {{ fa($b->nights) }} شب · سهم شما {{ toman($b->host_payout) }}</div><div class="xs" style="color:var(--red-d)">مهلت پاسخ: {{ jdate($b->respond_by, 'j F H:i') }}</div></div>
    <form method="post" action="{{ route('host.bookings.accept', $b) }}">@csrf<button class="btn btn-sm btn-green">تأیید</button></form><a class="btn btn-sm btn-light" href="{{ route('host.bookings.show', $b) }}">جزئیات</a></div>
  @endforeach
</div>
@endif

<div class="grid2" style="gap:18px;align-items:start">
  <div class="card"><h3><x-i3 name="trend" class="sm"/> درآمد ۶ ماه اخیر</h3><div class="chart-box"><canvas data-chart="{{ json_encode($revChart, JSON_UNESCAPED_UNICODE) }}"></canvas></div></div>
  <div class="card"><h3><x-i3 name="p_pin" class="sm"/> سهم رزروها به تفکیک اقامتگاه</h3>@if($share->count())<div class="chart-box"><canvas data-donut="{{ json_encode($shareChart, JSON_UNESCAPED_UNICODE) }}"></canvas></div>@else<div class="empty" style="padding:30px">هنوز رزروی نیست.</div>@endif</div>
</div>

<div class="grid2 mt" style="gap:18px;align-items:start">
  <div class="card"><h3><x-i3 name="p_calendar" class="sm"/> اشغال ۳۰ روز آینده</h3>
    @forelse($props as $p)<div class="occ"><span>{{ \Illuminate\Support\Str::limit($p->title, 30) }}</span><div class="occ-bar"><i style="width:{{ $occ[$p->id] ?? 0 }}%"></i></div><b>{{ fa($occ[$p->id] ?? 0) }}٪</b></div>@empty<div class="empty" style="padding:24px">اقامتگاه فعالی ندارید.</div>@endforelse</div>
  <div class="card"><h3><x-i3 name="p_key" class="sm"/> ورودهای نزدیک</h3>
    @forelse($upcoming as $b)<a href="{{ route('host.bookings.show', $b) }}" class="row between" style="padding:9px 0;border-bottom:1px solid var(--line)"><div><b>{{ $b->guest->name }}</b><div class="xs muted">{{ $b->property->title }}</div></div><span class="badge blue">{{ jdate($b->check_in, 'j F') }}</span></a>@empty<div class="empty" style="padding:24px">رزرو قطعی پیش‌رو ندارید.</div>@endforelse</div>
</div>
@endsection
