@extends('layouts.app')
@section('title', 'تماس با ما')
@section('content')
@php $phones = contact_lines('contact_phones'); $addrs = contact_lines('contact_addresses'); $mail = setting('support_email'); @endphp
<div class="container page-doc">
  <article class="doc card">
    <h1>{{ $page->title }}</h1>
    <div class="doc-body">{!! $page->body !!}</div>
    <div class="ct-grid">
      @foreach($phones as [$t, $n])
      <a class="ct-card" href="tel:{{ preg_replace('/[^\d+]/', '', \App\Support\Jalali::latinDigits($n)) }}"><i><x-i3 name="p_phone"/></i><div><small>{{ $t ?: 'تلفن' }}</small><b class="ltr">{{ fa($n) }}</b></div><span class="ct-go">تماس</span></a>
      @endforeach
      @foreach($addrs as [$t, $a])
      <a class="ct-card" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($a) }}"><i><x-i3 name="p_pin"/></i><div><small>{{ $t ?: 'آدرس' }}</small><b>{{ $a }}</b></div><span class="ct-go">مسیریابی</span></a>
      @endforeach
      @if($mail)<a class="ct-card" href="mailto:{{ $mail }}"><i><x-i3 name="p_chat"/></i><div><small>ایمیل</small><b class="ltr">{{ $mail }}</b></div><span class="ct-go">ارسال</span></a>@endif
    </div>
    <div class="doc-links">
      @foreach(\App\Support\DefaultPages::SLUGS as $sl => $tt)@if($sl !== $page->slug)<a href="{{ route('page', $sl) }}">{{ $tt }}</a>@endif @endforeach
    </div>
  </article>
</div>
@endsection
