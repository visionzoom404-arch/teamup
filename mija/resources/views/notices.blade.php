@extends('layouts.panel')
@section('title', 'اعلان‌ها')
@section('content')
<div class="page-h"><div><h1>اعلان‌ها</h1><div class="muted">رویدادهای مهم حساب شما</div></div>
  <button type="button" class="btn btn-sm btn-light" id="enable-browser-notify" hidden><x-icon name="bell" class="sm"/> فعال‌سازی اعلان مرورگر</button></div>
@forelse($items as $n)
  <a href="{{ $n->url ?: '#' }}" class="item-row ntc {{ $n->read_at ? '' : 'new' }}"><span class="ntc-ic {{ $n->kind }}"><x-icon name="{{ $n->kind === 'admin' ? 'shield' : ($n->kind === 'promo' ? 'megaphone' : 'bell') }}"/></span><div class="grow"><b>{{ $n->title }}</b>@if($n->kind === 'admin')<span class="badge dark" style="margin-inline-start:6px">مدیریتی</span>@elseif($n->kind === 'promo')<span class="badge gold" style="margin-inline-start:6px">پیام می‌جا</span>@endif @if($n->body)<div class="small muted">{{ $n->body }}</div>@endif<div class="xs muted">{{ jdate($n->created_at, 'j F Y · H:i') }}</div></div>@unless($n->read_at)<span class="dot-new" aria-label="خوانده‌نشده"></span>@endunless</a>
@empty
  <div class="card empty"><x-icon name="bell"/><p>اعلانی ندارید.</p></div>
@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
