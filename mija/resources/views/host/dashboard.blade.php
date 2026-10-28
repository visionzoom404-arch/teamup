@extends('layouts.panel')
@section('title', 'داشبورد میزبان')
@section('content')
@php $max = max(1, collect($months)->max('sum')); @endphp
<div class="page-h"><div><h1>پنل میزبان</h1><div class="muted">خوش آمدی {{ $u->name }}</div></div><a class="btn" href="{{ route('host.properties.create') }}"><x-icon name="plus"/> ثبت اقامتگاه جدید</a></div>

@if(! $u->sheba)<div class="alert warn"><x-icon name="info"/><div>برای دریافت درآمد، شماره شبا را در <a href="{{ route('profile') }}" style="text-decoration:underline;font-weight:800">پروفایل</a> تکمیل کنید.</div></div>@endif
<div class="tiles">
  <div class="tile card"><div class="ic"><x-icon name="coins"/></div><b>{{ toman($stats['income'], false) }}</b><span>کل درآمد (تومان)</span></div>
  <div class="tile card"><div class="ic dark"><x-icon name="wallet"/></div><b>{{ toman($u->wallet, false) }}</b><span>موجودی کیف پول</span></div>
  <div class="tile card"><div class="ic blue"><x-icon name="calendar"/></div><b>{{ fa($stats['bookings']) }}</b><span>رزرو قطعی</span></div>
  <div class="tile card"><div class="ic green"><x-icon name="home"/></div><b>{{ fa($stats['active']) }}/{{ fa($stats['properties']) }}</b><span>اقامتگاه فعال</span></div>
  <div class="tile card"><div class="ic red"><x-icon name="star"/></div><b>{{ $stats['rating'] ? fa($stats['rating']) : '—' }}</b><span>میانگین امتیاز</span></div>
</div>

@if($pendingRequests->count())
<div class="card mb" style="border-color:rgba(255,150,20,.5)"><h3><x-icon name="bell" class="sm"/> درخواست‌های در انتظار پاسخ ({{ fa($pendingRequests->count()) }})</h3>
  @foreach($pendingRequests as $b)
  <div class="item-row"><div class="grow"><b>{{ $b->property->title }}</b><div class="small muted">{{ $b->guest->name }} · {{ jdate($b->check_in, 'j F') }} تا {{ jdate($b->check_out, 'j F') }} · {{ fa($b->nights) }} شب · {{ toman($b->host_payout) }}</div><div class="xs" style="color:var(--red-d)">مهلت پاسخ: {{ jdate($b->respond_by, 'j F H:i') }}</div></div>
    <form method="post" action="{{ route('host.bookings.accept', $b) }}">@csrf<button class="btn btn-sm btn-green">تأیید</button></form><a class="btn btn-sm btn-light" href="{{ route('host.bookings.show', $b) }}">جزئیات</a></div>
  @endforeach
</div>
@endif

<div class="grid2" style="gap:18px;align-items:start">
  <div class="card"><h3>درآمد ۶ ماه اخیر</h3><div class="bars">@foreach($months as $m)<div class="bar"><b>{{ $m['sum'] ? fa(number_format($m['sum'] / 1000000, 1)).'م' : '' }}</b><i style="height:{{ max(3, $m['sum'] / $max * 100) }}%"></i>{{ $m['label'] }}</div>@endforeach</div><div class="xs muted center">میلیون تومان</div></div>
  <div class="card"><h3>ورودهای نزدیک</h3>
    @forelse($upcoming as $b)<a href="{{ route('host.bookings.show', $b) }}" class="row between" style="padding:9px 0;border-bottom:1px solid var(--line)"><div><b>{{ $b->guest->name }}</b><div class="xs muted">{{ $b->property->title }}</div></div><span class="badge blue">{{ jdate($b->check_in, 'j F') }}</span></a>@empty<div class="empty" style="padding:24px">رزرو قطعی پیش‌رو ندارید.</div>@endforelse
  </div>
</div>
@endsection
