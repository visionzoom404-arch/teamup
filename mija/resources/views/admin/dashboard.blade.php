@extends('layouts.panel')
@section('title', 'پنل مدیریت')
@section('content')
@php $mx = max(1, collect($days)->max('gmv')); $mc = max(1, collect($days)->max('count')); @endphp
<div class="page-h"><div><h1>پنل مدیریت می‌جا</h1><div class="muted">نمای کلی پلتفرم</div></div>
  <form method="post" action="{{ route('admin.tick') }}">@csrf<button class="btn btn-sm btn-light"><x-icon name="bolt" class="sm"/> اجرای تسویه و انقضا</button></form></div>

<div class="tiles">
  <div class="tile card"><div class="ic"><x-icon name="coins"/></div><b>{{ toman($kpi['gmv'], false) }}</b><span>حجم رزروها (تومان)</span></div>
  <div class="tile card"><div class="ic green"><x-icon name="wallet"/></div><b>{{ toman($kpi['commission'], false) }}</b><span>کمیسیون پلتفرم</span></div>
  <div class="tile card"><div class="ic blue"><x-icon name="calendar"/></div><b>{{ fa($kpi['bookings']) }}</b><span>کل رزروها</span></div>
  <div class="tile card"><div class="ic dark"><x-icon name="users"/></div><b>{{ fa($kpi['users']) }}</b><span>مهمان</span></div>
  <div class="tile card"><div class="ic red"><x-icon name="key"/></div><b>{{ fa($kpi['hosts']) }}</b><span>میزبان</span></div>
  <div class="tile card"><div class="ic"><x-icon name="home"/></div><b>{{ fa($kpi['properties']) }}</b><span>اقامتگاه فعال</span></div>
</div>

@if(array_sum($queue))
<div class="card mb" style="border-color:rgba(255,150,20,.5)"><h3>نیازمند اقدام</h3><div class="row wrap">
  @if($queue['properties'])<a class="btn btn-gold" href="{{ route('admin.properties', ['status' => 'pending']) }}">{{ fa($queue['properties']) }} اقامتگاه در انتظار تأیید</a>@endif
  @if($queue['payouts'])<a class="btn btn-blue" href="{{ route('admin.payouts') }}">{{ fa($queue['payouts']) }} درخواست برداشت</a>@endif
  @if($queue['unverified'])<a class="btn btn-light" href="{{ route('admin.users', ['role' => 'host']) }}">{{ fa($queue['unverified']) }} میزبان تأیید‌نشده</a>@endif
</div></div>
@endif

<div class="grid2" style="gap:18px;align-items:start">
  <div class="card"><h3>رزرو ۱۴ روز اخیر</h3><div class="bars" style="height:130px">@foreach($days as $d)<div class="bar"><i style="height:{{ max(3, $d['count'] / $mc * 100) }}%"></i><span>{{ $d['label'] }}</span></div>@endforeach</div></div>
  <div class="card"><h3>پرداخت‌های ۱۴ روز اخیر</h3><div class="bars" style="height:130px">@foreach($days as $d)<div class="bar"><i style="height:{{ max(3, $d['gmv'] / $mx * 100) }}%;background:var(--grad-red)"></i><span>{{ $d['label'] }}</span></div>@endforeach</div></div>
</div>

<div class="grid2 mt" style="gap:18px;align-items:start">
  <div class="card"><h3>اقامتگاه‌های در انتظار تأیید</h3>@forelse($pendingProps as $p)<a class="row between" href="{{ route('admin.properties.show', $p) }}" style="padding:9px 0;border-bottom:1px solid var(--line)"><div><b>{{ $p->title }}</b><div class="xs muted">{{ $p->host->name }} · {{ $p->city }}</div></div><x-icon name="chev-l" class="sm"/></a>@empty<div class="empty" style="padding:20px">موردی نیست 🎉</div>@endforelse</div>
  <div class="card"><h3>آخرین رزروها</h3>@foreach($recent as $b)<a class="row between" href="{{ route('admin.bookings.show', $b) }}" style="padding:9px 0;border-bottom:1px solid var(--line)"><div><b class="small">{{ $b->property->title }}</b><div class="xs muted">{{ $b->guest->name }} · {{ toman($b->total) }}</div></div><span class="badge {{ $b->statusColor() }}">{{ $b->statusLabel() }}</span></a>@endforeach</div>
</div>
@endsection
