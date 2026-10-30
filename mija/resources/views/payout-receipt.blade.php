@extends('layouts.app')
@section('title', 'رسید واریز')
@section('nofooter', '1')
@section('bare')
<div class="container inv-wrap">
  <article class="inv rcpt" id="invoice">
    <header class="inv-h"><img src="{{ asset('img/mija-logo-h.png') }}" alt="می‌جا" height="38"><div class="inv-no"><small>شماره‌ی رسید</small><b class="ltr">MJ-P{{ str_pad($po->id, 5, '0', STR_PAD_LEFT) }}</b><small>{{ jdate($po->processed_at ?? $po->created_at, 'j F Y — H:i') }}</small></div></header>
    <div class="stamp {{ $po->status === 'paid' ? 'ok' : 'bad' }}"><x-icon name="{{ $po->status === 'paid' ? 'check' : 'x' }}"/>{{ $po->status === 'paid' ? 'واریز شد' : 'رد شد' }}</div>
    <div class="rcpt-amt"><small>مبلغ واریزی</small><b>{{ toman($po->amount) }}</b></div>
    <table class="inv-t"><tbody>
      <tr><td>دریافت‌کننده</td><td>{{ $po->host->name }}</td></tr>
      <tr><td>صاحب حساب</td><td>{{ $po->bank_owner ?: $po->host->name }}</td></tr>
      <tr><td>شماره شبا</td><td class="ltr">{{ $po->sheba }}</td></tr>
      @if($po->tracking_code)<tr><td>کد پیگیری بانکی</td><td class="ltr">{{ $po->tracking_code }}</td></tr>@endif
      <tr><td>تاریخ درخواست</td><td>{{ jdate($po->created_at, 'j F Y — H:i') }}</td></tr>
      <tr><td>تاریخ واریز</td><td>{{ $po->processed_at ? jdate($po->processed_at, 'j F Y — H:i') : '—' }}</td></tr>
      @if($po->note)<tr><td>توضیح</td><td>{{ $po->note }}</td></tr>@endif
    </tbody><tfoot><tr class="tot"><td>مانده‌ی در انتظار تسویه</td><td>{{ toman(0) }}</td></tr></tfoot></table>
    @if($po->receipt)<div class="rcpt-img"><small>تصویر رسید بانکی</small><a href="{{ asset('storage/'.$po->receipt) }}" target="_blank"><img src="{{ asset('storage/'.$po->receipt) }}" alt="رسید بانکی"></a></div>@endif
    <p class="inv-note">این رسید به‌صورت خودکار توسط می‌جا صادر شده است.</p>
    <div class="inv-actions noprint"><button class="btn btn-light btn-sm" onclick="print()"><x-icon name="download" class="sm"/> چاپ / ذخیره PDF</button><a class="btn btn-light btn-sm" href="{{ auth()->user()->isAdmin() ? route('admin.payouts') : route('host.wallet') }}">بازگشت</a></div>
  </article>
</div>
@endsection
