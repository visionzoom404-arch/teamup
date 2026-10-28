@extends('layouts.panel')
@section('title', 'رزرو '.$b->code)
@section('content')
<div class="page-h"><div><a href="{{ route('admin.bookings') }}" class="small muted">← رزروها</a><h1>رزرو <span class="ltr">{{ $b->code }}</span></h1></div><span class="badge {{ $b->statusColor() }}" style="font-size:14px;padding:6px 16px">{{ $b->statusLabel() }}</span></div>
<div class="grid2" style="gap:18px;align-items:start">
  <div class="card"><h3>{{ $b->property->title }}</h3>
    <div class="qrow"><span>مهمان</span><b>{{ $b->guest->name }} <span class="ltr">{{ $b->guest->phone }}</span></b></div>
    <div class="qrow"><span>میزبان</span><b>{{ $b->host->name }} <span class="ltr">{{ $b->host->phone }}</span></b></div>
    <div class="qrow"><span>تاریخ</span><b>{{ jdate($b->check_in, 'j F Y') }} تا {{ jdate($b->check_out, 'j F Y') }} ({{ fa($b->nights) }} شب)</b></div>
    <div class="qrow"><span>مسافران</span><b>{{ fa($b->guestsCount()) }} نفر</b></div>
    <div class="qrow"><span>سیاست لغو</span><b>{{ config("mija.cancellation_policies.{$b->cancellation_policy}.name") }}</b></div>
  </div>
  <div class="card"><h3>مالی</h3>
    <div class="qrow"><span>مبلغ کل</span><b>{{ toman($b->total) }}</b></div><div class="qrow"><span>کمیسیون</span><b>{{ toman($b->commission) }}</b></div><div class="qrow"><span>سهم میزبان</span><b>{{ toman($b->host_payout) }}</b></div>
    @if($b->refund_amount)<div class="qrow"><span>بازگشت وجه</span><b>{{ toman($b->refund_amount) }}</b></div>@endif
    <h4 style="margin-top:14px">پرداخت‌ها</h4>@forelse($b->payments as $pay)<div class="qrow small"><span>{{ $pay->gateway }} · {{ $pay->ref_id }}</span><span>{{ toman($pay->amount) }} · {{ $pay->status }}</span></div>@empty<div class="muted small">—</div>@endforelse
  </div>
</div>
@if($b->messages->count())<div class="card mt"><h3>گفتگوها</h3><div class="chat">@foreach($b->messages as $m)<div class="msg {{ $m->sender_id === $b->guest_id ? '' : 'mine' }}"><small>{{ $m->sender->name }}</small>{{ $m->body }}</div>@endforeach</div></div>@endif
@endsection
