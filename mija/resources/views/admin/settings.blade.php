@extends('layouts.panel')
@section('title', 'تنظیمات')
@section('content')
<div class="page-h"><h1>تنظیمات و کمیسیون</h1></div>
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf
  <div class="grid3">
    <div class="field"><label>کمیسیون از میزبان (٪)</label><input class="input" dir="ltr" type="number" name="commission_percent" value="{{ setting('commission_percent') }}"></div>
    <div class="field"><label>کارمزد خدمات از مهمان (٪)</label><input class="input" dir="ltr" type="number" name="service_fee_percent" value="{{ setting('service_fee_percent') }}"></div>
    <div class="field"><label>حداقل مبلغ برداشت (تومان)</label><input class="input" dir="ltr" type="number" name="min_payout" value="{{ setting('min_payout') }}"></div>
    <div class="field"><label>مهلت پاسخ میزبان (ساعت)</label><input class="input" dir="ltr" type="number" name="host_response_hours" value="{{ setting('host_response_hours') }}"></div>
    <div class="field"><label>مهلت پرداخت (دقیقه)</label><input class="input" dir="ltr" type="number" name="payment_window_minutes" value="{{ setting('payment_window_minutes') }}"></div>
  </div><button class="btn">ذخیره</button></form>

<div class="card"><h3>ایام پیک (قیمت ویژه)</h3>
  <form method="post" action="{{ route('admin.peaks.store') }}" class="row wrap mb">@csrf<input class="input" style="max-width:200px" name="title" placeholder="عنوان (نوروز)" required><input class="input" style="max-width:150px" dir="ltr" name="from" placeholder="1406/01/01" required><input class="input" style="max-width:150px" dir="ltr" name="to" placeholder="1406/01/15" required><button class="btn btn-sm">افزودن</button></form>
  @foreach($peaks as $pk)<div class="row between" style="padding:8px 0;border-bottom:1px solid var(--line)"><div><b>{{ $pk->title }}</b> <span class="muted small">{{ jdate($pk->starts_on, 'j F Y') }} تا {{ jdate($pk->ends_on, 'j F Y') }}</span></div><form method="post" action="{{ route('admin.peaks.destroy', $pk) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-ghost"><x-icon name="trash" class="sm"/></button></form></div>@endforeach
</div>
@endsection
