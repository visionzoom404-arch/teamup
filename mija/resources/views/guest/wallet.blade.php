@extends('layouts.panel')
@section('title', 'کیف پول')
@section('content')
<div class="page-h"><h1>کیف پول</h1></div>
<div class="card" style="background:var(--grad-warm);color:#fff;border-color:rgba(255,255,255,.4)">
  <div class="muted" style="color:rgba(255,255,255,.85)">موجودی شما</div><div style="font-size:34px;font-weight:800">{{ toman(auth()->user()->wallet) }}</div>
  <form method="post" action="{{ route('guest.wallet.topup') }}" class="row wrap mt">@csrf<input class="input" style="max-width:240px" type="number" name="amount" min="10000" step="1000" placeholder="مبلغ شارژ (تومان)" required dir="ltr"><button class="btn btn-dark">شارژ کیف پول</button></form>
</div>
<div class="card mt"><h3>تراکنش‌ها</h3>
  @forelse($txs as $t)<div class="row between" style="padding:10px 0;border-bottom:1px solid var(--line)"><div><b>{{ $t->reason }}</b><div class="xs muted">{{ jdate($t->created_at, 'j F Y · H:i') }}</div></div><b style="color:{{ $t->type === 'credit' ? 'var(--green)' : 'var(--red-d)' }}" class="ltr">{{ $t->type === 'credit' ? '+' : '−' }}{{ toman($t->amount) }}</b></div>@empty<div class="empty">تراکنشی ثبت نشده است.</div>@endforelse
  @include('partials.pager', ['paginator' => $txs])
</div>
@endsection
