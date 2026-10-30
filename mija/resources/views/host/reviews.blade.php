@extends('layouts.panel')
@section('title', 'نظرات')
@section('content')
<div class="page-h"><h1>نظرات مهمانان</h1></div>
@include('partials.help', ['title' => 'نظرها چطور منتشر می‌شوند؟', 'text' => 'مهمانی که اقامت را انجام داده نظر و عکس ثبت می‌کند. شما آن را می‌خوانید؛ اگر مناسب بود «تأیید و انتشار» را بزنید (می‌توانید پاسخ هم بنویسید) تا در صفحه‌ی اقامتگاه نمایش داده شود. اگر محتوا نامناسب بود «رد» را بزنید؛ نظر برای تصمیم نهایی به مدیر سایت می‌رود و ممکن است منتشر یا حذف شود.', 'open' => $pending->count() > 0])

@if($pending->count())
<h2 style="font-size:16px;margin:10px 0">در انتظار تأیید شما ({{ fa($pending->count()) }})</h2>
@foreach($pending as $r)
<div class="card mb pend">
  <div class="row"><a href="{{ route('guest.public', $r->guest) }}"><img class="avatar sm" src="{{ $r->guest->avatarUrl() }}" alt=""></a><div class="grow"><b>{{ $r->guest->name }}</b> <span class="muted small">درباره‌ی {{ $r->property->title }}</span><div class="xs muted">{{ jdate($r->created_at, 'j F Y') }}</div></div><span class="stars"><x-icon name="star"/>{{ fa($r->rating) }}</span></div>
  <p style="margin:10px 0">{{ $r->comment }}</p>
  @if($r->photos)<div class="rv-ph">@foreach($r->photoUrls() as $u)<a href="{{ $u }}" target="_blank"><img src="{{ $u }}" alt=""></a>@endforeach</div>@endif
  <div class="row wrap" style="gap:8px;align-items:flex-start">
    <form method="post" action="{{ route('host.reviews.approve', $r) }}" class="row grow wrap" style="gap:6px">@csrf<input class="input" name="host_reply" placeholder="پاسخ شما (اختیاری)" style="flex:1;min-width:180px"><button class="btn btn-green btn-sm"><x-icon name="check" class="sm"/> تأیید و انتشار</button></form>
    <form method="post" action="{{ route('host.reviews.reject', $r) }}" class="row wrap" style="gap:6px">@csrf<input class="input" name="reason" placeholder="دلیل رد (برای مدیر)" required style="min-width:160px"><button class="btn btn-sm btn-ghost">رد و ارجاع به مدیر</button></form>
  </div>
  @error('reason')<div class="msg-e">{{ $message }}</div>@enderror
</div>
@endforeach
@endif

<h2 style="font-size:16px;margin:16px 0 10px">سایر نظرات</h2>
@forelse($items as $r)
<div class="card mb">
  <div class="row"><img class="avatar sm" src="{{ $r->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $r->guest->name }}</b> <span class="muted small">درباره‌ی {{ $r->property->title }}</span><div class="xs muted">{{ jdate($r->created_at, 'j F Y') }}</div></div><span class="badge {{ ['published' => 'green', 'host_rejected' => 'orange', 'removed' => 'red'][$r->status] ?? 'gray' }}">{{ ['published' => 'منتشر شده', 'host_rejected' => 'در انتظار تصمیم مدیر', 'removed' => 'حذف شده'][$r->status] ?? $r->status }}</span><span class="stars"><x-icon name="star"/>{{ fa($r->rating) }}</span></div>
  <p style="margin:10px 0">{{ $r->comment ?: '—' }}</p>
  @if($r->photos)<div class="rv-ph">@foreach($r->photoUrls() as $u)<a href="{{ $u }}" target="_blank"><img src="{{ $u }}" alt=""></a>@endforeach</div>@endif
  @if($r->host_reply)<div class="alert info" style="margin:0"><b>پاسخ شما:</b>&nbsp;{{ $r->host_reply }}</div>
  @elseif($r->status === 'published')<form method="post" action="{{ route('host.reviews.reply', $r) }}" class="row">@csrf<input class="input" name="host_reply" placeholder="پاسخ به مهمان…" required><button class="btn btn-sm">ارسال</button></form>@endif
</div>
@empty<div class="card empty"><x-icon name="star"/><p>هنوز نظری دریافت نکرده‌اید.</p></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
