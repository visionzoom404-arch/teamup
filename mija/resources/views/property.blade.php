@extends('layouts.app')
@section('title', 'اجاره '.$p->typeLabel().' '.$p->city.' — '.$p->title)
@section('keywords', 'اجاره '.$p->typeLabel().' در '.$p->city.', رزرو '.$p->typeLabel().' '.$p->province.', '.$p->title.', اجاره روزانه '.$p->typeLabel().', می‌جا')
@section('ogtype', 'product')
@section('ogimage', $p->cover())
@push('jsonld')
<script type="application/ld+json">{!! json_encode(array_filter(['@context' => 'https://schema.org', '@type' => 'VacationRental', 'name' => $p->title, 'description' => \Illuminate\Support\Str::limit(strip_tags($p->description), 300), 'url' => route('property', $p), 'image' => $p->images->take(6)->map->url()->values()->all() ?: [asset('img/og.png')], 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $p->city, 'addressRegion' => $p->province, 'addressCountry' => 'IR'], 'numberOfRooms' => $p->bedrooms, 'occupancy' => ['@type' => 'QuantitativeValue', 'maxValue' => $p->capacity_max], 'petsAllowed' => (bool) ($p->house_rules['pets'] ?? false), 'priceRange' => toman($p->startingPrice(), false).' تومان', 'aggregateRating' => $p->reviews_count ? ['@type' => 'AggregateRating', 'ratingValue' => (string) $p->rating_avg, 'reviewCount' => $p->reviews_count, 'bestRating' => 5] : null]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'می‌جا', 'item' => url('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'اجاره ویلا و کلبه در '.$p->city, 'item' => route('search', ['city' => $p->city])], ['@type' => 'ListItem', 'position' => 3, 'name' => $p->title, 'item' => route('property', $p)]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('desc', \Illuminate\Support\Str::limit(strip_tags($p->description), 150))
@push('head')<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">@endpush
@push('vendor')<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>@endpush
@section('bare')
@php
  $imgs = $p->images;
  $groups = ['feature' => ['امکانات ویژه', 'p_star'], 'basic' => ['امکانات پایه', 'p_house'], 'service' => ['خدمات', 'p_key'], 'rules' => ['قوانین و پذیرش', 'p_shield']];
  $credits = $imgs->whereNotNull('credit')->unique('credit');
  $statusFa = ['draft' => 'پیش‌نویس', 'pending' => 'در انتظار تأیید', 'rejected' => 'ردشده', 'suspended' => 'تعلیق‌شده'];
  $tiers = $p->policy()['tiers'];
  $cm = \App\Support\Pricing::commissionFor($p->host);
  $wdG = \App\Support\Pricing::gross($p->price_weekday, $cm); $weG = \App\Support\Pricing::gross($p->price_weekend, $cm);
@endphp
<div class="container vp">
  @include('partials.flash')
  @if($p->status !== 'approved')<div class="alert warn"><x-icon name="info"/>این اقامتگاه هنوز منتشر نشده است ({{ $statusFa[$p->status] ?? $p->status }}) و فقط برای شما قابل مشاهده است.</div>@endif

  @include('partials.stepper', ['steps' => [['انتخاب تاریخ', 'calendar'], ['تأیید میزبان', 'check'], ['پرداخت', 'card'], ['رزرو قطعی', 'shield'], ['اقامت', 'home'], ['ثبت نظر', 'star']], 'at' => 1])

  {{-- سرتیتر --}}
  <header class="vp-head">
    <div class="vp-title">
      <nav class="vp-crumb"><a href="{{ route('home') }}">خانه</a><i>/</i><a href="{{ route('search', ['q' => $p->city]) }}">{{ $p->city }}</a><i>/</i><span>{{ $p->typeLabel() }}</span></nav>
      <h1>{{ $p->title }}</h1>
      <div class="vp-meta">
        @if($p->reviews_count)<span class="vp-rate"><x-icon name="star" class="sm"/><b>{{ fa(number_format($p->rating_avg, 1)) }}</b><small>({{ fa($p->reviews_count) }} نظر)</small></span>@endif
        <span><x-icon name="pin" class="sm"/>{{ $p->province }}، {{ $p->city }}</span>
        <span class="vp-chip">{{ $p->typeLabel() }}</span>
        @if($p->instant_booking)<span class="vp-chip hot"><x-icon name="bolt" class="sm"/>رزرو فوری</span>@endif
        @if($hs['superhost'])<span class="vp-chip gold"><x-i3 name="p_crown" class="sm"/>میزبان برتر</span>@endif
      </div>
    </div>
    <div class="vp-act">
      <div class="share-pop"><button class="vp-ic" type="button" data-share-toggle aria-label="اشتراک"><x-icon name="share" class="sm"/><span>اشتراک</span></button>
        <div class="share-menu"><a target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode($p->title.' — '.route('property', $p)) }}"><x-icon name="chat" class="sm"/> واتس‌اپ</a><a target="_blank" rel="noopener" href="https://t.me/share/url?url={{ urlencode(route('property', $p)) }}&text={{ urlencode($p->title) }}"><x-icon name="send" class="sm"/> تلگرام</a><button type="button" data-copy="#plink"><x-icon name="share" class="sm"/> کپی لینک</button><span id="plink" hidden>{{ route('property', $p) }}</span></div></div>
      @auth @if(auth()->user()->isGuest())<button class="vp-ic" type="button" data-fav="{{ route('favorite', $p) }}"><span class="fav {{ $isFav ? 'on' : '' }}"><x-icon name="heart" class="sm"/></span><span>ذخیره</span></button>@endif @endauth
    </div>
  </header>

  {{-- گالری اسلایدی: هر تعداد عکس (۱ تا ۵۰) — اسلاید + تصاویر کوچک + نمایش تمام‌صفحه --}}
  <section class="pg" data-swiper="pgal" aria-label="عکس‌های اقامتگاه">
    <div class="pg-main"><div class="swiper"><div class="swiper-wrapper">
      @forelse($imgs as $i => $im)
        <div class="swiper-slide"><a href="{{ $im->url() }}" data-lightbox data-g="pg" class="pg-slide"><img src="{{ $im->url() }}" alt="{{ $p->title }} — عکس {{ fa($i + 1) }}" @if($i) loading="lazy" @else fetchpriority="high" @endif></a></div>
      @empty
        <div class="swiper-slide"><a href="#" class="pg-slide"><img class="pg-ph" src="{{ asset('img/placeholder.svg') }}" alt=""></a></div>
      @endforelse
    </div></div>
    @if($imgs->count() > 1)
    <button type="button" class="pg-nav sw-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><button type="button" class="pg-nav sw-next" aria-label="بعدی"><x-icon name="chev-l"/></button>
    <span class="count pg-count"></span>
    @endif
    </div>
    @if($imgs->count() > 1)
    <div class="pg-thumbs" role="tablist">@foreach($imgs as $i => $im)<button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-i="{{ $i }}" aria-label="عکس {{ fa($i + 1) }}"><img src="{{ $im->url() }}" alt="" loading="lazy"></button>@endforeach</div>
    @endif
  </section>
  @if($credits->count())<div class="xs muted" style="margin-top:6px">عکس‌ها: @foreach($credits as $cr)<a href="{{ $cr->credit_url }}" target="_blank" rel="noopener" style="text-decoration:underline">{{ $cr->credit }}</a>@if(!$loop->last)، @endif @endforeach — <a href="https://unsplash.com/?utm_source=mija&utm_medium=referral" target="_blank" rel="noopener" style="text-decoration:underline">Unsplash</a></div>@endif
  <div id="recent-data" hidden data-slug="{{ $p->slug }}" data-title="{{ $p->title }}" data-img="{{ $p->cover() }}" data-url="{{ route('property', $p) }}" data-price="{{ toman($p->startingPrice(), false) }}"></div>
  <div class="lightbox" id="lightbox"><button class="vp-ic lb-x" aria-label="بستن"><x-icon name="x"/></button><button class="pg-nav lb-prev" aria-label="قبلی"><x-icon name="chev-r"/></button><img src="" alt=""><button class="pg-nav lb-next" aria-label="بعدی"><x-icon name="chev-l"/></button><span class="lb-count"></span></div>

  <div class="vp-grid">
    <div class="vp-main">
      <nav class="vp-tabs" id="vp-tabs" aria-label="بخش‌های صفحه">
        <a href="#v-about" class="on">درباره</a><a href="#v-amen">امکانات</a><a href="#v-rules">قوانین</a><a href="#v-map">موقعیت</a><a href="#v-rev">نظرات</a>
      </nav>

      {{-- درباره --}}
      <section class="vp-card" id="v-about">
        <div class="vp-facts">
          <div><i><x-icon name="users"/></i><b>{{ fa($p->capacity_base) }}<small>+{{ fa($p->capacity_max - $p->capacity_base) }}</small></b><span>نفر</span></div>
          <div><i><x-icon name="bed"/></i><b>{{ fa($p->bedrooms) }}</b><span>اتاق خواب</span></div>
          <div><i><x-icon name="bed"/></i><b>{{ fa($p->beds) }}</b><span>تخت</span></div>
          <div><i><x-icon name="bath"/></i><b>{{ fa($p->bathrooms) }}</b><span>سرویس</span></div>
        </div>
        <div class="vp-host">
          <img class="avatar" src="{{ $p->host->avatarUrl() }}" alt="">
          <div class="grow">
            <div class="vp-host-n"><a href="{{ route('host.public', $p->host) }}">{{ $p->host->name }}</a>
              @if($p->host->host_verified)<span class="vp-chip ok"><x-icon name="shield" class="sm"/>هویت تأییدشده</span>@endif</div>
            <small>میزبان · عضو از {{ jdate($p->host->created_at, 'F Y') }}@if($hs['response_rate'] !== null) · پاسخ‌گویی {{ fa($hs['response_rate']) }}٪@endif</small>
          </div>
          <a class="vp-link" href="{{ route('host.public', $p->host) }}">پروفایل</a>
        </div>
        <h2 class="vp-h"><x-i3 name="p_house" class="sm"/>درباره‌ی اقامتگاه</h2>
        <p class="vp-text">{{ $p->description }}</p>
        <div class="vp-times"><span><x-icon name="clock" class="sm"/>ورود {{ fa($p->check_in_time) }}</span><span><x-icon name="clock" class="sm"/>خروج {{ fa($p->check_out_time) }}</span><span><x-icon name="calendar" class="sm"/>{{ fa($p->min_nights) }} تا {{ fa($p->max_nights) }} شب</span></div>
      </section>

      {{-- امکانات --}}
      <section class="vp-card" id="v-amen">
        <h2 class="vp-h"><x-i3 name="p_star" class="sm"/>امکانات</h2>
        @foreach($groups as $gk => [$gl, $gi])
          @php $list = $p->amenities->where('grp', $gk); @endphp
          @if($list->count())<div class="vp-amen-g"><h4>{{ $gl }}</h4><div class="vp-amen">@foreach($list as $a)<span><x-i3 :name="$a->i3()" class="sm"/>{{ $a->name }}</span>@endforeach</div></div>@endif
        @endforeach
      </section>

      {{-- قوانین --}}
      <section class="vp-card" id="v-rules">
        <h2 class="vp-h"><x-i3 name="p_shield" class="sm"/>قوانین و لغو رزرو</h2>
        <div class="vp-policy">
          <div class="vp-pol-h"><b>سیاست لغو: {{ $p->policy()['name'] }}</b><a href="{{ route('page', 'rules') }}">جزئیات</a></div>
          <div class="cx-bar">@foreach($tiers as [$days, $pct])<span style="flex:1;--c:{{ $pct >= 100 ? 'var(--green)' : '#F5B301' }}"><b>{{ fa($pct) }}٪</b></span>@endforeach<span style="flex:1;--c:var(--sale)"><b>۰٪</b></span></div>
          <div class="cx-lbl">@foreach($tiers as [$days, $pct])<span>تا {{ fa($days) }} روز مانده</span>@endforeach<span>کمتر</span></div>
          <p>{{ $p->policy()['desc'] }}</p>
        </div>
        <div class="rules-grid">@foreach($p->rulesList() as [$rk, $rl, $ok])<div class="{{ $ok ? 'yes' : 'no' }}"><i><x-icon :name="$ok ? 'check' : 'x'" class="sm"/></i>{{ $ok ? 'مجاز' : 'ممنوع' }}: {{ $rl }}</div>@endforeach</div>
        @if($p->extra_guest_fee)<div class="vp-line"><x-icon name="users" class="sm"/>ظرفیت استاندارد {{ fa($p->capacity_base) }} نفر؛ هر نفر اضافه (تا {{ fa($p->capacity_max) }} نفر): <b>{{ toman(\App\Support\Pricing::gross($p->extra_guest_fee, $cm)) }}</b> در شب</div>@endif
        @if($p->weekly_discount || $p->monthly_discount)<div class="vp-line off"><x-icon name="tag" class="sm"/>@if($p->weekly_discount)تخفیف هفتگی {{ fa($p->weekly_discount) }}٪ @endif @if($p->monthly_discount)· تخفیف ماهانه {{ fa($p->monthly_discount) }}٪@endif</div>@endif
        @if($p->rules)<div class="vp-rules"><h4>قوانین میزبان</h4><p>{{ $p->rules }}</p></div>@endif
      </section>

      {{-- موقعیت --}}
      <section class="vp-card" id="v-map">
        <h2 class="vp-h"><x-i3 name="p_pin" class="sm"/>موقعیت</h2>
        @if($approx)<div class="map-l" data-map data-approx data-lat="{{ $approx[0] }}" data-lng="{{ $approx[1] }}"></div><p class="xs muted" style="margin:8px 0 0"><x-icon name="lock" class="sm"/> محدوده‌ی تقریبی نمایش داده شده است؛ آدرس دقیق، نقشه و شماره‌ی میزبان پس از پرداخت و قطعی شدن رزرو برای شما باز می‌شود.</p>
        @else<div class="vp-nomap"><x-i3 name="p_pin" class="lg"/><b>{{ $p->province }}، {{ $p->city }}</b><small>آدرس دقیق پس از قطعی شدن رزرو ارسال می‌شود</small></div>@endif
      </section>

      {{-- نظرات --}}
      <section class="vp-card" id="v-rev">
        <h2 class="vp-h"><x-i3 name="p_chat" class="sm"/>نظرات مهمانان</h2>
        @if($p->reviews_count)
        <div class="vp-score">
          <div class="vp-big"><b>{{ fa(number_format($p->rating_avg, 1)) }}</b><span class="stars">@for($s = 0; $s < 5; $s++)<x-icon name="star" class="sm"/>@endfor</span><small>{{ fa($p->reviews_count) }} نظر</small></div>
          @if($breakdown)<div class="rbd">@foreach($breakdown as $l => $v)<div><span>{{ $l }}</span><div class="rbd-bar"><i style="width:{{ $v / 5 * 100 }}%"></i></div><b>{{ fa(number_format($v, 1)) }}</b></div>@endforeach</div>@endif
        </div>
        @endif
        @forelse($p->reviews as $rv)
          <article class="rev">
            <div class="row"><a href="{{ route('guest.public', $rv->guest) }}"><img class="avatar sm" src="{{ $rv->guest->avatarUrl() }}" alt=""></a><div class="grow"><a href="{{ route('guest.public', $rv->guest) }}"><b>{{ $rv->guest->name }}</b></a>@if($rv->recommend) <span class="badge green" style="padding:0 8px;font-size:10.5px">پیشنهاد می‌کند</span>@endif<div class="xs muted">{{ jdate($rv->created_at, 'j F Y') }} · اقامت تأییدشده</div></div><span class="vp-rate"><x-icon name="star" class="sm"/><b>{{ fa($rv->rating) }}</b></span></div>
            @if($rv->comment)<p>{{ $rv->comment }}</p>@endif
            @if($rv->photos)<div class="rv-ph">@foreach($rv->photoUrls() as $u)<a href="{{ $u }}" data-lightbox><img src="{{ $u }}" alt=""></a>@endforeach</div>@endif
            @if($rv->host_reply)<div class="rev-reply"><b>پاسخ میزبان</b><p>{{ $rv->host_reply }}</p></div>@endif
          </article>
        @empty
          <p class="muted small" style="margin:0">هنوز نظری ثبت نشده است.</p>
        @endforelse
      </section>
    </div>

    {{-- ستون رزرو --}}
    <aside class="booking-box">
      <div class="vp-book"><div class="grip-b"></div><button type="button" class="vp-x sheet-x" data-close-book aria-label="بستن"><x-icon name="x" class="sm"/></button>
        <div class="vp-price"><span>شروع از</span><div><b>{{ toman($p->startingPrice(), false) }}</b><small>تومان / شب</small></div><em>میان‌هفته {{ toman($wdG, false) }} · آخر هفته {{ toman($weG, false) }}</em></div>
        @auth
          @if(auth()->user()->isGuest())
          <form id="booking-form" method="post" action="{{ route('guest.book', $p) }}" data-quote="{{ route('property.quote', $p) }}">
            @csrf
            <div class="rp has-prices" data-rangepicker data-unavailable='@json($unavailable)' data-prices='@json($prices)' data-base="{{ $p->price_weekday }}">
              <input type="hidden" name="check_in" data-in value="{{ old('check_in') }}"><input type="hidden" name="check_out" data-out value="{{ old('check_out') }}">
              <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب تاریخ</b></div><div><small>خروج</small><b data-label-out>انتخاب تاریخ</b></div></div>
              <div class="rp-pop glass"></div>
            </div>
            <div class="vp-guests">
              <div class="guest-pick"><div><b>بزرگسال</b><div class="xs muted">تا {{ fa($p->capacity_max) }} نفر</div></div><div class="counter" data-min="1" data-max="{{ $p->capacity_max }}"><button type="button" data-dec>−</button><b>{{ fa(old('adults', 2)) }}</b><button type="button" data-inc>+</button><input type="hidden" name="adults" value="{{ old('adults', min(2, $p->capacity_max)) }}"></div></div>
              <div class="guest-pick"><div><b>کودک</b><div class="xs muted">زیر ۱۲ سال</div></div><div class="counter" data-min="0" data-max="{{ $p->capacity_max }}"><button type="button" data-dec>−</button><b>{{ fa(old('children', 0)) }}</b><button type="button" data-inc>+</button><input type="hidden" name="children" value="{{ old('children', 0) }}"></div></div>
            </div>
            <input class="input" name="coupon" placeholder="کد تخفیف (اختیاری)" value="{{ old('coupon') }}" dir="ltr" style="text-transform:uppercase">
            <textarea class="input" name="note" placeholder="پیام برای میزبان (اختیاری)" rows="2">{{ old('note') }}</textarea>
            <div id="quote"></div>
            <button class="btn btn-lg btn-block" id="book-submit" disabled>@if($p->instant_booking)<x-icon name="bolt"/> رزرو فوری @else<x-icon name="send"/> ارسال درخواست رزرو @endif</button>
            <p class="vp-note">{{ $p->instant_booking ? 'پس از ثبت، برای پرداخت '.fa(setting('payment_window_minutes')).' دقیقه فرصت دارید.' : 'تا قبل از تأیید میزبان، مبلغی از شما کسر نمی‌شود.' }}</p>
          </form>
          @elseif(auth()->id() === $p->host_id)
            <div class="alert info" style="margin:0"><x-icon name="info"/>این اقامتگاه متعلق به شماست.</div>
            <a class="btn btn-block mt" href="{{ route('host.properties.edit', $p) }}">ویرایش اقامتگاه</a>
          @else
            <div class="alert info" style="margin:0"><x-icon name="info"/>برای رزرو، با حساب «مهمان» وارد شوید.</div>
          @endif
        @else
          <p class="small muted" style="margin:0 0 12px">برای دیدن قیمت نهایی و رزرو، وارد شوید یا ثبت‌نام کنید.</p>
          <a class="btn btn-lg btn-block" href="{{ route('login') }}">ورود و رزرو</a>
          <a class="btn btn-light btn-block mt" href="{{ route('register') }}">ثبت‌نام مهمان</a>
        @endauth
        <ul class="vp-trust"><li><x-i3 name="p_shield" class="sm"/>پرداخت امن</li><li><x-i3 name="p_coin" class="sm"/>قیمت شفاف</li><li><x-i3 name="p_phone" class="sm"/>پشتیبانی</li></ul>
      </div>
    </aside>
  </div>

  @if($similar->count())
  <section class="sec" style="padding-top:8px"><div class="sec-h"><h2>اقامتگاه‌های مشابه</h2></div><div class="grid-cards">@foreach($similar as $p2)@include('partials.pcard', ['p' => $p2])@endforeach</div></section>
  @endif
</div>
<div class="mobile-book"><div><small>شروع از هر شب</small><b class="mb-total">{{ toman($p->startingPrice()) }}</b></div>
  @auth @if(auth()->user()->isGuest())<button class="btn btn-lg" data-open-book><x-icon name="bolt"/> {{ $p->instant_booking ? 'رزرو فوری' : 'درخواست رزرو' }}</button>@elseif(auth()->id() === $p->host_id)<a class="btn" href="{{ route('host.properties.edit', $p) }}">ویرایش</a>@endif
  @else<button class="btn btn-lg" data-open-book>رزرو</button>@endauth</div>
<script>
(function(){var t=document.getElementById('vp-tabs');if(!t||!('IntersectionObserver' in window))return;
var links=[].slice.call(t.querySelectorAll('a')),map={};links.forEach(function(a){var s=document.querySelector(a.getAttribute('href'));if(s)map[s.id]=a});
var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){links.forEach(function(l){l.classList.remove('on')});map[e.target.id].classList.add('on');map[e.target.id].scrollIntoView({block:'nearest',inline:'center'})}})},{rootMargin:'-30% 0px -60% 0px'});
Object.keys(map).forEach(function(id){io.observe(document.getElementById(id))});
links.forEach(function(a){a.addEventListener('click',function(e){var s=document.querySelector(a.getAttribute('href'));if(s){e.preventDefault();var y=s.getBoundingClientRect().top+scrollY-110;scrollTo({top:y,behavior:'smooth'})}})})})();
</script>
@endsection
