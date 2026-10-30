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
        {{-- دانلود مستقیم: همیشه در دسترس (به‌جز اپِ نسخه‌ی فروشگاهی که از خود فروشگاه به‌روز می‌شود) --}}
        <a class="ap-btn ap-main up-dl-main" id="up-dl" href="{{ route('app.download') }}" data-dl rel="nofollow">
          <span class="ap-ic"><x-icon name="download"/></span>
          <span class="ap-bt"><b><span class="lbl-new">دانلود مستقیم نسخه‌ی</span><span class="lbl-same">دانلود دوباره‌ی نسخه‌ی</span> <bdi dir="ltr">{{ fa($ver) }}</bdi></b><small>شماره‌ی ساخت <bdi dir="ltr">{{ fa($latest->version_code) }}</bdi> · {{ $size }} · فایل APK</small></span>
        </a>
        <div class="ap-stores up-stores">
          @if($bazaar)<a class="ap-btn ap-store" href="{{ $bazaar }}" target="_blank" rel="noopener"><span class="ap-bt"><small>به‌روزرسانی در</small><b>کافه‌بازار</b></span></a>@else<span class="ap-btn ap-store off"><span class="ap-bt"><small>به‌زودی در</small><b>کافه‌بازار</b></span></span>@endif
          @if($myket)<a class="ap-btn ap-store" href="{{ $myket }}" target="_blank" rel="noopener"><span class="ap-bt"><small>به‌روزرسانی در</small><b>مایکت</b></span></a>@else<span class="ap-btn ap-store off"><span class="ap-bt"><small>به‌زودی در</small><b>مایکت</b></span></span>@endif
        </div>
        {{-- فقط داخل خود اپ: به‌روزرسانی بومی (مستقیم یا از فروشگاه، بر اساس منبع نصب) --}}
        <button class="ap-btn ap-main up-native" type="button" id="up-go"><span class="ap-ic"><x-icon name="bolt"/></span><span class="ap-bt"><b>به‌روزرسانی داخل برنامه</b><small>همین حالا، بدون خروج از می‌جا</small></span></button>
        <button class="ap-btn ap-store only-inapp" type="button" id="chk"><span class="ap-bt"><b>بررسی دوباره‌ی نسخه</b></span></button>
      </div>
      @endif
    </section>

    @if($has)
    <section class="up-sec">
      <h2 class="ap-h">مشخصات نسخه‌ها</h2>
      <div class="up-ver glass">
        <div><small>نسخه‌ی نصب‌شده روی گوشی شما</small><b><span data-cur-name>—</span></b><em data-cur-code></em></div>
        <div><small>آخرین نسخه‌ی منتشرشده</small><b><bdi dir="ltr">{{ fa($ver) }}</bdi></b><em>شماره‌ی ساخت <bdi dir="ltr">{{ fa($latest->version_code) }}</bdi></em></div>
        <div><small>حجم فایل</small><b>{{ $size }}</b><em>{{ jdate($latest->created_at, 'j F Y') }}</em></div>
        <div><small>حداقل اندروید</small><b>۵٫۰</b><em>اندروید ۵ به بالا</em></div>
        <div class="wide"><small>اثر انگشت SHA-256 فایل مستقیم</small><code id="sha" dir="ltr">{{ $latest->direct_sha256 }}</code></div>
      </div>
    </section>
    @endif

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
          <summary><span class="dot"></span><b>نسخه‌ی <bdi dir="ltr">{{ fa($r->version_name) }}</bdi></b><span class="bc">ساخت <bdi dir="ltr">{{ fa($r->version_code) }}</bdi></span>@if($i === 0)<em>آخرین</em>@endif @if($r->force)<em class="red">ضروری</em>@endif<span class="dt">{{ jdate($r->created_at, 'j F Y') }}</span></summary>
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
