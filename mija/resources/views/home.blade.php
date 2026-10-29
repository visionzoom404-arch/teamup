@extends('layouts.app')
@section('title', 'رزرو آنلاین ویلا و کلبه')
@push('head')<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">@endpush
@section('bare')
<div class="container">
  {{-- ===== قهرمان ===== --}}
  <section class="stage light" data-parallax>
    @if($hero = site_image('hero'))<img class="stage-photo" src="{{ $hero }}" alt="" fetchpriority="high">@else
    @include('partials.auth-scene', ['bare' => true])
    @endif
    <div class="stage-in">
      <span class="glass-w badge-g" style="margin-bottom:16px"><x-i3 name="sun" class="sm"/> اقامتی که یادت می‌مونه</span>
      <h1>{{ config('mija.tagline') === 'جای خوب، سفر خوب' ? 'جای خوب،' : config('mija.tagline') }} <span class="grad-text">سفر خوب</span></h1>
      <p class="sub">همین امروز <span class="rotor"><span>ویلا</span><span>کلبه</span><span>باغ‌ویلا</span><span>سوئیت</span><span>بوم‌گردی</span></span> دلخواهت رو رزرو کن؛ مستقیم از میزبان، با قیمت شفاف و پرداخت امن.</p>
      <div class="trust">
        <div><span class="t-i"><x-i3 name="p_house" class="sm"/></span><span><b data-count="{{ $stats['properties'] }}">{{ fa($stats['properties']) }}</b><small>اقامتگاه تأییدشده</small></span></div>
        <div><span class="t-i"><x-i3 name="p_pin" class="sm"/></span><span><b data-count="{{ $stats['cities'] }}">{{ fa($stats['cities']) }}</b><small>شهر و منطقه</small></span></div>
        <div><span class="t-i"><x-i3 name="family" class="sm"/></span><span><b data-count="{{ $stats['guests'] }}">{{ fa($stats['guests']) }}</b><small>مهمان خوشحال</small></span></div>
        @if($stats['rating'])<div><span class="t-i"><x-i3 name="p_star" class="sm"/></span><span><b data-count="{{ $stats['rating'] }}" data-dec="1">{{ fa($stats['rating']) }}</b><small>میانگین امتیاز</small></span></div>@endif
      </div>
    </div>
    <div class="chip chip-a glass-w"><x-i3 name="p_bolt" class="lg"/><div><b>رزرو فوری</b><small>بدون انتظار تأیید</small></div></div>
    <div class="chip chip-b glass-w"><x-i3 name="p_shield" class="lg"/><div><b>پرداخت امن</b><small>ضمانت بازگشت وجه</small></div></div>
  </section>

  <form class="searchbar glass stage-search" action="{{ route('search') }}" method="get" id="home-search">
    <button type="button" class="sp-pill" data-open-search><x-i3 name="p_search"/><span><b>کجا می‌خوای بری؟</b><small>مقصد · تاریخ · تعداد نفرات</small></span><i>جستجو</i></button>
    <div class="sp-head"><b>جستجوی اقامتگاه</b><button type="button" class="btn btn-sm btn-icon btn-light" data-close-search aria-label="بستن"><x-icon name="x" class="sm"/></button></div>
    <div><label>کجا می‌خوای بری؟</label><select name="province"><option value="">همه‌ی استان‌ها</option>@foreach(config('mija.provinces') as $pv => $cs)<option>{{ $pv }}</option>@endforeach</select></div>
    <div class="rp" data-rangepicker data-unavailable="[]">
      <label>کی؟ (ورود و خروج)</label>
      <input type="hidden" name="check_in" data-in><input type="hidden" name="check_out" data-out>
      <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب تاریخ</b></div><div><small>خروج</small><b data-label-out>انتخاب تاریخ</b></div></div>
      <div class="rp-pop glass"></div>
    </div>
    <div><label>چند نفر؟</label><select name="guests"><option value="">هر تعداد</option>@foreach([2,4,6,8,10,15] as $n)<option value="{{ $n }}">{{ fa($n) }} نفر و بیشتر</option>@endforeach</select></div>
    <button class="btn btn-lg pulse"><x-icon name="search"/> جستجو</button>
    <div class="presets"><span class="muted xs">پیشنهاد سریع:</span><button type="button" class="qchip" data-preset="weekend">این آخر هفته</button><button type="button" class="qchip" data-preset="weekend2">آخر هفته‌ی بعد</button><button type="button" class="qchip" data-preset="midweek">۳ شب میان‌هفته</button><button type="button" class="qchip" data-preset="soon">۳ شب از فردا</button></div>
  </form>

  {{-- ===== دسته‌بندی‌ها ===== --}}
  <section class="sec" style="padding-bottom:10px">
    <div class="sec-h"><div><h2>دنبال چه جور <span class="grad-text">سفری</span> هستی؟</h2><p>از دسته‌بندی‌ها شروع کن؛ بقیه‌اش با ما</p></div></div>
    <div data-swiper="cats"><div class="swiper"><div class="swiper-wrapper">
      @foreach([
        ['pool', 'ویلای استخردار', ['amenity' => ['pool']]], ['tiny', 'کلبه و خانه چوبی', ['type' => ['cottage', 'tiny']]], ['garden_villa', 'باغ‌ویلا', ['type' => ['garden_villa']]],
        ['sun', 'شمال کشور', ['region' => 'north']], ['p_pin', 'نزدیک تهران', ['region' => 'tehran']], ['view', 'ییلاقی و کوهستانی', ['region' => 'highland']],
        ['eco', 'کویر و بوم‌گردی', ['type' => ['eco']]], ['p_bolt', 'رزرو فوری', ['instant' => 1]], ['family', 'مناسب خانواده', ['amenity' => ['family']]],
        ['fireplace', 'با شومینه', ['amenity' => ['fireplace']]], ['jacuzzi', 'جکوزی و سونا', ['amenity' => ['jacuzzi']]], ['pet', 'پذیرش حیوان خانگی', ['amenity' => ['pet']]],
        ['suite', 'سوئیت و آپارتمان', ['type' => ['suite', 'apartment']]], ['p_tag', 'تخفیف‌دار', ['discount' => 1]],
      ] as [$ic, $n, $q])
        <a class="cat-big swiper-slide" href="{{ route('search', $q) }}"><span class="cb-ic"><x-i3 :name="$ic"/></span><b>{{ $n }}</b></a>
      @endforeach
    </div></div></div>
  </section>

  {{-- ===== بازدیدهای اخیر (از مرورگر) ===== --}}
  <section class="sec recent" id="recent-box" data-swiper="cards">
    <div class="sec-h"><div><h2>ادامه‌ی جستجوی شما</h2><p>اقامتگاه‌هایی که اخیراً دیده‌اید</p></div></div>
    <div class="swiper"><div class="swiper-wrapper recent-list"></div></div>
  </section>

  @if($featured->count())
  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><h2>پیشنهاد <span class="grad-text">ویژه‌ی می‌جا</span></h2><p>اقامتگاه‌هایی که تیم ما دوستشون داره</p></div>
      <div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($featured as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
  </section>
  @endif

  {{-- ===== مقصدها (bento) ===== --}}
  <section class="sec">
    <div class="sec-h"><div><h2>مقصدهای <span class="grad-text">محبوب</span></h2><p>هر منطقه، یه حال و هوای متفاوت</p></div></div>
    <div class="bento">
      @foreach(config('mija.regions') as $k => $rg)
        @php $ic = ['north' => 'garden', 'tehran' => 'pin', 'highland' => 'view', 'desert' => 'eco', 'south' => 'pool'][$k]; @endphp
        <a class="bt bt-{{ $k }}" href="{{ route('search', ['region' => $k]) }}">
          <x-i3 :name="$ic" class="bt-ic"/>
          <div class="bt-t"><b>{{ $rg['name'] }}</b><small>{{ $rg['sub'] }}</small><span class="bt-n">{{ fa($regionCounts[$k] ?? 0) }} اقامتگاه <x-icon name="chev-l" class="sm"/></span></div>
        </a>
      @endforeach
    </div>
  </section>

  {{-- ===== تخفیف‌ها ===== --}}
  @if($discounted->count())
  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><h2><span class="tag-sale" style="font-size:14px;vertical-align:4px">تخفیف</span> اقامت‌های به‌صرفه</h2><p>تخفیف هفتگی و ماهانه برای اقامت‌های طولانی‌تر</p></div>
      <div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($discounted as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
  </section>
  @endif

  {{-- ===== نوار شهرها ===== --}}
  @php $mc = $cities->count() >= 6 ? $cities : collect(['نوشهر', 'چالوس', 'کلاردشت', 'رامسر', 'ماسوله', 'لواسان', 'کاشان', 'شیراز', 'قشم', 'کیش', 'سوادکوه', 'فشم'])->merge($cities)->unique()->values(); @endphp
  <div class="marq" aria-label="شهرها"><div class="marq-in">@for($k = 0; $k < 2; $k++)@foreach($mc as $c)<a href="{{ route('search', ['city' => $c]) }}"><x-icon name="pin" class="sm"/> {{ $c }}</a>@endforeach @endfor</div></div>

  {{-- ===== رزرو فوری ===== --}}
  @if($instant->count())
  <section class="sec" data-swiper="cards">
    <div class="sec-h"><div><h2><x-i3 name="p_bolt" class="h-i3"/> رزرو فوری، <span class="grad-text">بدون انتظار</span></h2><p>همین الان انتخاب کن، همین الان قطعی شو</p></div>
      <div class="sw-nav"><button class="sw-btn sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button class="sw-btn sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($instant as $p)<div class="swiper-slide">@include('partials.pcard')</div>@endforeach</div></div>
  </section>
  @endif

  {{-- ===== چطور کار می‌کند ===== --}}
  <section class="sec">
    <div class="sec-h"><div><h2>رزرو در <span class="grad-text">سه قدم</span></h2><p>ساده، سریع و بدون دردسر</p></div></div>
    <div class="steps-3">
      <div class="step"><div class="no"><x-i3 name="p_search" class="xl"/><span class="n">۱</span></div><b>جستجو و انتخاب</b><p class="small muted">مقصد و تاریخ رو بزن و بین اقامتگاه‌های تأییدشده انتخاب کن.</p></div>
      <div class="step"><div class="no"><x-i3 name="p_bolt" class="xl"/><span class="n">۲</span></div><b>رزرو و پرداخت امن</b><p class="small muted">رزرو فوری یا درخواست به میزبان؛ پرداخت آنلاین با ضمانت.</p></div>
      <div class="step"><div class="no"><x-i3 name="p_house" class="xl"/><span class="n">۳</span></div><b>سفر و لذت</b><p class="small muted">آدرس و راهنمای ورود رو بگیر و از اقامتت لذت ببر.</p></div>
    </div>
  </section>

  @if($top->count())
  <section class="sec"><div class="sec-h"><div><h2>بالاترین امتیاز <x-i3 name="p_star" class="h-i3"/></h2><p>محبوب‌ترین‌ها بین مهمان‌ها</p></div></div>
    <div class="grid-cards">@foreach($top as $p)@include('partials.pcard')@endforeach</div></section>
  @endif

  {{-- ===== چرا می‌جا ===== --}}
  <section class="sec">
    <div class="sec-h"><div><h2>چرا <span class="grad-text">می‌جا</span>؟</h2><p>چون سفر خوب، خیال راحت می‌خواد</p></div></div>
    <div class="feat">
      <div class="card"><div class="ico"><x-i3 name="p_shield" class="lg"/></div><b>پرداخت امن و ضمانت</b><p class="small muted">پولت تا روز ورود نزد می‌جا می‌مونه و اگه لغو کنی طبق قوانین برمی‌گرده.</p></div>
      <div class="card"><div class="ico"><x-i3 name="p_bolt" class="lg"/></div><b>رزرو فوری</b><p class="small muted">اقامتگاه‌های رزرو فوری بدون انتظار برای تأیید میزبان، همون لحظه قطعی می‌شن.</p></div>
      <div class="card"><div class="ico"><x-i3 name="p_tag" class="lg"/></div><b>قیمت شفاف</b><p class="small muted">قیمت نهایی با همه‌ی هزینه‌ها پیش از پرداخت نشون داده می‌شه؛ بدون هزینه‌ی پنهان.</p></div>
      <div class="card"><div class="ico"><x-i3 name="p_star" class="lg"/></div><b>نظرات واقعی</b><p class="small muted">فقط مهمان‌هایی که واقعاً اقامت داشتن می‌تونن نظر بذارن.</p></div>
    </div>
  </section>

  @if($reviews->count())
  <section class="sec" data-swiper="testi">
    <div class="sec-h"><div><h2>مهمان‌ها چی می‌گن</h2><p>نظرات واقعی از اقامت‌های انجام‌شده</p></div></div>
    <div class="swiper"><div class="swiper-wrapper">@foreach($reviews as $rv)
      <div class="swiper-slide"><div class="card testi"><div class="row"><img class="avatar sm" src="{{ $rv->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $rv->guest->name }}</b><div class="xs muted">{{ $rv->property->title }}</div></div><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span></div><p style="margin:12px 0 0">«{{ \Illuminate\Support\Str::limit($rv->comment, 160) }}»</p></div></div>
    @endforeach</div></div>
  </section>
  @endif

  {{-- ===== نصب اپ ===== --}}
  <section class="sec">
    <div class="cta"><div class="row wrap between" style="gap:22px">
      <div class="row" style="gap:18px"><img src="{{ asset('icons/icon-192.png') }}" width="84" height="84" alt="" style="border-radius:24px;box-shadow:0 18px 34px rgba(120,40,0,.4);animation:logoFloat 4s ease-in-out infinite"><div><h2 style="color:#fff;margin-bottom:4px">اپلیکیشن می‌جا رو نصب کن</h2><p style="opacity:.92;margin:0">سریع‌تر، بدون مرورگر؛ مستقیم از صفحه‌ی اصلی گوشی.</p></div></div>
      <button class="btn btn-light btn-lg" data-install><x-icon name="download"/> نصب اپلیکیشن</button>
    </div></div>
  </section>

  <section class="sec" style="max-width:860px;margin-inline:auto">
    <div class="sec-h"><div><h2>پرسش‌های متداول</h2></div></div>
    <div class="faq">
      <details><summary>رزرو فوری با رزرو درخواستی چه فرقی دارد؟</summary><p>در رزرو فوری بلافاصله پرداخت می‌کنی و رزرو قطعی می‌شه. در رزرو درخواستی اول میزبان باید تأیید کنه و بعدش پرداخت انجام می‌شه.</p></details>
      <details><summary>اگر رزروم را لغو کنم چه مقدار پول برمی‌گردد؟</summary><p>بسته به سیاست لغوِ اقامتگاه (منعطف، متوسط یا سخت‌گیرانه) و زمان لغو، بخشی یا کل مبلغ به کیف پولت برمی‌گرده. جزئیات در <a href="{{ route('page', 'rules') }}" style="color:var(--brand-d);font-weight:700">قوانین</a> هست.</p></details>
      <details><summary>پول من کی به دست میزبان می‌رسد؟</summary><p>پول تا روز ورود نزد می‌جا می‌مونه و بعد از ورود مهمان، به کیف پول میزبان واریز می‌شه.</p></details>
      <details><summary>چطور اقامتگاهم را ثبت کنم؟</summary><p>با حساب «میزبان» ثبت‌نام کن، اطلاعات و عکس‌ها رو وارد کن و برای بررسی بفرست. ثبت کاملاً رایگانه.</p></details>
    </div>
  </section>

  <section class="sec">
    <div class="cta cta-dark">
      <div class="row wrap between" style="gap:20px">
        <div style="max-width:560px"><h2 style="color:#fff">ملک داری؟ <span style="color:var(--gold-l)">میزبان می‌جا شو</span></h2><p style="opacity:.88;margin:0">اقامتگاهت رو رایگان ثبت کن، تقویم و قیمت‌ها رو خودت مدیریت کن و درآمدت رو مستقیم به حساب بانکی‌ات بگیر.</p></div>
        <a class="btn btn-gold btn-lg" href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه <x-icon name="chev-l"/></a>
      </div>
    </div>
  </section>
</div>
@endsection
