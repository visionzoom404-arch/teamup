@extends('layouts.panel')
@section('title', 'کیف پول')
@section('content')
@php $u = auth()->user(); $stl = ['pending' => ['در حال بررسی', 'gold'], 'paid' => ['واریز شد', 'green'], 'rejected' => ['رد شد', 'red']]; @endphp
<div class="page-h"><div><h1>کیف پول و برداشت</h1><div class="muted">موجودی قابل برداشت شما و سابقه‌ی تسویه‌ها</div></div></div>
<div class="atm">
  @include('partials.atm', ['kind' => 'host', 'amount' => $u->wallet, 'label' => 'موجودی قابل برداشت', 'holder' => $u->bank_owner ?: $u->name, 'sub' => $u->sheba ?: 'ثبت نشده', 'subLabel' => 'شماره شبا'])
  <div class="atm-side">
    <div class="atm-stat"><span class="ic"><x-i3 name="p_hourglass"/></span><div><small>در انتظار ورود مهمان</small><b>{{ toman($pendingIncome) }}</b></div></div>
    <div class="atm-stat"><span class="ic"><x-i3 name="p_coin"/></span><div><small>در حال تسویه (بررسی مدیر)</small><b>{{ toman($locked) }}</b></div></div>
    <div class="atm-stat ok"><span class="ic"><x-i3 name="p_trophy"/></span><div><small>مجموع واریزهای انجام‌شده</small><b>{{ toman($totalPaid) }}</b></div></div>
  </div>
</div>

<div class="card atm-screen mt">
  <div class="atm-screen-h"><b><x-icon name="wallet" class="sm"/> درخواست برداشت</b><span class="muted xs">تسویه {{ fa(setting('payout_hours_min')) }} تا {{ fa(setting('payout_hours_max')) }} ساعت پس از ثبت</span></div>
  @if(! $u->sheba)<div class="alert warn"><x-icon name="info"/>ابتدا اطلاعات بانکی را در <a href="{{ route('host.onboard') }}" style="text-decoration:underline">این صفحه</a> ثبت کنید.</div>
  @elseif($u->wallet < (int) setting('min_payout'))<div class="alert info"><x-icon name="info"/>حداقل مبلغ برداشت {{ toman(setting('min_payout')) }} است؛ موجودی شما هنوز به آن نرسیده.</div>@endif
  <form method="post" action="{{ route('host.wallet.payout') }}" id="po-form" class="atm-form">@csrf
    <div class="atm-display"><small>مبلغ برداشت</small><div class="ad-line"><div class="ad-in"><input id="po-amt" name="amount" dir="ltr" inputmode="numeric" placeholder="0" max="{{ $u->wallet }}" autocomplete="off" required data-money></div><em>تومان</em></div></div>
    <div class="atm-chips" data-max="{{ $u->wallet }}">@foreach([25, 50, 75, 100] as $pc)<button type="button" data-pc="{{ $pc }}">{{ $pc === 100 ? 'همه‌ی موجودی' : fa($pc).'٪' }}</button>@endforeach</div>
    <div class="atm-to"><x-icon name="shield" class="sm"/> واریز به شبا: <b class="ltr">{{ $u->sheba ?: '—' }}</b> @if($u->bank_name)<span class="muted">({{ $u->bank_name }})</span>@endif</div>
    @error('amount')<div class="msg-e">{{ $message }}</div>@enderror
    <button class="btn btn-lg btn-block atm-go" @disabled(! $u->sheba || $u->wallet < (int) setting('min_payout'))><span class="spin"></span><span class="lbl">ثبت درخواست برداشت</span></button>
  </form>
</div>

<div class="grid2 mt" style="gap:18px;align-items:start">
  <div class="card"><h3>تراکنش‌ها</h3>
    @forelse($txs as $t)<div class="stmt"><span class="stmt-ic {{ $t->type }}">{{ $t->type === 'credit' ? '↓' : '↑' }}</span><div class="grow"><b class="small">{{ $t->reason }}</b><div class="xs muted">{{ jdate($t->created_at, 'j F Y · H:i') }}</div></div><b class="ltr stmt-amt {{ $t->type }}">{{ $t->type === 'credit' ? '+' : '−' }}{{ fa(number_format($t->amount)) }}</b></div>@empty<p class="muted small">هنوز تراکنشی ندارید.</p>@endforelse
    @include('partials.pager', ['paginator' => $txs])
  </div>
  <div class="card"><h3>درخواست‌های برداشت و رسیدها</h3>
    @forelse($payouts as $po)<div class="stmt"><span class="stmt-ic {{ $po->status }}"><x-icon name="{{ $po->status === 'paid' ? 'check' : ($po->status === 'rejected' ? 'x' : 'clock') }}" class="sm"/></span><div class="grow"><b>{{ toman($po->amount) }}</b><div class="xs muted">{{ jdate($po->created_at, 'j F Y') }} · <span class="badge {{ $stl[$po->status][1] }}" style="padding:0 8px">{{ $stl[$po->status][0] }}</span></div></div>@if($po->status === 'paid')<a class="btn btn-sm btn-light" href="{{ route('payout.receipt', $po) }}"><x-icon name="receipt" class="sm"/> رسید</a>@endif</div>@empty<p class="muted small">هنوز درخواستی ثبت نکرده‌اید.</p>@endforelse
  </div>
</div>
@endsection
