@extends('layouts.panel')
@section('title', 'کیف پول')
@section('content')
@php $u = auth()->user(); @endphp
<div class="page-h"><h1>کیف پول و برداشت</h1></div>
<div class="tiles">
  <div class="tile card"><div class="ic3"><x-i3 name="p_wallet"/></div><b>{{ toman($u->wallet, false) }}</b><span>قابل برداشت (تومان)</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_hourglass"/></div><b>{{ toman($pendingIncome, false) }}</b><span>در انتظار ورود مهمان</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="sparkle"/></div><b>{{ toman($locked, false) }}</b><span>در حال تسویه</span></div>
</div>
<div class="card mb"><h3>درخواست برداشت</h3>
  @if(! $u->sheba)<div class="alert warn"><x-icon name="info"/>ابتدا شماره شبا را در <a href="{{ route('profile') }}" style="text-decoration:underline">پروفایل</a> ثبت کنید.</div>@else<p class="small muted">واریز به شبا: <span class="ltr">{{ $u->sheba }}</span> ({{ $u->bank_owner }}) — حداقل {{ toman(setting('min_payout')) }}</p>@endif
  <form method="post" action="{{ route('host.wallet.payout') }}" class="row wrap">@csrf<input class="input" style="max-width:260px" type="number" name="amount" dir="ltr" placeholder="مبلغ (تومان)" max="{{ $u->wallet }}" required data-money><button class="btn" @disabled(! $u->sheba)>ثبت درخواست</button></form>
</div>
<div class="grid2" style="gap:18px;align-items:start">
  <div class="card"><h3>تراکنش‌ها</h3>@forelse($txs as $t)<div class="row between" style="padding:9px 0;border-bottom:1px solid var(--line)"><div><b class="small">{{ $t->reason }}</b><div class="xs muted">{{ jdate($t->created_at, 'j F Y · H:i') }}</div></div><b class="ltr" style="color:{{ $t->type === 'credit' ? 'var(--green)' : 'var(--red-d)' }}">{{ $t->type === 'credit' ? '+' : '−' }}{{ toman($t->amount, false) }}</b></div>@empty<div class="empty">تراکنشی نیست.</div>@endforelse @include('partials.pager', ['paginator' => $txs])</div>
  <div class="card"><h3>درخواست‌های برداشت</h3>@forelse($payouts as $po)<div class="row between" style="padding:9px 0;border-bottom:1px solid var(--line)"><div><b>{{ toman($po->amount) }}</b><div class="xs muted">{{ jdate($po->created_at, 'j F Y') }} @if($po->tracking_code)· پیگیری {{ fa($po->tracking_code) }}@endif</div></div><span class="badge {{ ['pending'=>'gold','paid'=>'green','rejected'=>'red'][$po->status] }}">{{ ['pending'=>'در انتظار','paid'=>'واریز شد','rejected'=>'رد شد'][$po->status] }}</span></div>@empty<div class="empty">درخواستی ثبت نشده است.</div>@endforelse</div>
</div>
@endsection
