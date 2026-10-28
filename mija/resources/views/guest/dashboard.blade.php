@extends('layouts.panel')
@section('title', 'داشبورد مهمان')
@section('content')
<div class="page-h"><div><h1>سلام {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }} 👋</h1><div class="muted">سفر بعدی‌ات را برنامه‌ریزی کن.</div></div><a class="btn" href="{{ route('search') }}"><x-icon name="search"/> جستجوی اقامتگاه</a></div>

<div class="tiles">
  <div class="tile card"><div class="ic"><x-icon name="calendar"/></div><b>{{ fa($stats['total']) }}</b><span>کل رزروها</span></div>
  <div class="tile card"><div class="ic green"><x-icon name="check"/></div><b>{{ fa($stats['done']) }}</b><span>اقامت انجام‌شده</span></div>
  <div class="tile card"><div class="ic blue"><x-icon name="sun"/></div><b>{{ fa($stats['nights']) }}</b><span>شب اقامت</span></div>
  <div class="tile card"><div class="ic red"><x-icon name="heart"/></div><b>{{ fa($stats['fav']) }}</b><span>علاقه‌مندی</span></div>
</div>

@if($next)
<div class="card" style="background:linear-gradient(120deg,rgba(17,18,22,.94),rgba(150,18,34,.92));color:#fff;margin-bottom:20px">
  <div class="row wrap" style="gap:18px"><img src="{{ $next->property->cover() }}" style="width:130px;height:100px;border-radius:16px;object-fit:cover" alt="">
    <div class="grow"><span class="badge gold">سفر بعدی شما</span><h3 style="color:#fff;margin:6px 0 2px">{{ $next->property->title }}</h3><div style="opacity:.85">{{ jdate($next->check_in, 'l j F') }} · {{ $next->property->province }}، {{ $next->property->city }}</div><div class="small" style="opacity:.75">{{ ($d = (int) today()->diffInDays($next->check_in, false)) > 0 ? fa($d).' روز دیگر' : 'در حال اقامت' }}</div></div>
    <a class="btn btn-gold" href="{{ route('guest.bookings.show', $next) }}">جزئیات سفر</a></div>
</div>
@endif

@if($pending->count())
<div class="card mb"><h3>رزروهای در انتظار اقدام</h3>
  @foreach($pending as $b)@include('partials.booking-row', ['b' => $b, 'to' => route('guest.bookings.show', $b)])@endforeach
</div>
@endif

<div class="sec-h"><h2>پیشنهاد برای تو</h2><a href="{{ route('search') }}" class="small" style="color:var(--red-d);font-weight:700">مشاهده بیشتر</a></div>
<div class="grid-cards">@foreach($recommended as $p)@include('partials.pcard')@endforeach</div>
@endsection
