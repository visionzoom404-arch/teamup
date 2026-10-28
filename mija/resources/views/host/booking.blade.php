@extends('layouts.panel')
@section('title', 'رزرو '.$b->code)
@section('content')
@php $p = $b->property; @endphp
<div class="page-h"><div><a href="{{ route('host.bookings') }}" class="small muted">← رزروها</a><h1>رزرو <span class="ltr">{{ $b->code }}</span></h1></div><span class="badge {{ $b->statusColor() }}" style="font-size:14px;padding:6px 16px">{{ $b->statusLabel() }}</span></div>

@if($b->status === 'pending_host')
<div class="card mb" style="border-color:rgba(255,150,20,.55)"><h3>پاسخ به درخواست</h3><p class="small muted">مهلت پاسخ تا {{ jdate($b->respond_by, 'j F ساعت H:i') }}؛ در غیر این صورت درخواست منقضی می‌شود.</p>
  <div class="row wrap"><form method="post" action="{{ route('host.bookings.accept', $b) }}">@csrf<button class="btn btn-green"><x-icon name="check"/> تأیید درخواست</button></form>
  <form method="post" action="{{ route('host.bookings.reject', $b) }}" class="row grow">@csrf<input class="input" name="reason" placeholder="دلیل رد (اختیاری)"><button class="btn btn-ghost">رد</button></form></div></div>
@endif

<div class="grid2" style="gap:18px;align-items:start">
  <div class="card"><h3>{{ $p->title }}</h3>
    <div class="qrow"><span>مهمان</span><b>{{ $b->guest->name }}</b></div>
    @if(in_array($b->status, ['confirmed','completed']))<div class="qrow"><span>تلفن مهمان</span><b class="ltr">{{ $b->guest->phone }}</b></div>@endif
    <div class="qrow"><span>ورود</span><b>{{ jdate($b->check_in, 'l j F Y') }}</b></div>
    <div class="qrow"><span>خروج</span><b>{{ jdate($b->check_out, 'l j F Y') }}</b></div>
    <div class="qrow"><span>مدت</span><b>{{ fa($b->nights) }} شب</b></div>
    <div class="qrow"><span>مسافران</span><b>{{ fa($b->adults) }} بزرگسال @if($b->children)، {{ fa($b->children) }} کودک @endif</b></div>
    @if($b->guest_note)<div class="alert info mt" style="margin-bottom:0"><x-icon name="chat"/>{{ $b->guest_note }}</div>@endif
  </div>
  <div class="card"><h3>مالی</h3>
    <div class="qrow"><span>مبلغ پرداختی مهمان</span><span>{{ toman($b->total) }}</span></div>
    <div class="qrow"><span>کمیسیون می‌جا ({{ fa(setting('commission_percent')) }}٪)</span><span>−{{ toman($b->commission) }}</span></div>
    <div class="qrow total"><span>سهم شما</span><span>{{ toman($b->host_payout) }}</span></div>
    <p class="xs muted">{{ $b->settled_at ? 'واریز شده به کیف پول در '.jdate($b->settled_at, 'j F') : 'پس از ورود مهمان به کیف پول شما واریز می‌شود.' }}</p>
    @if(in_array($b->status, ['confirmed','awaiting_payment']))
    <form method="post" action="{{ route('host.bookings.cancel', $b) }}" class="mt" onsubmit="return confirm('لغو رزرو توسط میزبان؟ مبلغ کامل به مهمان بازگردانده می‌شود.')">@csrf
      <input class="input" name="reason" placeholder="دلیل لغو" style="margin-bottom:8px"><button class="btn btn-ghost btn-block">لغو رزرو توسط میزبان</button></form>
    @endif
  </div>
</div>
@if($b->review)<div class="card mt"><h3>نظر مهمان</h3><span class="stars"><x-icon name="star"/>{{ fa($b->review->rating) }}</span><p class="small">{{ $b->review->comment }}</p></div>@endif
@include('partials.chat', ['b' => $b])
@endsection
