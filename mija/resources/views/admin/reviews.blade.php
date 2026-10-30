@extends('layouts.panel')
@section('title', 'نظرات')
@section('content')
<div class="page-h"><h1>مدیریت نظرات</h1></div>
@include('partials.help', ['title' => 'چرخه‌ی نظرات', 'text' => 'مهمان نظر می‌نویسد ← میزبان می‌خواند. اگر تأیید کند منتشر می‌شود؛ اگر رد کند، نظر در تب «ارجاع‌شده» برای تصمیم نهایی شما می‌آید: <b>انتشار</b> یا <b>حذف</b>. نظر دارای کلمات ممنوع اصلاً ثبت نمی‌شود.'])
<div class="tabs">@foreach(['flagged' => 'ارجاع‌شده از میزبان', 'pending' => 'منتظر میزبان', 'published' => 'منتشرشده', 'removed' => 'حذف‌شده', 'all' => 'همه'] as $k => $l)<a href="{{ route('admin.reviews', ['tab' => $k]) }}" class="{{ $tab === $k ? 'on' : '' }}">{{ $l }} @if($k === 'flagged' && ($counts['host_rejected'] ?? 0))<span class="badge red" style="padding:0 7px">{{ fa($counts['host_rejected']) }}</span>@endif</a>@endforeach</div>
<div class="card mb rv-sample"><div class="row between wrap" style="gap:8px"><div class="row wrap" style="gap:8px"><span class="badge gold">نمونه‌ی پیش‌فرض — فقط مدیر می‌بیند</span></div><span class="xs muted">برای آشنایی با شکل نظرها</span></div>
  <div class="row wrap" style="gap:10px;margin-top:10px;align-items:center"><span class="avatar" style="width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,#F8BE55,#E07F08);color:#fff;font-weight:800">س</span><div class="grow"><b>سارا محمدی</b><div class="muted small">← ویلا استخردار با ویوی جنگل (میزبان: علی رضایی)</div></div><span class="rating-s">★★★★★</span></div>
  <p style="margin:10px 0 0">ویلا دقیقاً مثل عکس‌ها بود، تمیز و دنج. میزبان بسیار مهربان و پاسخگو بود. حتماً دوباره رزرو می‌کنیم.</p>
  <div class="row wrap" style="gap:6px;margin-top:8px"><span class="badge green">پیشنهاد می‌کنم</span><span class="badge gray">منتظر تأیید میزبان</span></div>
  <div class="small muted" style="margin-top:8px">این کارت نمونه است و برای کاربران و در سایت نمایش داده نمی‌شود. نظرهای واقعی پایین‌تر فهرست می‌شوند.</div></div>
@forelse($items as $r)
<div class="card mb"><div class="row between wrap" style="gap:12px;align-items:flex-start"><div class="grow"><div class="row wrap"><a href="{{ route('admin.users.edit', $r->guest) }}"><b>{{ $r->guest->name }}</b></a><span class="muted small">← {{ $r->property->title }} (میزبان: {{ $r->property->host->name }})</span><span class="stars"><x-icon name="star"/>{{ fa($r->rating) }}</span><span class="badge {{ ['published' => 'green', 'pending_host' => 'gold', 'host_rejected' => 'orange', 'removed' => 'red'][$r->status] ?? 'gray' }}">{{ ['published' => 'منتشر', 'pending_host' => 'منتظر میزبان', 'host_rejected' => 'ارجاع‌شده', 'removed' => 'حذف‌شده'][$r->status] ?? $r->status }}</span></div>
  <p style="margin:6px 0 0">{{ $r->comment ?: '—' }}</p>
  @if($r->photos)<div class="rv-ph">@foreach($r->photoUrls() as $u)<a href="{{ $u }}" target="_blank"><img src="{{ $u }}" alt=""></a>@endforeach</div>@endif
  @if($r->host_reject_reason)<div class="alert warn" style="margin:8px 0 0"><b>دلیل رد میزبان:</b> {{ $r->host_reject_reason }}</div>@endif @if($r->host_reply)<div class="small muted" style="margin-top:6px">پاسخ میزبان: {{ $r->host_reply }}</div>@endif</div>
  <div class="stack" style="min-width:200px">
    @if($r->status !== 'published')<form method="post" action="{{ route('admin.reviews.approve', $r) }}">@csrf<button class="btn btn-green btn-sm btn-block">انتشار نظر</button></form>@endif
    @if($r->status !== 'removed')<form method="post" action="{{ route('admin.reviews.remove', $r) }}" class="row" style="gap:6px">@csrf<input class="input" name="note" placeholder="دلیل (برای مهمان)" style="padding:6px 10px"><button class="btn btn-sm btn-ghost">حذف</button></form>@endif
    <form method="post" action="{{ route('admin.reviews.destroy', $r) }}" onsubmit="return confirm('برای همیشه حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger btn-block"><x-icon name="trash" class="sm"/> حذف دائمی</button></form></div></div></div>
@empty<div class="card empty"><x-icon name="star"/><p>موردی نیست.</p></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
