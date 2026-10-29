@extends('layouts.app')
@section('title', 'رزرو آنلاین ویلا و کلبه')
@push('head')<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">@endpush
@section('bare')
<div class="container">
  {{-- ===== قهرمان ===== --}}
  <section class="stage" data-parallax>
    @include('partials.auth-scene', ['bare' => true])
    <div class="stage-in">
      <span class="glass-dark badge-g" style="margin-bottom:16px"><x-i3 name="sun" class="sm"/> اقامتی به‌یادماندنی، مستقیم از میزبان</span>
      <h1>همین امروز <span class="rotor"><span>ویلا</span><span>کلبه</span><span>باغ‌ویلا</span><span>سوئیت</span></span> رویایی‌ات را رزرو کن</h1>
      <p class="sub">اقامتگاه‌های تأییدشده در شمال، جنگل، کوهستان و کویر؛ با قیمت شفاف، پرداخت امن و ضمانت بازگشت وجه.</p>
      <div class="trust">
        <div><span class="t-i"><x-icon name="home"/></span><span><b data-count="{{ max($stats['properties'], 0) }}">{{ fa($stats['properties']) }}</b><small>اقامتگاه تأییدشده</small></span></div>
        <div><span class="t-i"><x-icon name="pin"/></span><span><b data-count="{{ $stats['cities'] }}">{{ fa($stats['cities']) }}</b><small>شهر و منطقه</small></span></div>
        <div><span class="t-i"><x-icon name="users"/></span><span><b data-count="{{ $stats['guests'] }}">{{ fa($stats['guests']) }}</b><small>مهمان خوشحال</small></span></div>
        @if($stats['rating'])<div><span class="t-i"><x-icon name="star"/></span><span><b data-count="{{ $stats['rating'] }}" data-dec="1">{{ fa($stats['rating']) }}</b><small>میانگین امتیاز</small></span></div>@endif
      </div>
    </div>

    <div class="chip chip-a glass-dark"><x-i3 name="bolt" class="lg"/><div><b>رزرو فوری</b><small>بدون انتظار تأیید</small></div></div>
    <div class="chip chip-b glass-dark"><x-i3 name="shield" class="lg"/><div><b>پرداخت امن</b><small>ضمانت بازگشت وجه</small></div></div>

  </section>

    <form class="searchbar glass stage-search" action="{{ route('search') }}" method="get">
      <div><label>مقصد</label><select name="province"><option value="">همه‌ی استان‌ها</option>@foreach(config('mija.provinces') as $pv => $cs)<option>{{ $pv }}</option>@endforeach</select></div>
      <div class="rp" data-rangepicker data-unavailable="[]">
        <label>تاریخ ورود و خروج</label>
        <input type="hidden" name="check_in" data-in><input type="hidden" name="check_out" data-out>
        <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب تاریخ</b></div><div><small>خروج</small><b data-label-out>انتخاب تاریخ</b></div></div>
        <div class="rp-pop glass"></div>
      </div>
      <div><label>تعداد مسافر</label><select name="guests"><option value="">هر تعداد</option>@foreach([2,4,6,8,10,15] as $n)<option value="{{ $n }}">{{ fa($n) }} نفر و بیشتر</option>@endforeach</select></div>
      <button class="btn btn-lg pulse"><x-icon name="search"/> جستجو</button>
      <div class="presets"><span class="muted xs">پیشنهاد سریع:</span><button type="button" class="qchip" data-preset="weekend">این آخر هفته</button><button type="button" class="qchip" data-preset="weekend2">آخر هفته‌ی بعد</button><button type="button" class="qchip" data-preset="midweek">۳ شب میان‌هفته</button><button type="button" class="qchip" data-preset="soon">۳ شب از فردا</button></div>
    </form>

  {{-- ===== دسته‌ها ===== --}}
  <div data-swiper="cats" style="margin-top:26px">
    <div class="swiper"><div class="swiper-wrapper">
      @foreach([['villa','ویلا'],['cottage','کلبه'],['garden_villa','ویلا باغ'],['tiny','خانه چوبی'],['eco','بوم‌گردی'],['suite','سوئیت'],['farm','مزرعه و باغ'],['apartment','آپارتمان']] as [$k,$n])
        <a class="cat swiper-slide" style="width:96px" href="{{ route('search', ['type' => [$k]]) }}"><x-i3 :name="config('mija.type_icons.'.$k)" class="lg"/>{{ $n }}</a>
      @endforeach
    </div></div>
  </div>

  {{-- ===== بازدیدهای اخیر (از مرورگر) ===== --}}
  <section class="sec recent" id="recent-box" data-swiper="cards">
    <div class="sec-h"><div><h2>ادامه‌ی جستجوی شما</h2><p>اقامتگاه‌هایی که اخیراً دیده‌اید</p></div></div>
    <div class="swiper"><div class="swiper-wrapper recent-list"></div></div>
  </section>

  {{-- ===== ویژه ===== --}}
  @if($featured->count())
  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><h2>اقامتگاه‌های <span class="grad-text">ویژه</span></h2><p>انتخاب‌شده توسط تیم می‌جا</p></div>
      <div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($featured as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
  </section>
  @endif

  {{-- ===== چطور کار می‌کند ===== --}}
  <section class="sec">
    <div class="sec-h"><div><h2>رزرو در <span class="grad-text">سه قدم</span></h2><p>ساده، سریع و بدون دردسر</p></div></div>
    <div class="steps-3">
      <div class="step"><div class="no"><x-i3 name="search" class="xl"/><span class="n">۱</span></div><b>جستجو و انتخاب</b><p class="small muted">مقصد و تاریخ را بزن و بین اقامتگاه‌های تأییدشده انتخاب کن.</p></div>
      <div class="step"><div class="no"><x-i3 name="bolt" class="xl"/><span class="n">۲</span></div><b>رزرو و پرداخت امن</b><p class="small muted">رزرو فوری یا درخواست به میزبان؛ پرداخت آنلاین با ضمانت.</p></div>
      <div class="step"><div class="no"><x-i3 name="home" class="xl"/><span class="n">۳</span></div><b>سفر و لذت</b><p class="small muted">آدرس و تماس میزبان را بگیر و از اقامتت لذت ببر.</p></div>
    </div>
  </section>

  @if($destinations->count())
  <section class="sec">
    <div class="sec-h"><div><h2>مقصدهای محبوب</h2><p>پرطرفدارترین شهرها برای سفر</p></div></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px">
      @foreach($destinations as $i => $d)
        <a class="dest" href="{{ route('search', ['city' => $d->city]) }}">
          <img class="bgart" src="{{ asset('storage/seed/art-'.($i % 4).'.svg') }}" alt="" loading="lazy" style="object-fit:cover;width:100%;height:100%" onerror="this.style.display='none'">
          <div><b>{{ $d->city }}</b><small>{{ $d->province }} · {{ fa($d->c) }} اقامتگاه</small></div>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><h2>تازه‌ترین اقامتگاه‌ها</h2><p>تازه به می‌جا پیوسته‌اند</p></div><div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($latest as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
    @if(! $latest->count())<div class="card empty"><x-icon name="home"/><p>هنوز اقامتگاهی ثبت نشده است.</p></div>@endif
  </section>

  @if($top->count())
  <section class="sec"><div class="sec-h"><div><h2>بالاترین امتیاز <x-i3 name="star" class="h-i3"/></h2><p>محبوب‌ترین‌ها بین مهمانان</p></div></div>
    <div class="grid-cards">@foreach($top as $p)@include('partials.pcard')@endforeach</div></section>
  @endif

  {{-- ===== چرا می‌جا ===== --}}
  <section class="sec">
    <div class="sec-h"><div><h2>چرا <span class="grad-text">می‌جا</span>؟</h2></div></div>
    <div class="feat">
      <div class="card"><div class="ico"><x-icon name="shield" class="lg"/></div><b>پرداخت امن و ضمانت</b><p class="small muted">وجه تا روز ورود نزد می‌جا می‌ماند و در صورت لغو طبق قوانین بازگردانده می‌شود.</p></div>
      <div class="card"><div class="ico blue"><x-icon name="bolt" class="lg"/></div><b>رزرو فوری</b><p class="small muted">اقامتگاه‌های رزرو فوری بدون انتظار برای تأیید میزبان، همان لحظه قطعی می‌شوند.</p></div>
      <div class="card"><div class="ico green"><x-icon name="tag" class="lg"/></div><b>قیمت شفاف</b><p class="small muted">قیمت نهایی شامل همه‌ی هزینه‌ها پیش از پرداخت نمایش داده می‌شود.</p></div>
      <div class="card"><div class="ico dark"><x-icon name="star" class="lg"/></div><b>نظرات واقعی</b><p class="small muted">فقط مهمانانی که اقامت داشته‌اند می‌توانند نظر ثبت کنند.</p></div>
    </div>
  </section>

  @if($reviews->count())
  <section class="sec" data-swiper="testi">
    <div class="sec-h"><div><h2>مهمانان چه می‌گویند</h2><p>نظرات واقعی از اقامت‌های انجام‌شده</p></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($reviews as $rv)
      <div class="swiper-slide"><div class="card testi"><div class="row"><img class="avatar sm" src="{{ $rv->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $rv->guest->name }}</b><div class="xs muted">{{ $rv->property->title }}</div></div><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span></div><p style="margin:12px 0 0">«{{ \Illuminate\Support\Str::limit($rv->comment, 160) }}»</p></div></div>
    @endforeach</div></div>
  </section>
  @endif

  {{-- ===== نصب اپ ===== --}}
  <section class="sec">
    <div class="cta"><div class="row wrap between" style="gap:22px">
      <div class="row" style="gap:18px"><img src="{{ asset('icons/icon-192.png') }}" width="84" height="84" alt="" style="border-radius:24px;box-shadow:0 18px 34px rgba(0,0,0,.4);animation:logoFloat 4s ease-in-out infinite"><div><h2 style="color:#fff;margin-bottom:4px">اپلیکیشن <span style="color:var(--gold-l)">می‌جا</span> را نصب کن</h2><p style="opacity:.85;margin:0">سریع‌تر، بدون نیاز به مرورگر؛ مستقیم از صفحه‌ی اصلی گوشی.</p></div></div>
      <button class="btn btn-gold btn-lg pulse" data-install><x-icon name="download"/> نصب اپلیکیشن</button>
    </div></div>
  </section>

  {{-- ===== پرسش‌های متداول ===== --}}
  <section class="sec" style="max-width:860px;margin-inline:auto">
    <div class="sec-h"><div><h2>پرسش‌های متداول</h2></div></div>
    <div class="faq">
      <details><summary>رزرو فوری با رزرو درخواستی چه فرقی دارد؟</summary><p>در رزرو فوری بلافاصله پرداخت می‌کنید و رزرو قطعی می‌شود. در رزرو درخواستی ابتدا میزبان باید درخواست را تأیید کند و سپس پرداخت انجام می‌شود.</p></details>
      <details><summary>اگر رزروم را لغو کنم چه مقدار پول برمی‌گردد؟</summary><p>بسته به سیاست لغو اقامتگاه (منعطف، متوسط یا سخت‌گیرانه) و زمان لغو، بخشی یا کل مبلغ به کیف پول شما بازمی‌گردد. جزئیات در صفحه‌ی <a href="{{ route('page', 'rules') }}" style="color:var(--red-d);font-weight:700">قوانین</a> آمده است.</p></details>
      <details><summary>پول من کی به دست میزبان می‌رسد؟</summary><p>وجه تا روز ورود نزد می‌جا می‌ماند و پس از ورود مهمان، به کیف پول میزبان واریز می‌شود.</p></details>
      <details><summary>چطور اقامتگاهم را ثبت کنم؟</summary><p>با حساب «میزبان» ثبت‌نام کنید، اطلاعات و عکس‌ها را وارد کنید و برای بررسی ارسال کنید. ثبت کاملاً رایگان است.</p></details>
    </div>
  </section>

  <section class="sec">
    <div class="cta">
      <div class="row wrap between" style="gap:20px">
        <div style="max-width:560px"><h2 style="color:#fff">ملک داری؟ <span style="color:var(--gold-l)">میزبان می‌جا شو</span></h2><p style="opacity:.85;margin:0">اقامتگاهت را رایگان ثبت کن، تقویم و قیمت‌ها را خودت مدیریت کن و درآمدت را مستقیم به حساب بانکی‌ات بگیر.</p></div>
        <a class="btn btn-gold btn-lg" href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه <x-icon name="chev-l"/></a>
      </div>
    </div>
  </section>
</div>
@endsection
