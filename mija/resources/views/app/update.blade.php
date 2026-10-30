@extends('layouts.app')
@section('title', 'به‌روزرسانی اپلیکیشن می‌جا')
@section('bodyclass', 'ap-body')
@push('head')
<meta name="robots" content="noindex,follow">
<link rel="stylesheet" href="{{ asset('css/appdl.css') }}?v={{ filemtime(public_path('css/appdl.css')) }}">
@endpush
@section('bare')
@php
  $has = (bool) $latest;
  $ver = $latest?->version_name;
  $size = $latest?->direct_size ? fa(number_format($latest->direct_size / 1048576, 1)).' مگابایت' : '';
@endphp
<div class="ap up" id="up" data-state="unknown" data-mode="direct" data-latest="{{ $latest?->version_code ?? 0 }}" data-latest-name="{{ $ver }}" data-force="{{ $latest?->force ? 1 : 0 }}" data-cur="{{ $client['code'] ?? $cur }}" data-src="{{ $client['flavor'] ?? $src }}" data-url="{{ route('app') }}">
  <div class="container up-wrap">
    <section class="up-hero glass">
      <div class="up-ring" aria-hidden="true">
        <svg viewBox="0 0 120 120"><circle class="rg-bg" cx="60" cy="60" r="52"/><circle class="rg-fg" cx="60" cy="60" r="52"/></svg>
        <img src="{{ asset('img/mija-mark.png') }}" alt="" width="52" height="60">
        <span class="up-badge up-ok"><x-icon name="check"/></span>
        <span class="up-badge up-new"><x-icon name="download"/></span>
      </div>

      <div class="up-txt">
        <div class="st st-uptodate"><h1>آخرین نسخه را دارید</h1><p>نسخه‌ی <b data-cur-name><bdi dir="ltr">{{ fa($ver) }}</bdi></b> نصب است و همه‌چیز به‌روز است.</p></div>
        <div class="st st-available"><h1>نسخه‌ی جدید آماده است</h1><p class="vflow"><span class="vchip old" data-cur-name>—</span><span class="arrow">←</span><span class="vchip new"><bdi dir="ltr">{{ fa($ver) }}</bdi></span></p></div>
        <div class="st st-force"><h1>به‌روزرسانی ضروری</h1><p>برای ادامه‌ی استفاده، نسخه‌ی <b><bdi dir="ltr">{{ fa($ver) }}</bdi></b> لازم است. <span class="vflow"><span class="vchip old" data-cur-name>—</span><span class="arrow">←</span><span class="vchip new"><bdi dir="ltr">{{ fa($ver) }}</bdi></span></span></p></div>
        <div class="st st-unknown"><h1>آخرین نسخه: <bdi dir="ltr">{{ fa($ver) }}</bdi></h1><p>اگر قبلاً نصب کرده‌اید، نسخه‌ی جدید روی همان نصب می‌شود و اطلاعات و رزروهایتان حفظ می‌ماند.</p></div>
        <div class="st st-none"><h1>هنوز نسخه‌ای منتشر نشده</h1><p>به‌زودی اینجا نسخه‌ی اپلیکیشن قرار می‌گیرد.</p></div>
      </div>

      @if($has)
      <div class="up-actions">
        {{-- حالت مستقیم: دانلود از سایت --}}
        <div class="by-direct">
          <a class="ap-btn ap-main" id="up-go" href="{{ route('app.download') }}" data-dl rel="nofollow"><span class="ap-ic"><x-icon name="download"/></span><span class="ap-bt"><b>دانلود و نصب نسخه‌ی <bdi dir="ltr">{{ fa($ver) }}</bdi></b><small>{{ $size }} · روی نسخه‌ی فعلی نصب می‌شود</small></span></a>
        </div>
        {{-- حالت فروشگاهی: بازار یا مایکت --}}
        <div class="by-store">
          <div class="ap-stores big">
            @if($bazaar)<a class="ap-btn ap-main" href="{{ $bazaar }}" target="_blank" rel="noopener"><span class="ap-bt"><small>به‌روزرسانی در</small><b>کافه‌بازار</b></span></a>@endif
            @if($myket)<a class="ap-btn ap-main alt" href="{{ $myket }}" target="_blank" rel="noopener"><span class="ap-bt"><small>به‌روزرسانی در</small><b>مایکت</b></span></a>@endif
            @if(! $bazaar && ! $myket)<a class="ap-btn ap-main" href="{{ route('app.download') }}" data-dl rel="nofollow"><span class="ap-ic"><x-icon name="download"/></span><span class="ap-bt"><b>دانلود مستقیم</b><small>{{ $size }}</small></span></a>@endif
          </div>
          @if($bazaar || $myket)<p class="xs muted">اپ شما از فروشگاه نصب شده؛ به‌روزرسانی هم از همان فروشگاه انجام می‌شود.</p>@endif
        </div>
        <button class="ap-btn ap-store only-inapp" type="button" id="chk"><span class="ap-bt"><b>بررسی دوباره</b></span></button>
      </div>
      @endif
    </section>

    @if($has && ($latest->changelog ?? []))
    <section class="up-sec">
      <h2 class="ap-h">در نسخه‌ی <bdi dir="ltr">{{ fa($ver) }}</bdi> چه خبر است؟</h2>
      <ul class="up-notes glass">@foreach($latest->changelog as $c)<li><x-icon name="check" class="sm"/>{{ $c }}</li>@endforeach</ul>
    </section>
    @endif

    <section class="up-sec">
      <h2 class="ap-h">به‌روزرسانی هوشمند می‌جا</h2>
      <div class="ap-feats three">
        <div class="ap-feat glass"><i><x-i3 name="p_compass"/></i><b>منبع نصب را می‌شناسد</b><p>بازار، مایکت یا سایت؛ هر کدام که نصب کرده‌اید، همان مسیر را پیشنهاد می‌دهد.</p></div>
        <div class="ap-feat glass"><i><x-i3 name="p_shield"/></i><b>دانلود امن</b><p>در نسخه‌ی مستقیم، فایل با اثر انگشت SHA-256 و امضای می‌جا بررسی می‌شود.</p></div>
        <div class="ap-feat glass"><i><x-i3 name="p_lock"/></i><b>اطلاعات حفظ می‌شود</b><p>حساب، رزروها و تنظیمات شما با به‌روزرسانی هیچ تغییری نمی‌کند.</p></div>
      </div>
    </section>

    @if($history->count())
    <section class="up-sec">
      <h2 class="ap-h">تاریخچه‌ی نسخه‌ها</h2>
      <div class="up-tl">
        @foreach($history as $i => $r)
        <details class="glass" @if($i === 0) open @endif>
          <summary><span class="dot"></span><b>نسخه‌ی <bdi dir="ltr">{{ fa($r->version_name) }}</bdi></b>@if($i === 0)<em>آخرین</em>@endif @if($r->force)<em class="red">ضروری</em>@endif<span class="dt">{{ jdate($r->created_at, 'j F Y') }}</span></summary>
          <ul>@forelse(($r->changelog ?? []) as $c)<li>{{ $c }}</li>@empty<li>بهبود پایداری و کارایی</li>@endforelse</ul>
        </details>
        @endforeach
      </div>
    </section>
    @endif

    <p class="up-back"><a class="ap-link" href="{{ route('app') }}">← صفحه‌ی دانلود اپلیکیشن</a></p>
  </div>
</div>
@include('app._sheet')
<script src="{{ asset('js/appdl.js') }}?v={{ filemtime(public_path('js/appdl.js')) }}"></script>
@endsection
