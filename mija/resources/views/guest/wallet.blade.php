@extends('layouts.panel')
@section('title', 'کیف پول')
@section('content')
@php $u = auth()->user(); $lotSum = (int) $lots->sum('remaining'); @endphp
<div class="page-h"><div><h1>کیف پول</h1><div class="muted">موجودی برای رزرو بعدی، هدیه‌ی نظرها و بازگشت وجه‌ها</div></div></div>
<div class="atm">
  @include('partials.atm', ['kind' => 'guest', 'amount' => $u->wallet, 'label' => 'موجودی کیف پول', 'holder' => $u->name, 'sub' => fa($u->phone), 'subLabel' => 'شماره موبایل'])
  <div class="atm-side">
    <div class="atm-stat"><span class="ic"><x-i3 name="p_gift"/></span><div><small>هدیه‌ی نظر (موقت)</small><b>{{ toman($lotSum) }}</b></div></div>
    <div class="atm-stat"><span class="ic"><x-i3 name="p_shield"/></span><div><small>بازگشت وجه به همین کیف پول</small><b>در صورت لغو رزرو</b></div></div>
  </div>
</div>

<div class="card atm-screen mt">
  <div class="atm-screen-h"><b><x-icon name="coins" class="sm"/> افزایش موجودی</b><span class="muted xs">پرداخت امن از درگاه بانکی</span></div>
  <form method="post" action="{{ route('guest.wallet.topup') }}" class="atm-form">@csrf
    @php $gws = \App\Support\Gateways::online(); @endphp
    @if(count($gws) > 1)<div class="atm-chips" style="margin-top:0">@foreach($gws as $gi => $gk)<label class="gw-pick"><input type="radio" name="gateway" value="{{ $gk }}" @checked($gi === 0)><span>{{ $gk === 'sandbox' ? 'آزمایشی' : \App\Support\Gateways::label($gk) }}</span></label>@endforeach</div>@elseif(empty($gws))<div class="alert warn"><x-icon name="info"/>درگاه پرداخت آنلاین فعلاً فعال نیست.</div>@endif
    <div class="atm-display"><small>مبلغ شارژ</small><div class="ad-line"><div class="ad-in"><input id="po-amt" name="amount" dir="ltr" inputmode="numeric" placeholder="0" min="10000" autocomplete="off" required data-money></div><em>تومان</em></div></div>
    <div class="atm-chips" data-fixed="1">@foreach([100000, 200000, 500000, 1000000, 2000000] as $v)<button type="button" data-v="{{ $v }}">{{ fa(number_format($v)) }}</button>@endforeach</div>
    @error('amount')<div class="msg-e">{{ $message }}</div>@enderror
    <button class="btn btn-lg btn-block atm-go"><span class="spin"></span><span class="lbl">پرداخت و شارژ کیف پول</span></button>
  </form>
</div>

@if($lots->count())
<div class="card mt"><h3>هدیه‌های نظر (تا زمان انقضا قابل استفاده)</h3>
  @foreach($lots as $l)<div class="stmt"><span class="stmt-ic credit"><x-i3 name="p_gift" class="sm"/></span><div class="grow"><b>{{ toman($l->remaining) }}</b><div class="xs muted">انقضا: {{ jdate($l->expires_at, 'j F Y') }}</div></div></div>@endforeach
</div>
@endif

<div class="card mt"><h3>تراکنش‌ها</h3>
  @forelse($txs as $t)<div class="stmt"><span class="stmt-ic {{ $t->type }}">{{ $t->type === 'credit' ? '↓' : '↑' }}</span><div class="grow"><b class="small">{{ $t->reason }}</b><div class="xs muted">{{ jdate($t->created_at, 'j F Y · H:i') }}</div></div><b class="ltr stmt-amt {{ $t->type }}">{{ $t->type === 'credit' ? '+' : '−' }}{{ fa(number_format($t->amount)) }}</b></div>@empty<p class="muted small">هنوز تراکنشی ندارید.</p>@endforelse
  @include('partials.pager', ['paginator' => $txs])
</div>
@endsection
