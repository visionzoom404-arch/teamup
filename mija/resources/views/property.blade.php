@extends('layouts.app')
@section('title', $p->title)
@section('desc', \Illuminate\Support\Str::limit(strip_tags($p->description), 150))
@push('head')<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">@endpush
@push('vendor')<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>@endpush
@section('bare')
@php $imgs = $p->images; $groups = ['feature' => 'امکانات ویژه', 'basic' => 'امکانات پایه', 'service' => 'خدمات', 'rules' => 'قوانین و پذیرش']; @endphp
<div class="container">
  <div style="padding-top:20px">@include('partials.flash')
  @if($p->status !== 'approved')<div class="alert warn"><x-icon name="info"/>این اقامتگاه هنوز منتشر نشده است ({{ ['draft'=>'پیش‌نویس','pending'=>'در انتظار تأیید','rejected'=>'ردشده','suspended'=>'تعلیق‌شده'][$p->status] ?? $p->status }}) و فقط برای شما قابل مشاهده است.</div>@endif</div>

  <div class="row between wrap" style="margin-top:6px">
    <div>
      <h1 style="font-size:clamp(22px,4vw,32px);margin-bottom:6px">{{ $p->title }}</h1>
      <div class="row wrap muted" style="gap:14px;font-size:13.5px">
        @if($p->reviews_count)<span class="stars"><x-icon name="star"/>{{ fa(number_format($p->rating_avg, 1)) }} <span class="muted" style="font-weight:500">({{ fa($p->reviews_count) }} نظر)</span></span>@endif
        <span class="row" style="gap:4px"><x-icon name="pin" class="sm"/>{{ $p->province }}، {{ $p->city }}</span>
        <span class="badge">{{ $p->typeLabel() }}</span>@if($p->instant_booking)<span class="badge orange"><x-icon name="bolt" class="sm"/> رزرو فوری</span>@endif
      </div>
    </div>
    <div class="row" style="gap:8px">
      <div class="share-pop"><button class="btn btn-sm btn-light" type="button" data-share-toggle><x-icon name="share" class="sm"/> اشتراک</button>
        <div class="share-menu glass"><a target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode($p->title.' — '.route('property', $p)) }}"><x-icon name="chat" class="sm"/> واتس‌اپ</a><a target="_blank" rel="noopener" href="https://t.me/share/url?url={{ urlencode(route('property', $p)) }}&text={{ urlencode($p->title) }}"><x-icon name="send" class="sm"/> تلگرام</a><button type="button" data-copy="#plink"><x-icon name="share" class="sm"/> کپی لینک</button><span id="plink" hidden>{{ route('property', $p) }}</span></div></div>
      @auth @if(auth()->user()->isGuest())<button class="btn btn-sm btn-light" data-fav="{{ route('favorite', $p) }}"><span class="fav {{ $isFav ? 'on' : '' }}" style="position:static;width:auto;height:auto;border:0;background:none;box-shadow:none;backdrop-filter:none"><x-icon name="heart" class="sm"/></span> ذخیره</button>@endif @endauth
    </div>
  </div>

  <div class="gallery">
    @forelse($imgs->take(5) as $i => $im)
      <a href="{{ $im->url() }}" data-lightbox><img src="{{ $im->url() }}" alt="{{ $p->title }}" @if($i) loading="lazy" @endif>@if($loop->last && $imgs->count() > 5)<span class="more btn btn-sm btn-light">+{{ fa($imgs->count() - 5) }} عکس</span>@endif</a>
    @empty
      <a href="#" style="grid-column:1/-1;grid-row:1/-1"><img src="{{ asset('img/placeholder.svg') }}" alt=""></a>
    @endforelse
  </div>
  @php $credits = $imgs->whereNotNull('credit')->unique('credit'); @endphp
  @if($credits->count())<div class="xs muted" style="margin-top:6px">عکس‌ها: @foreach($credits as $cr)<a href="{{ $cr->credit_url }}" target="_blank" rel="noopener" style="text-decoration:underline">{{ $cr->credit }}</a>@if(!$loop->last)، @endif @endforeach — <a href="https://unsplash.com/?utm_source=mija&utm_medium=referral" target="_blank" rel="noopener" style="text-decoration:underline">Unsplash</a></div>@endif
  <div class="gal-sw" data-swiper="gallery"><div class="swiper"><div class="swiper-wrapper">@forelse($imgs as $im)<div class="swiper-slide"><img src="{{ $im->url() }}" alt="{{ $p->title }}"></div>@empty<div class="swiper-slide"><img src="{{ asset('img/placeholder.svg') }}" alt=""></div>@endforelse</div></div><span class="count"></span></div>
  <div id="recent-data" hidden data-slug="{{ $p->slug }}" data-title="{{ $p->title }}" data-img="{{ $p->cover() }}" data-url="{{ route('property', $p) }}" data-price="{{ toman($p->startingPrice(), false) }}"></div>
  <div class="lightbox" id="lightbox"><button class="btn btn-light btn-icon"><x-icon name="x"/></button><img src="" alt=""></div>

  <div class="pshow">
    <div>
      <div class="facts">
        <span class="fact"><x-icon name="users"/>{{ fa($p->capacity_base) }} نفر (تا {{ fa($p->capacity_max) }} نفر)</span>
        <span class="fact"><x-icon name="bed"/>{{ fa($p->bedrooms) }} اتاق خواب · {{ fa($p->beds) }} تخت</span>
        <span class="fact"><x-icon name="bath"/>{{ fa($p->bathrooms) }} سرویس</span>
        @if($p->area)<span class="fact"><x-icon name="ruler"/>{{ fa($p->area) }} متر بنا</span>@endif
        @if($p->land_area)<span class="fact"><x-icon name="home"/>{{ fa($p->land_area) }} متر زمین</span>@endif
      </div>

      <div class="card flat row" style="gap:14px">
        <img class="avatar lg" style="width:60px;height:60px" src="{{ $p->host->avatarUrl() }}" alt="">
        <div class="grow"><div class="muted small">میزبان</div><a href="{{ route('host.public', $p->host) }}"><b>{{ $p->host->name }}</b></a> @if($p->host->host_verified)<span class="badge green"><x-icon name="shield" class="sm"/> هویت تأییدشده</span>@endif @if($hs['superhost'])<span class="badge gold"><x-i3 name="p_crown" class="sm"/> میزبان برتر</span>@endif<div class="xs muted">عضو از {{ jdate($p->host->created_at, 'F Y') }}@if($hs['response_rate'] !== null) · پاسخ‌گویی {{ fa($hs['response_rate']) }}٪@endif · <a href="{{ route('host.public', $p->host) }}" style="color:var(--red-d);font-weight:700">مشاهده پروفایل</a></div></div>
        <div class="small muted nowrap">ورود {{ fa($p->check_in_time) }} · خروج {{ fa($p->check_out_time) }}</div>
      </div>

      <hr class="divider">
      <h2>درباره‌ی اقامتگاه</h2>
      <p style="white-space:pre-line">{{ $p->description }}</p>

      <hr class="divider">
      <h2>امکانات</h2>
      @foreach($groups as $gk => $gl)
        @php $list = $p->amenities->where('grp', $gk); @endphp
        @if($list->count())<h4 class="muted" style="margin-top:12px">{{ $gl }}</h4><div class="amen">@foreach($list as $a)<div><i><x-i3 :name="$a->i3()" class="sm"/></i>{{ $a->name }}</div>@endforeach</div>@endif
      @endforeach

      <hr class="divider">
      <h2>قوانین و شرایط</h2>
      <div class="grid2">
        <div class="card flat"><h3><x-icon name="clock" class="sm"/> ورود و خروج</h3><p class="small">ورود از ساعت {{ fa($p->check_in_time) }} · خروج تا ساعت {{ fa($p->check_out_time) }}</p><p class="small">حداقل اقامت {{ fa($p->min_nights) }} شب، حداکثر {{ fa($p->max_nights) }} شب</p>@if($p->extra_guest_fee)<p class="small">هزینه‌ی هر نفر اضافه (بالای {{ fa($p->capacity_base) }} نفر): {{ toman($p->extra_guest_fee) }} در شب</p>@endif</div>
        <div class="card flat"><h3><x-icon name="shield" class="sm"/> سیاست لغو: {{ $p->policy()['name'] }}</h3>@php $tiers = $p->policy()['tiers']; $d0 = $tiers[0][0]; @endphp
        <div class="cx"><div class="cx-bar">@foreach($tiers as $i => [$days, $pct])<span style="flex:1;--c:{{ $pct >= 100 ? 'var(--green)' : '#F5B301' }}"><b>{{ fa($pct) }}٪</b></span>@endforeach<span style="flex:1;--c:var(--sale)"><b>۰٪</b></span></div>
          <div class="cx-lbl">@foreach($tiers as [$days, $pct])<span>تا {{ fa($days) }} روز مانده</span>@endforeach<span>کمتر</span></div></div>
        <p class="small" style="margin-top:8px">{{ $p->policy()['desc'] }}</p><a class="small" style="color:var(--red-d);font-weight:700" href="{{ route('page', 'rules') }}">جزئیات قوانین</a></div>
      </div>
      @if($p->rules)<div class="card flat mt"><h3>قوانین میزبان</h3><p class="small" style="white-space:pre-line;margin:0">{{ $p->rules }}</p></div>@endif
      @if($p->weekly_discount || $p->monthly_discount)<div class="alert info mt"><x-icon name="tag"/><div>@if($p->weekly_discount)تخفیف اقامت هفتگی ({{ fa(7) }} شب به بالا): {{ fa($p->weekly_discount) }}٪ @endif @if($p->monthly_discount) · تخفیف ماهانه: {{ fa($p->monthly_discount) }}٪@endif</div></div>@endif

      <hr class="divider">
      <h2>موقعیت</h2>
      @if($p->lat && $p->lng)<div class="map-l" data-map data-lat="{{ $p->lat }}" data-lng="{{ $p->lng }}"></div><p class="xs muted" style="margin-top:6px">موقعیت تقریبی نمایش داده شده است؛ آدرس دقیق پس از قطعی شدن رزرو ارسال می‌شود.</p>@else<div class="mapbox"><div class="center"><x-icon name="pin" class="lg" /><div><b>{{ $p->province }}، {{ $p->city }}</b></div>@if($p->lat && $p->lng)<a class="btn btn-sm btn-blue mt" target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat={{ $p->lat }}&mlon={{ $p->lng }}#map=14/{{ $p->lat }}/{{ $p->lng }}">مشاهده روی نقشه</a>@else<div class="xs muted">آدرس دقیق پس از قطعی شدن رزرو ارسال می‌شود</div>@endif</div></div>@endif

      <hr class="divider">
      <div class="row between"><h2>نظرات مهمانان</h2>@if($p->reviews_count)<span class="stars" style="font-size:18px"><x-icon name="star"/>{{ fa(number_format($p->rating_avg, 1)) }}</span>@endif</div>
      @if($breakdown)<div class="rbd">@foreach($breakdown as $l => $v)<div><span>{{ $l }}</span><div class="rbd-bar"><i style="width:{{ $v / 5 * 100 }}%"></i></div><b>{{ fa(number_format($v, 1)) }}</b></div>@endforeach</div>@endif
      @forelse($p->reviews as $rv)
        <div class="rev">
          <div class="row"><img class="avatar sm" src="{{ $rv->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $rv->guest->name }}</b> <span class="badge green" style="padding:0 8px;font-size:11px"><x-icon name="shield" class="sm"/> اقامت تأییدشده</span><div class="xs muted">{{ jdate($rv->created_at, 'j F Y') }}</div></div><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span></div>
          @if($rv->comment)<p style="margin:8px 0 0">{{ $rv->comment }}</p>@endif
          @if($rv->host_reply)<div class="card flat" style="margin-top:10px;padding:12px 16px"><b class="small">پاسخ میزبان</b><p class="small" style="margin:4px 0 0">{{ $rv->host_reply }}</p></div>@endif
        </div>
      @empty
        <p class="muted">هنوز نظری ثبت نشده است.</p>
      @endforelse
    </div>

    {{-- ستون رزرو --}}
    <aside class="booking-box">
      <div class="card" style="padding:22px"><div class="grip-b"></div><button type="button" class="btn btn-sm btn-icon btn-light sheet-x" data-close-book aria-label="بستن" style="position:absolute;top:14px;inset-inline-end:14px"><x-icon name="x" class="sm"/></button>
        <div class="row between" style="align-items:baseline"><div><span class="muted small">شروع از</span><div><b style="font-size:24px;color:var(--red-d)">{{ toman($p->startingPrice(), false) }}</b> <span class="muted small">تومان / شب</span></div></div></div>
        <div class="xs muted" style="margin-bottom:12px">میان‌هفته {{ toman($p->price_weekday, false) }} · آخر هفته {{ toman($p->price_weekend, false) }}</div>
        @auth
          @if(auth()->user()->isGuest())
          <form id="booking-form" method="post" action="{{ route('guest.book', $p) }}" data-quote="{{ route('property.quote', $p) }}">
            @csrf
            <div class="rp has-prices" data-rangepicker data-unavailable='@json($unavailable)' data-prices='@json($prices)' data-base="{{ $p->price_weekday }}">
              <input type="hidden" name="check_in" data-in value="{{ old('check_in') }}"><input type="hidden" name="check_out" data-out value="{{ old('check_out') }}">
              <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب تاریخ</b></div><div><small>خروج</small><b data-label-out>انتخاب تاریخ</b></div></div>
              <div class="rp-pop glass"></div>
            </div>
            <div style="margin-top:10px">
              <div class="guest-pick"><div><b>بزرگسال</b><div class="xs muted">ظرفیت تا {{ fa($p->capacity_max) }} نفر</div></div><div class="counter" data-min="1" data-max="{{ $p->capacity_max }}"><button type="button" data-dec>−</button><b>{{ fa(old('adults', 2)) }}</b><button type="button" data-inc>+</button><input type="hidden" name="adults" value="{{ old('adults', min(2, $p->capacity_max)) }}"></div></div>
              <div class="guest-pick"><div><b>کودک</b><div class="xs muted">زیر ۱۲ سال</div></div><div class="counter" data-min="0" data-max="{{ $p->capacity_max }}"><button type="button" data-dec>−</button><b>{{ fa(old('children', 0)) }}</b><button type="button" data-inc>+</button><input type="hidden" name="children" value="{{ old('children', 0) }}"></div></div>
            </div>
            <input class="input" name="coupon" placeholder="کد تخفیف (اختیاری)" value="{{ old('coupon') }}" style="margin-bottom:10px;text-transform:uppercase" dir="ltr">
            <textarea class="input" name="note" placeholder="پیام برای میزبان (اختیاری)" style="min-height:64px;margin-bottom:10px">{{ old('note') }}</textarea>
            <div id="quote" style="margin:8px 0 14px"></div>
            <button class="btn btn-lg btn-block" id="book-submit" disabled>@if($p->instant_booking)<x-icon name="bolt"/> رزرو فوری @else<x-icon name="send"/> ارسال درخواست رزرو @endif</button>
            <p class="xs muted center" style="margin:10px 0 0">{{ $p->instant_booking ? 'پس از ثبت، برای پرداخت '.fa(setting('payment_window_minutes')).' دقیقه فرصت دارید.' : 'تا قبل از تأیید میزبان، مبلغی از شما کسر نمی‌شود.' }}</p>
          </form>
          @elseif(auth()->id() === $p->host_id)
            <div class="alert info" style="margin:0"><x-icon name="info"/>این اقامتگاه متعلق به شماست.</div>
            <a class="btn btn-block mt" href="{{ route('host.properties.edit', $p) }}">ویرایش اقامتگاه</a>
          @else
            <div class="alert info" style="margin:0"><x-icon name="info"/>برای رزرو، با حساب «مهمان» وارد شوید.</div>
          @endif
        @else
          <p class="small muted">برای مشاهده‌ی قیمت نهایی و رزرو، وارد شوید یا ثبت‌نام کنید.</p>
          <a class="btn btn-lg btn-block" href="{{ route('login') }}">ورود و رزرو</a>
          <a class="btn btn-light btn-block mt" href="{{ route('register') }}">ثبت‌نام مهمان</a>
        @endauth
      </div>
    </aside>
  </div>

  @if($similar->count())
  <section class="sec" style="padding-top:0"><h2>اقامتگاه‌های مشابه</h2><div class="grid-cards">@foreach($similar as $p2)@include('partials.pcard', ['p' => $p2])@endforeach</div></section>
  @endif
</div>
<div class="mobile-book glass"><div><div class="xs muted">شروع از هر شب</div><b class="mb-total">{{ toman($p->startingPrice()) }}</b></div>
  @auth @if(auth()->user()->isGuest())<button class="btn btn-lg" data-open-book><x-icon name="bolt"/> {{ $p->instant_booking ? 'رزرو فوری' : 'درخواست رزرو' }}</button>@elseif(auth()->id() === $p->host_id)<a class="btn" href="{{ route('host.properties.edit', $p) }}">ویرایش</a>@endif
  @else<button class="btn btn-lg" data-open-book>رزرو</button>@endauth</div>
@endsection
