<!doctype html>
<html lang="fa" dir="rtl"><head>@include('partials.head')<style>
body{background:#f4f5f8!important;animation:none}body::before{display:none}
.inv{max-width:820px;margin:24px auto;background:#fff;border-radius:22px;padding:38px 42px;box-shadow:0 20px 60px rgba(20,22,32,.1);position:relative;overflow:hidden}
.inv::before{content:"";position:absolute;top:0;inset-inline:0;height:8px;background:var(--grad-warm)}
.inv h1{font-size:24px;margin:0}.inv table{width:100%;border-collapse:collapse;margin-top:18px;font-size:14px}.inv td,.inv th{padding:10px 8px;border-bottom:1px solid #eee;text-align:start}
.inv .tot td{font-weight:800;font-size:16px;border-top:2px solid #111;border-bottom:0}
.stamp{position:absolute;inset-inline-start:36px;top:120px;transform:rotate(-12deg);border:3px solid var(--green);color:var(--green);padding:4px 18px;border-radius:12px;font-weight:800;font-size:20px;opacity:.8}
@media print{body{background:#fff!important}.inv{box-shadow:none;margin:0}.noprint{display:none!important}}
</style></head><body>
<div class="inv">
  <div class="row between" style="margin-top:8px"><div class="row"><img src="{{ asset('img/mija-mark.svg') }}" width="54" alt=""><div><h1>فاکتور رزرو</h1><div class="muted small">می‌جا — پلتفرم رزرو ویلا و کلبه</div></div></div><div class="center"><div class="muted small">شماره رزرو</div><b class="ltr" style="font-size:20px">{{ $b->code }}</b></div></div>
  @if(in_array($b->status, ['confirmed','completed']))<div class="stamp">پرداخت‌شده</div>@endif
  <hr class="divider">
  <div class="grid2"><div><div class="muted small">مهمان</div><b>{{ $b->guest->name }}</b><div class="small ltr">{{ fa($b->guest->phone) }}</div></div><div><div class="muted small">میزبان</div><b>{{ $b->property->host->name }}</b></div></div>
  <div class="grid2" style="margin-top:14px"><div><div class="muted small">اقامتگاه</div><b>{{ $b->property->title }}</b><div class="small">{{ $b->property->province }}، {{ $b->property->city }}</div></div><div><div class="muted small">تاریخ اقامت</div><b>{{ jdate($b->check_in, 'j F Y') }} تا {{ jdate($b->check_out, 'j F Y') }}</b><div class="small">{{ fa($b->nights) }} شب · {{ fa($b->guestsCount()) }} نفر</div></div></div>
  @php $bd = $b->breakdown ?? []; @endphp
  <table><thead><tr><th>شرح</th><th style="text-align:end">مبلغ (تومان)</th></tr></thead><tbody>
    <tr><td>اقامت ({{ fa($b->nights) }} شب)</td><td style="text-align:end">{{ toman($bd['base'] ?? 0, false) }}</td></tr>
    @if(($bd['extra'] ?? 0) > 0)<tr><td>نفر اضافه</td><td style="text-align:end">{{ toman($bd['extra'], false) }}</td></tr>@endif
    @if(($bd['cleaning'] ?? 0) > 0)<tr><td>هزینه‌ی نظافت</td><td style="text-align:end">{{ toman($bd['cleaning'], false) }}</td></tr>@endif
    @if(($bd['long_discount'] ?? 0) > 0)<tr><td>تخفیف اقامت طولانی</td><td style="text-align:end">−{{ toman($bd['long_discount'], false) }}</td></tr>@endif
    @if($b->discount > 0)<tr><td>کد تخفیف {{ $b->coupon_code }}</td><td style="text-align:end">−{{ toman($b->discount, false) }}</td></tr>@endif
    @if($b->service_fee > 0)<tr><td>کارمزد خدمات</td><td style="text-align:end">{{ toman($b->service_fee, false) }}</td></tr>@endif
    <tr class="tot"><td>مبلغ کل</td><td style="text-align:end">{{ toman($b->total, false) }}</td></tr></tbody></table>
  <p class="small muted" style="margin-top:18px">وضعیت: {{ $b->statusLabel() }} @if($b->paid_at)· پرداخت در {{ jdate($b->paid_at, 'j F Y H:i') }}@endif @foreach($b->payments->where('status','paid') as $pay)· کد پیگیری <span class="ltr">{{ $pay->ref_id }}</span>@endforeach</p>
  <div class="row noprint" style="gap:8px;margin-top:18px"><button class="btn" onclick="print()">چاپ / ذخیره PDF</button><a class="btn btn-light" href="javascript:history.back()">بازگشت</a></div>
</div></body></html>
