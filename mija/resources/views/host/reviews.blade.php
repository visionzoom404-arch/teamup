@extends('layouts.panel')
@section('title', 'نظرات')
@section('content')
<div class="page-h"><h1>نظرات مهمانان</h1></div>
@forelse($items as $r)
<div class="card mb">
  <div class="row"><img class="avatar sm" src="{{ $r->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $r->guest->name }}</b> <span class="muted small">درباره‌ی {{ $r->property->title }}</span><div class="xs muted">{{ jdate($r->created_at, 'j F Y') }}</div></div><span class="stars"><x-icon name="star"/>{{ fa($r->rating) }}</span></div>
  <p style="margin:10px 0">{{ $r->comment ?: '—' }}</p>
  @if($r->host_reply)<div class="alert info" style="margin:0"><b>پاسخ شما:</b>&nbsp;{{ $r->host_reply }}</div>
  @else<form method="post" action="{{ route('host.reviews.reply', $r) }}" class="row">@csrf<input class="input" name="host_reply" placeholder="پاسخ به مهمان…" required><button class="btn btn-sm">ارسال</button></form>@endif
</div>
@empty<div class="card empty"><x-icon name="star"/><p>هنوز نظری دریافت نکرده‌اید.</p></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
