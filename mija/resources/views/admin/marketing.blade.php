@extends('layouts.panel')
@section('title', 'بازاریابی')
@section('content')
@php $n = fn ($k) => fa((int) ($counts[$k] ?? 0)); @endphp
<div class="page-h"><div><h1>بازاریابی — دعوت میزبانان</h1><div class="muted">شماره‌ها را وارد کنید؛ پیامک دعوت به ثبت‌نام میزبان با لینک (کوتاه‌شده) برایشان می‌رود</div></div></div>

@unless($smsReady)<div class="alert warn"><x-icon name="info"/><div>ارسال پیامک هنوز فعال نیست؛ اول در <a href="{{ route('admin.settings', ['tab' => 'sms']) }}"><b>تنظیمات ← پیامک</b></a> توکن و خط را وارد و «ارسال فعال باشد» را بزنید. تا آن موقع پیام‌ها فقط در گزارش ثبت می‌شوند.</div></div>@endunless

<div class="tiles">
  <div class="tile card"><div class="ic3"><x-i3 name="family"/></div><b>{{ fa(array_sum($counts->all())) }}</b><span>کل شماره‌ها</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_phone"/></div><b>{{ $n('sent') }}</b><span>پیامک رفته</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_key"/></div><b>{{ $n('registered') }}</b><span>ثبت‌نام کردند</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_bell"/></div><b>{{ fa($today) }} / {{ fa($cap) }}</b><span>ارسال امروز / سقف</span></div>
</div>

<div class="grid2 mb" style="gap:18px;align-items:start">
  <form class="card" method="post" action="{{ route('admin.marketing.store') }}">@csrf
    <h3><x-i3 name="p_phone" class="sm"/> افزودن شماره و ارسال پیامک (دستی)</h3>
    <div class="field"><label>شماره‌ها — هر خط یک شماره (اختیاری: بعدش نام)</label><textarea class="input" name="numbers" rows="5" dir="ltr" placeholder="09123456789 علی رضایی&#10;09351234567" required>{{ old('numbers') }}</textarea>@error('numbers')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field"><label>متن پیام (اختیاری — اگر خالی بماند «متن پیش‌فرض» پایین استفاده می‌شود)</label><textarea class="input" name="text" rows="4" maxlength="400" placeholder="{{ $tpl }}">{{ old('text') }}</textarea><div class="help">می‌توانید از <b dir="ltr">{name}</b> و <b dir="ltr">{link}</b> (لینک کوتاهِ ثبت‌نام میزبان) استفاده کنید. «لغو۱۱» اگر ننویسید خودکار اضافه می‌شود.</div></div>
    <label class="check"><input type="checkbox" name="send" value="1" checked><div><b>همین حالا پیامک ارسال شود</b><div class="xs muted" style="font-weight:500">اگر تیک را بردارید فقط در لیست ذخیره می‌شوند.</div></div></label>
    @error('send')<div class="msg-e">{{ $message }}</div>@enderror
    <button class="btn mt"><x-icon name="send" class="sm"/> ذخیره و ارسال</button>
  </form>

  <div class="stack" style="gap:18px">
    <form class="card" method="post" action="{{ route('admin.marketing.template') }}">@csrf
      <h3><x-i3 name="p_chat" class="sm"/> متن پیش‌فرض دعوت</h3>
      <div class="field"><textarea class="input" name="mk_template" rows="5" maxlength="400" required>{{ old('mk_template', $tpl) }}</textarea></div>
      <div class="mk-prev"><small class="muted">پیش‌نمایش برای «علی»:</small><div class="rv-bub">{{ $preview }}</div></div>
      <div class="field"><label>سقف ارسال در روز</label><input class="input" type="number" dir="ltr" name="mk_daily_cap" min="1" max="5000" value="{{ old('mk_daily_cap', $cap) }}"></div>
      <button class="btn btn-light">ذخیره‌ی متن و سقف</button>
    </form>
    <div class="card"><h3><x-i3 name="p_shield" class="sm"/> نکات قانونی و ippanel</h3>
      <ul class="sp-steps" style="padding-inline-start:18px;margin:0">
        <li>پیامک تبلیغاتی فقط برای کسانی مناسب است که شماره‌شان را خودشان داده‌اند یا با شما ارتباط کاری دارند؛ ارسال انبوه به شماره‌های تصادفی خلاف مقررات است.</li>
        <li>هر پیام با «لغو۱۱» تمام می‌شود و هر کس لغو خواست را با دکمه‌ی «لغو» علامت بزنید؛ دیگر پیامی نمی‌گیرد.</li>
        <li>ippanel ممکن است پیامک دارای لینک یا تبلیغاتی را روی خط عادی رد کند؛ در این صورت خط تبلیغاتی یا الگوی تأییدشده بگیرید. علت رد در «گزارش پیامک» دیده می‌شود.</li>
      </ul></div>
  </div>
</div>

<div class="tabs">@foreach(['' => 'همه'] + collect(\App\Models\MarketingContact::STATUS)->map(fn ($v) => $v[0])->all() as $k => $l)<a href="{{ route('admin.marketing', array_filter(['status' => $k])) }}" class="{{ ($st ?? '') === (string) $k ? 'on' : '' }}">{{ $l }}@if($k !== '' && ($counts[$k] ?? 0)) <span class="badge" style="padding:0 7px">{{ fa($counts[$k]) }}</span>@endif</a>@endforeach</div>
<div class="row between wrap mb" style="gap:8px"><form method="get" class="row" style="gap:6px"><input class="input" name="q" value="{{ request('q') }}" placeholder="جستجوی شماره یا نام"><button class="btn btn-sm btn-light">جستجو</button></form>
  @if(($counts['new'] ?? 0))<form method="post" action="{{ route('admin.marketing.bulk') }}" onsubmit="return confirm('به حداکثر ۵۰ شماره‌ی ارسال‌نشده پیامک می‌رود. ادامه؟')">@csrf<button class="btn btn-sm btn-gold"><x-icon name="send" class="sm"/> ارسال به {{ fa(min(50, $counts['new'])) }} شماره‌ی ارسال‌نشده</button></form>@endif</div>

<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>شماره</th><th>نام</th><th>وضعیت</th><th>آخرین ارسال</th><th></th></tr></thead><tbody>
@forelse($items as $c)@php [$sl, $sc] = \App\Models\MarketingContact::STATUS[$c->status] ?? [$c->status, 'gray']; @endphp
<tr><td class="ltr"><b>{{ fa($c->phone) }}</b></td><td>{{ $c->name ?: '—' }}</td>
<td><span class="badge {{ $sc }}">{{ $sl }}</span>@if($c->sent_count)<div class="xs muted">{{ fa($c->sent_count) }} بار</div>@endif @if($c->status === 'failed' && $c->last_error)<div class="xs" style="color:#B42318;max-width:240px">{{ $c->last_error }}</div>@endif</td>
<td class="small nowrap">{{ $c->last_sent_at ? jdate($c->last_sent_at, 'j F H:i') : '—' }}</td>
<td><div class="row wrap" style="gap:6px">
@if(! in_array($c->status, ['optout']))<form method="post" action="{{ route('admin.marketing.send', $c) }}">@csrf<button class="btn btn-sm btn-light"><x-icon name="send" class="sm"/> {{ $c->sent_count ? 'ارسال دوباره' : 'ارسال' }}</button></form>@endif
<form method="post" action="{{ route('admin.marketing.optout', $c) }}">@csrf<button class="btn btn-sm btn-ghost">{{ $c->status === 'optout' ? 'خروج از لغو' : 'لغو کرده' }}</button></form>
<form method="post" action="{{ route('admin.marketing.destroy', $c) }}" onsubmit="return confirm('حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" aria-label="حذف"><x-icon name="trash" class="sm"/></button></form></div></td></tr>
@empty<tr><td colspan="5" class="muted" style="text-align:center;padding:24px">هنوز شماره‌ای ثبت نشده است؛ از کادر بالا اضافه کنید.</td></tr>@endforelse
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
