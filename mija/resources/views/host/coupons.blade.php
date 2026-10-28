@extends('layouts.panel')
@section('title', 'کد تخفیف')
@section('content')
<div class="page-h"><h1>کدهای تخفیف من</h1></div>
<form class="card mb" method="post" action="{{ route('host.coupons.store') }}">@csrf <h3>ساخت کد جدید</h3>
  <div class="grid4">
    <div class="field"><label>کد (انگلیسی/عدد)</label><input class="input" dir="ltr" name="code" required style="text-transform:uppercase" placeholder="NOWRUZ"></div>
    <div class="field"><label>نوع</label><select name="type"><option value="percent">درصدی</option><option value="fixed">مبلغ ثابت (تومان)</option></select></div>
    <div class="field"><label>مقدار</label><input class="input" dir="ltr" type="number" name="value" required></div>
    <div class="field"><label>تعداد استفاده</label><input class="input" dir="ltr" type="number" name="usage_limit" placeholder="نامحدود"></div>
  </div>
  <div class="field" style="max-width:240px"><label>اعتبار (روز)</label><input class="input" dir="ltr" type="number" name="valid_days" placeholder="نامحدود"></div>
  <button class="btn">ساخت کد</button>
</form>
@forelse($items as $c)<div class="item-row"><div class="grow"><b class="ltr">{{ $c->code }}</b> <span class="badge">{{ $c->type === 'percent' ? fa($c->value).'٪' : toman($c->value) }}</span><div class="xs muted">استفاده: {{ fa($c->used_count) }}{{ $c->usage_limit ? ' / '.fa($c->usage_limit) : '' }} @if($c->expires_at)· تا {{ jdate($c->expires_at, 'j F Y') }}@endif</div></div>
  <form method="post" action="{{ route('host.coupons.destroy', $c) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-ghost"><x-icon name="trash" class="sm"/></button></form></div>
@empty<div class="card empty"><x-icon name="tag"/><p>کدی ندارید.</p></div>@endforelse
@endsection
