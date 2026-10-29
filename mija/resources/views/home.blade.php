@extends('layouts.app')
@section('title', 'رزرو ویلا و کلبه')
@section('bodyclass', 'has-hero')
@section('bare')
{{-- ===== هیرو ===== --}}
<section class="stage" data-parallax>
  @if($hero = site_image('hero'))<img class="stage-photo" src="{{ $hero }}" alt="" fetchpriority="high">@else
  @include('partials.hero-art')
  @endif
  <div class="container stage-in">
    <span class="eyebrow">MIJA · اقامتگاه‌های منتخب ایران</span>
    <h1>جای خوب،<br><em>سفر خوب.</em></h1>
    <p class="sub">ویلا، کلبه و اقامتگاه‌های تأییدشده در سراسر ایران؛ مستقیم از میزبان، با قیمت شفاف و پرداخت امن.</p>
    <div class="trust">
      <div><b data-count="{{ $stats['properties'] }}">{{ fa($stats['properties']) }}</b><small>اقامتگاه تأییدشده</small></div>
      <div><b data-count="{{ $stats['cities'] }}">{{ fa($stats['cities']) }}</b><small>شهر و منطقه</small></div>
      @if($stats['rating'])<div><b data-count="{{ $stats['rating'] }}" data-dec="1">{{ fa($stats['rating']) }}</b><small>امتیاز مهمانان</small></div>@endif
    </div>
  </div>
</section>

<div class="container">
  <form class="searchbar stage-search" action="{{ route('search') }}" method="get" id="home-search">
    <button type="button" class="sp-pill" data-open-search><x-icon name="search"/><span><b>کجا می‌خوای بری؟</b><small>مقصد · تاریخ · تعداد نفرات</small></span><i>جستجو</i></button>
    <div class="sp-head"><b>جستجوی اقامتگاه</b><button type="button" class="btn btn-sm btn-icon btn-light" data-close-search aria-label="بستن"><x-icon name="x" class="sm"/></button></div>
    <div><label>مقصد</label><select name="province"><option value="">همه‌ی استان‌ها</option>@foreach(config('mija.provinces') as $pv => $cs)<option>{{ $pv }}</option>@endforeach</select></div>
    <div class="rp" data-rangepicker data-unavailable="[]">
      <label>ورود و خروج</label>
      <input type="hidden" name="check_in" data-in><input type="hidden" name="check_out" data-out>
      <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب تاریخ</b></div><div><small>خروج</small><b data-label-out>انتخاب تاریخ</b></div></div>
      <div class="rp-pop"></div>
    </div>
    <div><label>مسافران</label><select name="guests"><option value="">هر تعداد</option>@foreach([2,4,6,8,10,15] as $n)<option value="{{ $n }}">{{ fa($n) }} نفر و بیشتر</option>@endforeach</select></div>
    <button class="btn btn-lg"><x-icon name="search"/> جستجو</button>
    <div class="presets"><span class="muted xs">پیشنهاد سریع</span><button type="button" class="qchip" data-preset="weekend">این آخر هفته</button><button type="button" class="qchip" data-preset="weekend2">آخر هفته‌ی بعد</button><button type="button" class="qchip" data-preset="midweek">۳ شب میان‌هفته</button><button type="button" class="qchip" data-preset="soon">۳ شب از فردا</button></div>
  </form>

  {{-- ===== دسته‌بندی‌ها ===== --}}
  <section class="sec">
    <div class="sec-h"><div><span class="eyebrow-s">COLLECTIONS</span><h2>سفرت چه حال‌وهوایی دارد؟</h2></div></div>
    <div class="cats">
      @foreach([
        ['pool', 'ویلای استخردار', ['amenity' => ['pool']]], ['tiny', 'کلبه و خانه چوبی', ['type' => ['cottage', 'tiny']]], ['garden_villa', 'باغ‌ویلا', ['type' => ['garden_villa']]],
        ['sun', 'شمال کشور', ['region' => 'north']], ['pin', 'نزدیک تهران', ['region' => 'tehran']], ['view', 'ییلاقی و کوهستانی', ['region' => 'highland']],
        ['eco', 'کویر و بوم‌گردی', ['type' => ['eco']]], ['bolt', 'رزرو فوری', ['instant' => 1]], ['family', 'مناسب خانواده', ['amenity' => ['family']]],
        ['fireplace', 'با شومینه', ['amenity' => ['fireplace']]], ['suite', 'سوئیت و آپارتمان', ['type' => ['suite', 'apartment']]], ['tag', 'تخفیف‌دار', ['discount' => 1]],
      ] as [$ic, $n, $q])
        <a class="cat-big" href="{{ route('search', $q) }}"><span class="cb-ic"><x-i3 :name="$ic"/></span>{{ $n }}</a>
      @endforeach
    </div>
  </section>

  <section class="sec recent" id="recent-box" data-swiper="cards">
    <div class="sec-h"><div><span class="eyebrow-s">RECENTLY VIEWED</span><h2>ادامه‌ی جستجوی شما</h2></div></div>
    <div class="swiper"><div class="swiper-wrapper recent-list"></div></div>
  </section>

  @if($featured->count())
  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><span class="eyebrow-s">SIGNATURE</span><h2>اقامتگاه‌های منتخب</h2><p>انتخاب‌شده توسط تیم می‌جا</p></div>
      <div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($featured as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
  </section>
  @endif

  {{-- ===== مقصدها ===== --}}
  <section class="sec">
    <div class="sec-h"><div><span class="eyebrow-s">DESTINATIONS</span><h2>مقصدهای محبوب</h2></div></div>
    <div class="bento">
      @foreach(config('mija.regions') as $k => $rg)
        @php $ic = ['north' => 'leaf', 'tehran' => 'pin', 'highland' => 'mountain', 'desert' => 'sun', 'south' => 'pool'][$k]; @endphp
        <a class="bt bt-{{ $k }}" href="{{ route('search', ['region' => $k]) }}">
          <x-icon :name="$ic" class="bt-ic"/>
          <div class="bt-t"><b>{{ $rg['name'] }}</b><small>{{ $rg['sub'] }}</small><span class="bt-n">{{ fa($regionCounts[$k] ?? 0) }} اقامتگاه <x-icon name="arrow-l" class="sm"/></span></div>
        </a>
      @endforeach
    </div>
  </section>

  @if($discounted->count())
  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><span class="eyebrow-s">OFFERS</span><h2>اقامت‌های به‌صرفه</h2><p>تخفیف هفتگی و ماهانه برای اقامت‌های طولانی‌تر</p></div>
      <div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($discounted as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
  </section>
  @endif

  @if($instant->count())
  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><span class="eyebrow-s">INSTANT BOOK</span><h2>رزرو فوری، بدون انتظار</h2></div>
      <div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($instant as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
  </section>
  @endif

  {{-- ===== سه قدم ===== --}}
  <section class="sec">
    <div class="sec-h"><div><span class="eyebrow-s">HOW IT WORKS</span><h2>رزرو در سه قدم</h2></div></div>
    <div class="steps-3">
      <div class="step"><span class="no">۰۱</span><b>جستجو و انتخاب</b><p>مقصد و تاریخ را انتخاب کن و بین اقامتگاه‌های تأییدشده مقایسه کن.</p></div>
      <div class="step"><span class="no">۰۲</span><b>رزرو و پرداخت امن</b><p>رزرو فوری یا درخواست به میزبان؛ پرداخت آنلاین با ضمانت بازگشت وجه.</p></div>
      <div class="step"><span class="no">۰۳</span><b>سفر</b><p>آدرس و راهنمای ورود را دریافت کن و از اقامتت لذت ببر.</p></div>
    </div>
  </section>

  @if($top->count())
  <section class="sec"><div class="sec-h"><div><span class="eyebrow-s">TOP RATED</span><h2>بالاترین امتیاز</h2></div></div>
    <div class="grid-cards">@foreach($top as $p)@include('partials.pcard')@endforeach</div></section>
  @endif

  <section class="sec">
    <div class="sec-h"><div><span class="eyebrow-s">WHY MIJA</span><h2>چرا می‌جا؟</h2></div></div>
    <div class="feat">
      <div class="card"><div class="ico"><x-icon name="shield"/></div><b>پرداخت امن</b><p class="small muted">وجه تا روز ورود نزد می‌جا می‌ماند و در صورت لغو طبق قوانین بازمی‌گردد.</p></div>
      <div class="card"><div class="ico"><x-icon name="bolt"/></div><b>رزرو فوری</b><p class="small muted">اقامتگاه‌های رزرو فوری بدون انتظار برای تأیید میزبان قطعی می‌شوند.</p></div>
      <div class="card"><div class="ico"><x-icon name="tag"/></div><b>قیمت شفاف</b><p class="small muted">قیمت نهایی با همه‌ی هزینه‌ها پیش از پرداخت نمایش داده می‌شود.</p></div>
      <div class="card"><div class="ico"><x-icon name="star"/></div><b>نظرات واقعی</b><p class="small muted">فقط مهمان‌هایی که واقعاً اقامت داشته‌اند می‌توانند نظر بگذارند.</p></div>
    </div>
  </section>

  @if($reviews->count())
  <section class="sec" data-swiper="testi">
    <div class="sec-h"><div><span class="eyebrow-s">REVIEWS</span><h2>مهمان‌ها چه می‌گویند</h2></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($reviews as $rv)
      <div class="swiper-slide"><div class="card testi"><x-icon name="quote" class="lg" /><p style="margin:10px 0 18px">{{ \Illuminate\Support\Str::limit($rv->comment, 170) }}</p><div class="row"><img class="avatar sm" src="{{ $rv->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $rv->guest->name }}</b><div class="xs muted">{{ $rv->property->title }}</div></div><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span></div></div></div>
    @endforeach</div></div>
  </section>
  @endif

  <section class="sec" style="max-width:860px;margin-inline:auto">
    <div class="sec-h"><div><span class="eyebrow-s">FAQ</span><h2>پرسش‌های متداول</h2></div></div>
    <div class="faq">
      <details><summary>رزرو فوری با رزرو درخواستی چه فرقی دارد؟</summary><p>در رزرو فوری بلافاصله پرداخت می‌کنید و رزرو قطعی می‌شود. در رزرو درخواستی ابتدا میزبان باید تأیید کند و سپس پرداخت انجام می‌شود.</p></details>
      <details><summary>اگر رزروم را لغو کنم چه مقدار پول برمی‌گردد؟</summary><p>بسته به سیاست لغو اقامتگاه (منعطف، متوسط یا سخت‌گیرانه) و زمان لغو، بخشی یا کل مبلغ به کیف پول شما بازمی‌گردد. جزئیات در صفحه‌ی <a href="{{ route('page', 'rules') }}" style="color:var(--brand-d);font-weight:600">قوانین</a> آمده است.</p></details>
      <details><summary>پول من کی به دست میزبان می‌رسد؟</summary><p>وجه تا روز ورود نزد می‌جا می‌ماند و پس از ورود مهمان، به کیف پول میزبان واریز می‌شود.</p></details>
      <details><summary>چطور اقامتگاهم را ثبت کنم؟</summary><p>با حساب «میزبان» ثبت‌نام کنید، اطلاعات و عکس‌ها را وارد کنید و برای بررسی ارسال کنید. ثبت کاملاً رایگان است.</p></details>
    </div>
  </section>

  <section class="sec">
    <div class="cta"><div class="row wrap between" style="gap:24px">
      <div style="max-width:560px"><h2>ملک دارید؟ میزبان می‌جا شوید.</h2><p>اقامتگاه را رایگان ثبت کنید، تقویم و قیمت را خودتان مدیریت کنید و درآمد را مستقیم به حساب بانکی‌تان بگیرید.</p></div>
      <a class="btn btn-gold btn-lg" href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه <x-icon name="arrow-l"/></a>
    </div></div>
  </section>
</div>
@endsection
