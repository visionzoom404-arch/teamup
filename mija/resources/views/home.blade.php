@extends('layouts.app')
@section('title', 'رزرو ویلا و کلبه')
@section('bare')
<div class="container">
  {{-- ===== بالای صفحه: عنوان فشرده + جستجو ===== --}}
  <section class="hero-c">
    <div class="hero-top">
      <div>
        <span class="kicker"><x-icon name="sparkle" class="sm"/> اقامتگاه‌های منتخب ایران</span>
        <h1>جای خوب، سفر خوب.</h1>
        <p>ویلا، کلبه و اقامتگاه‌های تأییدشده؛ مستقیم از میزبان، با قیمت شفاف و پرداخت امن.</p>
      </div>
      <div class="hero-stats">
        <div><b data-count="{{ $stats['properties'] }}">{{ fa($stats['properties']) }}</b><small>اقامتگاه</small></div>
        <div><b data-count="{{ $stats['cities'] }}">{{ fa($stats['cities']) }}</b><small>شهر و منطقه</small></div>
        @if($stats['rating'])<div><b data-count="{{ $stats['rating'] }}" data-dec="1">{{ fa($stats['rating']) }}</b><small>امتیاز مهمانان</small></div>@endif
      </div>
    </div>

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
      <button class="btn"><x-icon name="search"/> جستجو</button>
      <div class="presets"><span class="muted">پیشنهاد سریع</span><button type="button" class="qchip" data-preset="weekend">این آخر هفته</button><button type="button" class="qchip" data-preset="weekend2">آخر هفته‌ی بعد</button><button type="button" class="qchip" data-preset="midweek">۳ شب میان‌هفته</button><button type="button" class="qchip" data-preset="soon">۳ شب از فردا</button></div>
    </form>

    {{-- نوار نوع اقامتگاه --}}
    <div class="tstrip">
      @foreach(config('mija.property_types') as $k => $n)
        <a class="tcell" href="{{ route('search', ['type' => [$k]]) }}"><x-i3 :name="config('mija.type_icons.'.$k)"/>{{ $n }}<small>{{ fa($typeCounts[$k] ?? 0) }} مورد</small></a>
      @endforeach
    </div>

    {{-- مرکز دسته‌بندی: همه‌چیز ریز و مرتب --}}
    <div class="hub">
      <div class="hub-g"><b>بر اساس منطقه</b><div class="hub-chips">
        @foreach(config('mija.regions') as $k => $rg)<a class="chip-s" href="{{ route('search', ['region' => $k]) }}"><x-icon name="pin"/>{{ $rg['name'] }}<i>{{ fa($regionCounts[$k] ?? 0) }}</i></a>@endforeach
      </div></div>
      <div class="hub-g"><b>بر اساس امکانات</b><div class="hub-chips">
        @foreach([['pool','استخر'],['jacuzzi','جکوزی'],['sauna','سونا'],['fireplace','شومینه'],['bbq','باربیکیو'],['garden','باغ و حیاط'],['view','ویوی دریا/کوه'],['wifi','اینترنت'],['pet','حیوان خانگی'],['family','مناسب خانواده'],['ev','شارژر برقی']] as [$k,$n])<a class="chip-s" href="{{ route('search', ['amenity' => [$k]]) }}"><x-i3 :name="$k"/>{{ $n }}</a>@endforeach
      </div></div>
      <div class="hub-g"><b>بر اساس ظرفیت</b><div class="hub-chips">
        <a class="chip-s" href="{{ route('search', ['guests' => 2]) }}"><x-icon name="users"/>دونفره</a>
        <a class="chip-s" href="{{ route('search', ['guests' => 4]) }}"><x-icon name="users"/>تا ۴ نفر</a>
        <a class="chip-s" href="{{ route('search', ['guests' => 8]) }}"><x-icon name="users"/>۸ نفر و بیشتر</a>
        <a class="chip-s" href="{{ route('search', ['guests' => 15]) }}"><x-icon name="users"/>گروهی (۱۵+)</a>
      </div></div>
      <div class="hub-g"><b>بر اساس بودجه</b><div class="hub-chips">
        <a class="chip-s" href="{{ route('search', ['max_price' => 2000000]) }}">زیر ۲ میلیون</a>
        <a class="chip-s" href="{{ route('search', ['min_price' => 2000000, 'max_price' => 4000000]) }}">۲ تا ۴ میلیون</a>
        <a class="chip-s" href="{{ route('search', ['min_price' => 4000000]) }}">بالای ۴ میلیون</a>
        <a class="chip-s" href="{{ route('search', ['discount' => 1]) }}"><x-icon name="tag"/>تخفیف‌دار</a>
        <a class="chip-s" href="{{ route('search', ['instant' => 1]) }}"><x-icon name="bolt"/>رزرو فوری</a>
      </div></div>
    </div>
  </section>

  @if($banners->count())
  <section class="sec ad-banners"><div class="ab-row">@foreach($banners as $ad)@php $bp = $ad->property; @endphp
    <a class="ab" href="{{ route('ad.go', $ad) }}"><img src="{{ $bp->cover() }}" alt=""><div><span class="ad-tag">تبلیغ</span><b>{{ $ad->title ?: $bp->title }}</b><small>{{ $bp->province }}، {{ $bp->city }} · از {{ toman($bp->startingPrice(), false) }} تومان</small></div></a>@endforeach</div></section>
  @endif
  @if($flash->count())
  <section class="sec flash-sec"><div class="sec-h"><div><h2><x-i3 name="p_bolt" class="sm"/> تخفیف لحظه‌آخری</h2><p>فقط برای امروز و فردا؛ میزبان‌ها با تخفیف ویژه، سریع رزرو کنید.</p></div></div><div class="grid-cards">@foreach($flash as $p)@include('partials.pcard')@endforeach</div></section>
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
    <div class="grid-cards">@foreach($list->take(4) as $p)@include('partials.pcard')@endforeach</div>
  </section>
  @endif
  @endforeach

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

  <section class="sec" style="max-width:820px;margin-inline:auto">
    <div class="sec-h"><div><h2>پرسش‌های متداول</h2></div></div>
    <div class="faq">
      <details><summary>رزرو فوری با رزرو درخواستی چه فرقی دارد؟</summary><p>در رزرو فوری بلافاصله پرداخت می‌کنید و رزرو قطعی می‌شود. در رزرو درخواستی ابتدا میزبان باید تأیید کند و سپس پرداخت انجام می‌شود.</p></details>
      <details><summary>اگر رزروم را لغو کنم چه مقدار پول برمی‌گردد؟</summary><p>بسته به سیاست لغو اقامتگاه و زمان لغو، بخشی یا کل مبلغ به کیف پول شما بازمی‌گردد. جزئیات در صفحه‌ی <a href="{{ route('page', 'rules') }}" style="color:var(--brand-d);font-weight:600">قوانین</a> آمده است.</p></details>
      <details><summary>پول من کی به دست میزبان می‌رسد؟</summary><p>وجه تا روز ورود نزد می‌جا می‌ماند و پس از ورود مهمان، به کیف پول میزبان واریز می‌شود.</p></details>
      <details><summary>چطور اقامتگاهم را ثبت کنم؟</summary><p>با حساب «میزبان» ثبت‌نام کنید، اطلاعات و عکس‌ها را وارد کنید و برای بررسی ارسال کنید. ثبت کاملاً رایگان است.</p></details>
    </div>
  </section>

  <section class="sec">
    <div class="cta"><div class="row wrap between" style="gap:20px">
      <div style="max-width:560px"><h2>ملک دارید؟ میزبان می‌جا شوید.</h2><p>اقامتگاه را رایگان ثبت کنید، تقویم و قیمت را خودتان مدیریت کنید و درآمد را مستقیم به حساب بانکی‌تان بگیرید.</p></div>
      <a class="btn btn-gold btn-lg" href="{{ route('register', ['as' => 'host']) }}">ثبت اقامتگاه <x-icon name="arrow-l"/></a>
    </div></div>
  </section>
</div>
@endsection
