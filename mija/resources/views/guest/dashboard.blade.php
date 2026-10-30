@extends('layouts.panel')
@section('title', 'داشبورد مهمان')
@section('content')
<div class="page-h"><div><h1>سلام {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }} <x-i3 name="wave" class="lg h-i3"/></h1><div class="muted">سفر بعدی‌ات را برنامه‌ریزی کن.</div></div><a class="btn" href="{{ route('search') }}"><x-icon name="search"/> جستجوی اقامتگاه</a></div>

<div class="tiles">
  <div class="tile card"><div class="ic3"><x-i3 name="p_calendar"/></div><b>{{ fa($stats['total']) }}</b><span>کل رزروها</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="check"/></div><b>{{ fa($stats['done']) }}</b><span>اقامت انجام‌شده</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="sun"/></div><b>{{ fa($stats['nights']) }}</b><span>شب اقامت</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_heart"/></div><b>{{ fa($stats['fav']) }}</b><span>علاقه‌مندی</span></div>
</div>

@if($next)
<div class="card next-trip" style="background:linear-gradient(120deg,#FBD27C,#E8860C)!important;color:#2a1a02;margin-bottom:20px">
  <div class="row wrap" style="gap:18px"><img src="{{ $next->property->cover() }}" style="width:130px;height:100px;border-radius:16px;object-fit:cover" alt="">
    <div class="grow"><span class="badge gold">سفر بعدی شما</span><h3 style="margin:6px 0 2px">{{ $next->property->title }}</h3><div style="opacity:.85">{{ jdate($next->check_in, 'l j F') }} · {{ $next->property->province }}، {{ $next->property->city }}</div><div class="small" style="opacity:.75">{{ ($d = (int) today()->diffInDays($next->check_in, false)) > 0 ? fa($d).' روز دیگر' : 'در حال اقامت' }}</div></div>
    <a class="btn btn-gold" href="{{ route('guest.bookings.show', $next) }}">جزئیات سفر</a></div>
</div>
@endif

@if(auth()->user()->installment_unlocked)
<div class="alert ok"><x-i3 name="p_crown" class="sm"/><div><b>رزرو اقساطی برای شما فعال است.</b> به‌دلیل امتیاز بالایتان در می‌جا، هنگام پرداخت می‌توانید «پرداخت اقساطی» را انتخاب کنید.</div></div>
@endif
@if($dueInstallments->count())
<div class="card mb"><h3><x-i3 name="p_calendar" class="sm"/> اقساط پیش‌رو</h3>@foreach($dueInstallments as $in)<div class="inst"><span class="n">{{ fa($in->seq) }}</span><div class="grow"><b>{{ toman($in->amount) }}</b><div class="xs muted">{{ $in->booking->property->title }} · سررسید {{ jdate($in->due_on, 'j F') }}</div></div><a class="btn btn-sm" href="{{ route('guest.bookings.show', $in->booking) }}">پرداخت</a></div>@endforeach</div>
@endif
@if($pending->count())
<div class="card mb"><h3>رزروهای در انتظار اقدام</h3>
  @foreach($pending as $b)@include('partials.booking-row', ['b' => $b, 'to' => route('guest.bookings.show', $b)])@endforeach
</div>
@endif

<div class="sec-h"><h2>پیشنهاد برای تو</h2><a href="{{ route('search') }}" class="small" style="color:var(--red-d);font-weight:700">مشاهده بیشتر</a></div>
<div class="grid-cards">@foreach($recommended as $p)@include('partials.pcard')@endforeach</div>
@endsection
