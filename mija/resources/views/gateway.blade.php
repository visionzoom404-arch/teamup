@extends('layouts.app')
@section('title', 'درگاه پرداخت')
@section('nofooter', '1')
@section('bare')
<div class="container"><div class="gw card">
  <span class="badge orange">درگاه آزمایشی — محیط توسعه</span>
  <h2 style="margin-top:14px">پرداخت امن</h2>
  <div class="muted small">{{ $pay->booking ? 'رزرو '.$pay->booking->code.' — '.$pay->booking->property->title : 'شارژ کیف پول' }}</div>
  <div style="font-size:30px;font-weight:800;margin:20px 0" class="grad-text">{{ toman($pay->amount) }}</div>
  <form method="post" action="{{ route('gateway.result', $pay->authority) }}">@csrf
    <button class="btn btn-green btn-lg btn-block" name="status" value="ok"><x-icon name="check"/> پرداخت موفق</button>
    <button class="btn btn-ghost btn-block mt" name="status" value="fail">انصراف / پرداخت ناموفق</button>
  </form>
  <p class="xs muted mt">پس از اتصال به درگاه واقعی این صفحه جایگزین می‌شود.</p>
</div></div>
@endsection
