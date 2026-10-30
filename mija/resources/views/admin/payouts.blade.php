@extends('layouts.panel')
@section('title', 'برداشت‌ها')
@section('content')
<div class="page-h"><h1>درخواست‌های برداشت میزبانان</h1></div>
@include('partials.help', ['title' => 'تسویه', 'text' => 'تسویه باید بین '.fa(setting('payout_hours_min')).' تا '.fa(setting('payout_hours_max')).' ساعت پس از درخواست انجام شود. پس از واریز به شبا یا کارت میزبان، «رسید» را بارگذاری یا کد پیگیری را وارد کنید و «واریز شد» را بزنید. درخواست‌های قدیمی‌تر از '.fa(setting('payout_hours_max')).' ساعت قرمز می‌شوند.'])
@forelse($items as $po)
@php $late = $po->status === 'pending' && $po->created_at->diffInHours(now()) > (int) setting('payout_hours_max'); @endphp
<div class="card mb" @if($late) style="border-color:#E5483A!important" @endif><div class="row between wrap" style="gap:14px"><div class="grow"><b>{{ $po->host->name }}</b> <span class="muted small">{{ jdate($po->created_at, 'j F Y H:i') }}</span> @if($late)<span class="badge red">معوق</span>@endif<div style="font-size:20px;font-weight:800">{{ toman($po->amount) }}</div><div class="small muted">شبا: <span class="ltr">{{ $po->sheba }}</span> — {{ $po->bank_owner }}@if($po->host->card_number) · کارت <span class="ltr">{{ $po->host->card_number }}</span>@endif</div></div>
  @if($po->status === 'pending')
  <form method="post" action="{{ route('admin.payouts.update', $po) }}" enctype="multipart/form-data" class="stack" style="min-width:260px">@csrf<input class="input" name="tracking_code" placeholder="کد پیگیری بانکی" dir="ltr"><input class="input" type="file" name="receipt" accept="image/*"><div class="row"><button class="btn btn-sm btn-green" name="action" value="paid">واریز شد</button><button class="btn btn-sm btn-ghost" name="action" value="rejected">رد و بازگشت وجه</button></div>@error('receipt')<div class="msg-e">{{ $message }}</div>@enderror</form>
  @else<div><span class="badge {{ $po->status === 'paid' ? 'green' : 'red' }}">{{ $po->status === 'paid' ? 'واریز شد' : 'رد شد' }} {{ $po->tracking_code ? '· '.fa($po->tracking_code) : '' }}</span>@if($po->receipt)<div><a class="small" style="color:var(--brand-d);text-decoration:underline" target="_blank" href="{{ asset('storage/'.$po->receipt) }}">رسید</a></div>@endif</div>@endif</div></div>
@empty<div class="card empty"><x-icon name="wallet"/><p>درخواستی نیست.</p></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
