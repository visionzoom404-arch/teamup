@extends('layouts.app')
@section('title', 'رزرو ویلا و کلبه در شمال و سراسر ایران')
@section('desc', 'می‌جا؛ رزرو آنلاین ویلا، کلبه چوبی، ویلا استخردار، سوئیت و اقامتگاه بوم‌گردی در شمال، نزدیک تهران و سراسر ایران. مستقیم از میزبان، قیمت شفاف، پرداخت امن و رزرو فوری. جای خوب، سفر خوب.')
@section('keywords', 'رزرو ویلا, اجاره ویلا, رزرو کلبه, اجاره کلبه, ویلا شمال, اجاره ویلا در شمال, کلبه جنگلی, ویلا استخردار, اجاره روزانه ویلا, ویلا لاکچری, رزرو آنلاین ویلا, می‌جا, mija.click')
@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect([['رزرو فوری با رزرو درخواستی چه فرقی دارد؟', 'در رزرو فوری بلافاصله پرداخت می‌کنید و رزرو قطعی می‌شود. در رزرو درخواستی، میزبان ۳۰ دقیقه فرصت دارد درخواست را تأیید کند و سپس پرداخت انجام می‌شود.'], ['اگر رزروم را لغو کنم چه مقدار پول برمی‌گردد؟', 'بسته به سیاست لغو اقامتگاه (منعطف، متوسط، سخت‌گیرانه) و زمان لغو، بخشی یا کل مبلغ به کیف پول شما بازمی‌گردد.'], ['آدرس دقیق ویلا کی نمایش داده می‌شود؟', 'آدرس دقیق، نقشه و شماره‌ی میزبان پس از پرداخت و قطعی شدن رزرو نمایش داده می‌شود.'], ['چطور اقامتگاهم را در می‌جا ثبت کنم؟', 'با حساب میزبان ثبت‌نام کنید، اطلاعات هویتی و بانکی را وارد کنید، عکس‌های واقعی ویلا را بارگذاری کنید و برای بررسی ارسال کنید.']])->map(fn ($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]])->all()], JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@section('bare')
<div class="container">
  {{-- ===== بالای صفحه: عنوان فشرده + جستجو ===== --}}
  <section class="hero-c">
    <div class="hx-wrap">
    <div class="hx">
      @if(setting('home_hero'))<img class="hx-img hx-day" src="{{ asset('storage/'.setting('home_hero')) }}" alt="اجاره ویلا و کلبه در سراسر ایران" width="1920" height="1080" fetchpriority="high">@else<picture class="hx-day"><source srcset="{{ asset('img/hero-home.webp') }}" type="image/webp"><img class="hx-img" data-fx="0.8217,0.435" src="{{ asset('img/hero-home.jpg') }}" alt="ویلای ساحلی و کوهستانی هنگام غروب" width="1916" height="821" fetchpriority="high"></picture>@endif
      @if(setting('home_hero_night'))<img class="hx-img hx-night" src="{{ asset('storage/'.setting('home_hero_night')) }}" alt="" width="1920" height="1080" loading="lazy" aria-hidden="true">@else<picture class="hx-night" aria-hidden="true"><source srcset="{{ asset('img/hero-night.webp') }}" type="image/webp"><img class="hx-img" data-fx="0.8247,0.3686" src="{{ asset('img/hero-night.jpg') }}" alt="" width="1774" height="887" loading="lazy"></picture>@endif
      <div class="hx-in">
        <span class="kicker"><x-icon name="sparkle" class="sm"/> رزرو مستقیم از میزبان، بدون واسطه‌ی پنهان</span>
        <h1>کجا می‌خوای بری؟</h1>
        <p>ویلا، کلبه و اقامتگاه‌های تأییدشده با قیمت شفاف و پرداخت امن؛ همه‌جا از شمال تا جنوب.</p>
        <div class="hx-stats">
          <span><b data-count="{{ $stats['properties'] }}">{{ fa($stats['properties']) }}</b> اقامتگاه</span>
          <span><b data-count="{{ $stats['cities'] }}">{{ fa($stats['cities']) }}</b> شهر و منطقه</span>
          @if($stats['rating'])<span><b data-count="{{ $stats['rating'] }}" data-dec="1">{{ fa($stats['rating']) }}</b> امتیاز مهمانان</span>@endif
        </div>
      </div>
    </div>

    <form class="searchbar stage-search" action="{{ route('search') }}" method="get" id="home-search">
      <button type="button" class="sp-pill" data-open-search><x-icon name="search"/><span><b>جستجوی اقامتگاه</b><small>مقصد · تاریخ · تعداد نفرات</small></span><i>جستجو</i></button>
      <div class="sp-head"><b>جستجوی اقامتگاه</b><button type="button" class="btn btn-sm btn-icon btn-light" data-close-search aria-label="بستن"><x-icon name="x" class="sm"/></button></div>
      <div><label>مقصد</label><select name="province"><option value="">همه‌ی استان‌ها</option>@foreach(config('mija.provinces') as $pv => $cs)<option>{{ $pv }}</option>@endforeach</select></div>
      <div class="rp" data-rangepicker data-unavailable="[]">
        <label>ورود و خروج</label>
        <input type="hidden" name="check_in" data-in><input type="hidden" name="check_out" data-out>
        <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب تاریخ</b></div><div><small>خروج</small><b data-label-out>انتخاب تاریخ</b></div></div>
        <div class="rp-pop"></div>
      </div>
      <div><label>مسافران</label><div class="counter gcount" data-any data-min="0" data-max="30"><button type="button" data-dec aria-label="کمتر">−</button><b>هر تعداد</b><button type="button" data-inc aria-label="بیشتر">+</button><input type="hidden" name="guests" value=""></div></div>
      <button class="btn"><x-icon name="search"/> جستجو</button>
      <div class="presets"><span class="muted">پیشنهاد سریع</span><button type="button" class="qchip" data-preset="weekend">این آخر هفته</button><button type="button" class="qchip" data-preset="weekend2">آخر هفته‌ی بعد</button><button type="button" class="qchip" data-preset="midweek">۳ شب میان‌هفته</button><button type="button" class="qchip" data-preset="soon">۳ شب از فردا</button></div>
    </form>
    </div>

    {{-- دسته‌بندی دایره‌ای (نوع اقامتگاه) --}}
    <div class="cat-rail" role="list">
      @foreach(config('mija.property_types') as $k => $n)
        <a role="listitem" class="cat" href="{{ route('search', ['type' => [$k]]) }}"><span class="cat-ic"><x-i3 :name="config('mija.type_icons.'.$k)"/></span><b>{{ $n }}</b><small>{{ fa($typeCounts[$k] ?? 0) }} مورد</small></a>
      @endforeach
    </div>
  </section>

  @if($destinations->count())
  <section class="sec" aria-labelledby="dest-h">
    <div class="sec-h"><div><h2 id="dest-h">مقصدهای محبوب</h2><p>برای سفر بعدی‌تان از این شهرها شروع کنید</p></div><a class="more" href="{{ route('search') }}">همه‌ی مقصدها <x-icon name="arrow-l" class="sm"/></a></div>
    <div class="rail-wrap"><div class="rail dest-rail" data-rail>
      @foreach($destinations as $d)
      <a class="dest" href="{{ route('search', ['city' => $d->city]) }}"><img src="{{ $destCovers[$d->city] ?? asset('img/og.png') }}" alt="اجاره ویلا در {{ $d->city }}" loading="lazy" width="320" height="400"><span class="dest-s"></span><div><b>{{ $d->city }}</b><small>{{ fa($d->c) }} اقامتگاه · از {{ toman($d->p, false) }} تومان</small></div></a>
      @endforeach
    </div>@include('partials.rail-nav')</div>
  </section>
  @endif

  <section class="sec qf" aria-label="فیلتر سریع">
    <div class="qf-rail">
      @foreach(config('mija.regions') as $k => $rg)<a class="chip-s" href="{{ route('search', ['region' => $k]) }}"><x-icon name="pin"/>{{ $rg['name'] }}</a>@endforeach
      <a class="chip-s" href="{{ route('search', ['amenity' => ['pool']]) }}"><x-icon name="pool"/> استخر</a>
      <a class="chip-s" href="{{ route('search', ['instant' => 1]) }}"><x-icon name="bolt"/> رزرو فوری</a>
      <a class="chip-s" href="{{ route('search', ['discount' => 1]) }}"><x-icon name="tag"/> تخفیف‌دار</a>
      <a class="chip-s" href="{{ route('search', ['guests' => 8]) }}"><x-icon name="users"/> ۸ نفر و بیشتر</a>
      <a class="chip-s" href="{{ route('search', ['max_price' => 2000000]) }}">زیر ۲ میلیون</a>
      <a class="chip-s" href="{{ route('search', ['guests' => 2]) }}"><x-icon name="users"/> دونفره</a>
    </div>
  </section>

  @if($banners->count())
  <section class="sec ad-banners" aria-label="تبلیغات">
    <div class="bn" data-swiper="banners"><div class="swiper"><div class="swiper-wrapper">
      @foreach($banners as $ad)@php $bp = $ad->property; @endphp
      <div class="swiper-slide"><a class="bn-slide" href="{{ route('ad.go', $ad) }}" rel="sponsored nofollow"><img src="{{ $ad->bannerUrl() }}" alt="{{ $ad->title ?: $bp->title }}" @if(! $loop->first) loading="lazy" @endif>
        <span class="ad-tag">AD</span>
        <div class="bn-txt"><b>{{ $ad->title ?: $bp->title }}</b><small>{{ $ad->text ?: ($bp->province.'، '.$bp->city) }} · از {{ toman($bp->startingPrice(), false) }} تومان</small></div></a></div>
      @endforeach
    </div></div>@if($banners->count() > 1)<div class="swiper-pagination"></div>@endif</div>
  </section>
  @endif
  @if($flash->count())
  <section class="sec flash-sec"><div class="sec-h"><div><h2><x-i3 name="p_bolt" class="sm"/> تخفیف لحظه‌آخری</h2><p>فقط برای امروز و فردا؛ میزبان‌ها با تخفیف ویژه، سریع رزرو کنید.</p></div></div><div class="rail-wrap"><div class="rail" data-rail>@foreach($flash as $p)@include('partials.pcard')@endforeach</div>@include('partials.rail-nav')</div></section>
  @endif

  <section class="sec recent" id="recent-box" data-swiper="cards">
    <div class="sec-h"><div><h2>ادامه‌ی جستجوی شما</h2></div></div>
    <div class="swiper"><div class="swiper-wrapper recent-list"></div></div>
  </section>

  @foreach([
    ['منتخب می‌جا', 'اقامتگاه‌هایی که تیم ما پیشنهاد می‌کند', $featured, ['sort' => '']],
    ['رزرو فوری', 'همین الان انتخاب کن، همین الان قطعی شو', $instant, ['instant' => 1]],
    ['اقامت‌های به‌صرفه', 'تخفیف هفتگی و ماهانه', $discounted, ['discount' => 1]],
    ['تازه‌ترین‌ها', 'تازه به می‌جا پیوسته‌اند', $latest, ['sort' => 'new']],
    ['بالاترین امتیاز', 'محبوب‌ترین‌ها بین مهمان‌ها', $top, ['sort' => 'rating']],
  ] as [$t, $sub, $list, $q])
  @if($list->count())
  <section class="sec">
    <div class="sec-h"><div><h2>{{ $t }}</h2><p>{{ $sub }}</p></div><a class="more" href="{{ route('search', array_filter($q)) }}">مشاهده همه <x-icon name="arrow-l" class="sm"/></a></div>
    <div class="rail-wrap"><div class="rail" data-rail>@foreach($list->take(10) as $p)@include('partials.pcard')@endforeach</div>@include('partials.rail-nav')</div>
  </section>
  @endif
  @endforeach

  <section class="sec">
    <a class="app-bn" href="{{ route('app') }}">
      <div class="app-bn-t"><span class="kicker"><x-i3 name="p_phone" class="sm"/> اپلیکیشن اندروید</span><h2>می‌جا را در گوشی داشته باشید</h2><p>رزرو سریع‌تر، اعلان لحظه‌ای وضعیت رزرو و پرداخت، و دسترسی آسان به ووچر سفر.</p><span class="btn btn-gold">دریافت اپلیکیشن <x-icon name="arrow-l" class="sm"/></span></div>
      <div class="app-bn-i" aria-hidden="true"><i></i><i></i><i></i></div>
    </a>
  </section>

  <section class="sec">
    <div class="sec-h"><div><h2>رزرو در سه قدم</h2></div></div>
    <div class="mini-steps">
      <div class="ms"><span class="n">۱</span><div><b>جستجو و انتخاب</b><p>مقصد و تاریخ را بزن و بین اقامتگاه‌های تأییدشده مقایسه کن.</p></div></div>
      <div class="ms"><span class="n">۲</span><div><b>رزرو و پرداخت امن</b><p>رزرو فوری یا درخواست به میزبان؛ پرداخت آنلاین با ضمانت.</p></div></div>
      <div class="ms"><span class="n">۳</span><div><b>سفر</b><p>آدرس و راهنمای ورود را بگیر و از اقامتت لذت ببر.</p></div></div>
    </div>
  </section>

  <section class="sec">
    <div class="feat">
      <div class="card"><div class="ico"><x-icon name="shield"/></div><b>پرداخت امن</b><p class="small muted">وجه تا روز ورود نزد می‌جا می‌ماند و در صورت لغو طبق قوانین بازمی‌گردد.</p></div>
      <div class="card"><div class="ico"><x-icon name="bolt"/></div><b>رزرو فوری</b><p class="small muted">بدون انتظار برای تأیید میزبان، همان لحظه قطعی می‌شود.</p></div>
      <div class="card"><div class="ico"><x-icon name="tag"/></div><b>قیمت شفاف</b><p class="small muted">قیمت نهایی با همه‌ی هزینه‌ها پیش از پرداخت نمایش داده می‌شود.</p></div>
      <div class="card"><div class="ico"><x-icon name="star"/></div><b>نظرات واقعی</b><p class="small muted">فقط مهمان‌هایی که واقعاً اقامت داشته‌اند نظر می‌گذارند.</p></div>
    </div>
  </section>

  @if($reviews->count())
  <section class="sec" data-swiper="testi">
    <div class="sec-h"><div><h2>نظر مهمانان</h2></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($reviews as $rv)
      <div class="swiper-slide"><div class="card testi"><p style="margin:0 0 14px">{{ \Illuminate\Support\Str::limit($rv->comment, 150) }}</p><div class="row"><img class="avatar sm" src="{{ $rv->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $rv->guest->name }}</b><div class="xs muted">{{ $rv->property->title }}</div></div><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span></div></div></div>
    @endforeach</div></div>
  </section>
  @endif

  <section class="sec seo-text">
    <h2>رزرو ویلا و کلبه با می‌جا</h2>
    <p>می‌جا بستری برای <b>رزرو ویلا</b>، <b>اجاره کلبه</b> و اقامتگاه‌های بوم‌گردی در سراسر ایران است؛ از <a href="{{ route('search', ['region' => 'north']) }}">ویلاهای شمال</a> و <a href="{{ route('search', ['type' => ['cottage']]) }}">کلبه‌های جنگلی</a> تا <a href="{{ route('search', ['region' => 'tehran']) }}">اقامتگاه‌های نزدیک تهران</a>، <a href="{{ route('search', ['region' => 'desert']) }}">بوم‌گردی کویر</a> و <a href="{{ route('search', ['region' => 'south']) }}">جزیره‌های جنوب</a>. قیمت‌ها شفاف است، تقویم شمسی روزهای خالی را نشان می‌دهد و پرداخت امن انجام می‌شود.</p>
    <div class="seo-links">@foreach($destinations as $d)<a href="{{ route('search', ['city' => $d->city]) }}">اجاره ویلا در {{ $d->city }}</a>@endforeach</div>
  </section>

  <section class="sec" style="max-width:820px;margin-inline:auto">
    <div class="sec-h"><div><h2>پرسش‌های متداول</h2></div></div>
    <div class="faq">
      <details><summary>رزرو فوری با رزرو درخواستی چه فرقی دارد؟</summary><p>در رزرو فوری بلافاصله پرداخت می‌کنید و رزرو قطعی می‌شود. در رزرو درخواستی ابتدا میزبان باید تأیید کند و سپس پرداخت انجام می‌شود.</p></details>
      <details><summary>اگر رزروم را لغو کنم چه مقدار پول برمی‌گردد؟</summary><p>بسته به سیاست لغو اقامتگاه و زمان لغو، بخشی یا کل مبلغ به کیف پول شما بازمی‌گردد. جزئیات در صفحه‌ی <a href="{{ route('page', 'rules') }}" style="color:var(--brand-d);font-weight:600">قوانین</a> آمده است.</p></details>
      <details><summary>پول من کی به دست میزبان می‌رسد؟</summary><p>وجه تا روز ورود نزد می‌جا می‌ماند و پس از ورود مهمان، به کیف پول میزبان واریز می‌شود.</p></details>
      <details><summary>چطور اقامتگاهم را ثبت کنم؟</summary><p>با حساب «میزبان» ثبت‌نام کنید، اطلاعات و عکس‌ها را وارد کنید و برای بررسی ارسال کنید. ثبت کاملاً رایگان است.</p></details>
    </div>
  </section>

  @php $homeArts = \App\Models\Article::published()->latest('published_at')->limit(3)->get(); @endphp
  @if($homeArts->count())
  <section class="sec">
    <div class="sec-h"><h2>از مجله‌ی می‌جا</h2><a href="{{ route('blog') }}" class="small">همه‌ی مقاله‌ها</a></div>
    <div class="bl-grid">@foreach($homeArts as $ha)<a class="bl-card" href="{{ $ha->url() }}"><div class="bl-img"><img src="{{ $ha->imageUrl() }}" alt="{{ $ha->image_alt ?: $ha->title }}" width="600" height="338" loading="lazy"></div><div class="bl-b"><h3>{{ $ha->title }}</h3><small>{{ fa($ha->readMinutes()) }} دقیقه مطالعه</small></div></a>@endforeach</div>
  </section>
  @endif

  <section class="sec">
    <div class="cta"><div class="row wrap between" style="gap:20px">
      <div style="max-width:560px"><h2>ملک دارید؟ میزبان می‌جا شوید.</h2><p>اقامتگاه را رایگان ثبت کنید، تقویم و قیمت را خودتان مدیریت کنید و درآمد را مستقیم به حساب بانکی‌تان بگیرید.</p></div>
      <a class="btn btn-gold btn-lg" href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه <x-icon name="arrow-l"/></a>
    </div></div>
  </section>
</div>
@endsection
