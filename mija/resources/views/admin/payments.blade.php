@extends('layouts.panel')
@section('title', 'پرداخت‌ها')
@section('content')
<div class="page-h"><h1>پرداخت‌ها</h1><a class="btn btn-sm btn-light" href="{{ route('admin.export', 'payments') }}"><x-icon name="download" class="sm"/> CSV</a></div>
<div class="tabs"><a href="{{ route('admin.payments') }}" class="{{ $tab !== 'review' ? 'on' : '' }}">همه</a><a href="{{ route('admin.payments', ['tab' => 'review']) }}" class="{{ $tab === 'review' ? 'on' : '' }}">کارت‌به‌کارت در انتظار تأیید @if($reviewCount)<span class="badge red" style="padding:0 7px">{{ fa($reviewCount) }}</span>@endif</a></div>
@if($tab === 'review')
@include('partials.help', ['title' => 'تأیید کارت‌به‌کارت', 'text' => 'پس از اطمینان از واریز مبلغ به یکی از کارت‌های سایت، پرداخت را تأیید کنید تا رزرو قطعی شود. اگر واریز نشده بود، رد کنید تا مهمان دوباره پرداخت کند. کارت‌ها را در «تنظیمات ← درگاه و کارت‌ها» تغییر دهید.'])
@forelse($items as $p)
<div class="card mb"><div class="row between wrap" style="gap:14px"><div class="grow"><b>{{ $p->user->name }}</b> <span class="muted small">{{ jdate($p->created_at, 'j F H:i') }}</span><div style="font-size:20px;font-weight:800">{{ toman($p->amount) }}</div>
  <div class="small muted">رزرو {{ $p->booking?->code }} · کد پیگیری/۴ رقم آخر: <b class="ltr">{{ $p->tracking_code }}</b> · به کارت <span class="ltr">{{ \App\Support\Payments::fmtCard($p->card_to ?? '') }}</span></div>
  @if($p->receipt)<a href="{{ asset('storage/'.$p->receipt) }}" target="_blank"><img src="{{ asset('storage/'.$p->receipt) }}" alt="" style="max-height:140px;border-radius:12px;margin-top:8px"></a>@endif</div>
  <div class="stack" style="min-width:230px"><form method="post" action="{{ route('admin.payments.approve', $p) }}">@csrf<button class="btn btn-green btn-block"><x-icon name="check"/> تأیید پرداخت و قطعی‌کردن رزرو</button></form>
  <form method="post" action="{{ route('admin.payments.reject', $p) }}" class="row" style="gap:6px">@csrf<input class="input" name="note" placeholder="دلیل رد"><button class="btn btn-ghost">رد</button></form></div></div></div>
@empty<div class="card empty"><x-icon name="coins"/><p>پرداخت کارت‌به‌کارتی در انتظار نیست.</p></div>@endforelse
@else
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>تاریخ</th><th>کاربر</th><th>بابت</th><th>مبلغ</th><th>درگاه</th><th>کد پیگیری</th><th>وضعیت</th></tr></thead><tbody>
@foreach($items as $p)<tr><td class="small nowrap">{{ jdate($p->created_at, 'Y/m/d H:i') }}</td><td>{{ $p->user->name }}</td><td>{{ $p->purpose === 'topup' ? 'شارژ کیف پول' : ($p->booking?->code) }}</td><td>{{ toman($p->amount, false) }}</td><td>{{ ['sandbox' => 'آزمایشی', 'zibal' => 'زیبال', 'wallet' => 'کیف پول', 'card' => 'کارت‌به‌کارت'][$p->gateway] ?? $p->gateway }}</td><td class="ltr">{{ $p->ref_id ?: $p->tracking_code }}</td><td><span class="badge {{ ['paid'=>'green','pending'=>'gold','review'=>'orange','failed'=>'red','refunded'=>'gray'][$p->status] ?? 'gray' }}">{{ ['paid'=>'موفق','pending'=>'در انتظار','review'=>'در انتظار تأیید','failed'=>'ناموفق','refunded'=>'بازگشتی'][$p->status] ?? $p->status }}</span></td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endif
@endsection
