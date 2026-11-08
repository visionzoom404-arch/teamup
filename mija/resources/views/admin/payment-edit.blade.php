@extends('layouts.panel')
@section('title', 'ویرایش پرداخت')
@section('content')
<div class="page-h"><div><a href="{{ route('admin.payments') }}" class="small muted">← پرداخت‌ها</a><h1>ویرایش پرداخت #{{ fa($p->id) }}</h1></div></div>
<div class="alert warn"><x-icon name="info"/><div>این صفحه فقط «سابقه‌ی پرداخت» را اصلاح می‌کند. عوض‌کردن وضعیت به «موفق» به‌تنهایی رزرو را قطعی نمی‌کند؛ اگر می‌خواهید اثرش (قطعی‌شدن رزرو یا شارژ کیف پول) هم اعمال شود، تیک پایین را بزنید. هر تغییر در «گزارش فعالیت» ثبت می‌شود.</div></div>
<form class="card mb" method="post" action="{{ route('admin.payments.update', $p) }}">@csrf
  <div class="qrow"><span>کاربر</span><b>{{ $p->user->name }}</b></div>
  <div class="qrow"><span>بابت</span><b>{{ $p->purpose === 'topup' ? 'شارژ کیف پول' : ($p->booking?->code ?: '—') }}</b></div>
  <div class="grid2 mt">
    <div class="field"><label>مبلغ (تومان)</label><input class="input" dir="ltr" type="number" name="amount" value="{{ old('amount', $p->amount) }}" required>@error('amount')<div class="msg-e">{{ fa($message) }}</div>@enderror</div>
    <div class="field"><label>وضعیت</label><select class="input" name="status">@foreach(['pending' => 'در انتظار', 'review' => 'در انتظار تأیید', 'paid' => 'موفق', 'failed' => 'ناموفق', 'refunded' => 'بازگشتی'] as $k => $l)<option value="{{ $k }}" @selected(old('status', $p->status) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>درگاه / روش</label><select class="input" name="gateway">@foreach(['zibal' => 'زیبال', 'bitpay' => 'بیت‌پی', 'card' => 'کارت‌به‌کارت', 'wallet' => 'کیف پول', 'sandbox' => 'آزمایشی'] as $k => $l)<option value="{{ $k }}" @selected(old('gateway', $p->gateway) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label>کد رهگیری (ref)</label><input class="input" dir="ltr" name="ref_id" value="{{ old('ref_id', $p->ref_id) }}"></div>
    <div class="field"><label>کد پیگیری کارت‌به‌کارت</label><input class="input" dir="ltr" name="tracking_code" value="{{ old('tracking_code', $p->tracking_code) }}"></div>
    <div class="field"><label>یادداشت</label><input class="input" name="note" value="{{ old('note', $p->note) }}"></div>
  </div>
  @if($p->status !== 'paid')<label class="check"><input type="checkbox" name="apply" value="1"><div><b>اگر وضعیت را «موفق» کردم، اثرش را هم اعمال کن</b><div class="xs muted" style="font-weight:500">رزرو قطعی می‌شود (یا کیف پول شارژ می‌شود) و پیامک/اعلان معمول ارسال می‌شود.</div></div></label>@endif
  <div class="row wrap mt" style="gap:8px"><button class="btn">ذخیره</button><a class="btn btn-light" href="{{ route('admin.payments') }}">انصراف</a></div>
</form>
<form method="post" action="{{ route('admin.payments.destroy', $p) }}" onsubmit="return confirm('این پرداخت برای همیشه حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-ghost" style="color:#B42318"><x-icon name="trash" class="sm"/> حذف این پرداخت</button></form>
@endsection
