@extends('layouts.panel')
@section('title', 'برداشت‌ها')
@section('content')
<div class="page-h"><h1>درخواست‌های برداشت میزبانان</h1></div>
@forelse($items as $po)
<div class="card mb"><div class="row between wrap"><div><b>{{ $po->host->name }}</b> <span class="muted small">{{ jdate($po->created_at, 'j F Y H:i') }}</span><div style="font-size:20px;font-weight:800">{{ toman($po->amount) }}</div><div class="small muted">شبا: <span class="ltr">{{ $po->sheba }}</span> — {{ $po->bank_owner }}</div></div>
  @if($po->status === 'pending')
  <form method="post" action="{{ route('admin.payouts.update', $po) }}" class="row wrap">@csrf<input class="input" style="max-width:170px" name="tracking_code" placeholder="کد پیگیری بانکی" dir="ltr"><button class="btn btn-sm btn-green" name="action" value="paid">واریز شد</button><button class="btn btn-sm btn-ghost" name="action" value="rejected">رد و بازگشت وجه</button></form>
  @else<span class="badge {{ $po->status === 'paid' ? 'green' : 'red' }}">{{ $po->status === 'paid' ? 'واریز شد' : 'رد شد' }} {{ $po->tracking_code ? '· '.fa($po->tracking_code) : '' }}</span>@endif</div></div>
@empty<div class="card empty"><x-icon name="wallet"/><p>درخواستی نیست.</p></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
