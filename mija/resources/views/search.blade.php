@extends('layouts.app')
@section('title', 'جستجوی اقامتگاه')
@section('bare')
<div class="container">
  <div style="padding-top:20px">@include('partials.flash')</div>
  <form id="searchform" method="get" action="{{ route('search') }}">
  <div class="search-layout">
    <aside class="filters card" id="filters">
      <div class="row between"><h3 style="margin:0">فیلترها</h3><button type="button" class="btn btn-sm btn-icon btn-light filters-toggle" data-toggle-filters aria-label="بستن"><x-icon name="x" class="sm"/></button></div>
      <h4>تاریخ سفر</h4>
      <div class="rp" data-rangepicker data-unavailable="[]">
        <input type="hidden" name="check_in" data-in value="{{ request('check_in') }}"><input type="hidden" name="check_out" data-out value="{{ request('check_out') }}">
        <div class="rp-field"><div><small>ورود</small><b data-label-in>انتخاب</b></div><div><small>خروج</small><b data-label-out>انتخاب</b></div></div>
        <div class="rp-pop glass"></div>
      </div>
      <div class="presets" style="margin-top:8px"><span class="muted xs">پیشنهاد سریع:</span><button type="button" class="qchip" data-preset="weekend">این آخر هفته</button><button type="button" class="qchip" data-preset="weekend2">آخر هفته‌ی بعد</button><button type="button" class="qchip" data-preset="midweek">۳ شب میان‌هفته</button><button type="button" class="qchip" data-preset="soon">۳ شب از فردا</button></div>
      <h4>مقصد</h4>
      <select name="province" onchange="this.form.city && (this.form.city.value='')"><option value="">همه‌ی استان‌ها</option>@foreach(config('mija.provinces') as $pv => $cs)<option @selected(request('province')==$pv)>{{ $pv }}</option>@endforeach</select>
      <select name="region" style="margin-top:8px"><option value="">همه‌ی مناطق گردشگری</option>@foreach(config('mija.regions') as $rk => $rg)<option value="{{ $rk }}" @selected(request('region') == $rk)>{{ $rg['name'] }}</option>@endforeach</select>
      <input class="input" style="margin-top:8px" name="q" value="{{ request('q') }}" placeholder="نام شهر یا اقامتگاه…">
      <input type="hidden" name="city" value="{{ request('city') }}">
      <h4>نوع اقامتگاه</h4>
      <div class="chips">@foreach(config('mija.property_types') as $k => $n)<label class="check" style="padding:6px 12px"><input type="checkbox" name="type[]" value="{{ $k }}" @checked(in_array($k, (array) request('type')))>{{ $n }}</label>@endforeach</div>
      <h4>تعداد نفرات</h4>
      <select name="guests"><option value="">هر تعداد</option>@foreach([2,4,6,8,10,15,20] as $n)<option value="{{ $n }}" @selected(request('guests')==$n)>{{ fa($n) }} نفر و بیشتر</option>@endforeach</select>
      <h4>محدوده قیمت (هر شب، تومان)</h4>
      <div class="grid2"><input class="input" type="number" name="min_price" placeholder="از" value="{{ request('min_price') }}" dir="ltr"><input class="input" type="number" name="max_price" placeholder="تا" value="{{ request('max_price') }}" dir="ltr"></div>
      <h4>خواب</h4>
      <select name="bedrooms"><option value="">فرقی ندارد</option>@foreach([1,2,3,4,5] as $n)<option value="{{ $n }}" @selected(request('bedrooms')==$n)>{{ fa($n) }} خواب و بیشتر</option>@endforeach</select>
      <h4>امکانات</h4>
      <div class="chips">@foreach($amenities as $a)<label class="check" style="padding:6px 12px"><input type="checkbox" name="amenity[]" value="{{ $a->key }}" @checked(in_array($a->key, (array) request('amenity')))><x-i3 :name="$a->i3()" class="sm"/> {{ $a->name }}</label>@endforeach</div>
      <label class="check" style="margin-top:14px"><input type="checkbox" name="discount" value="1" @checked(request('discount'))><x-i3 name="p_tag" class="sm"/> فقط تخفیف‌دار</label>
      <label class="check" style="margin-top:8px"><input type="checkbox" name="instant" value="1" @checked(request('instant'))><x-icon name="bolt" class="sm"/> فقط رزرو فوری</label>
      <div class="row" style="margin-top:18px"><button class="btn grow">اعمال فیلتر</button><a class="btn btn-light" href="{{ route('search') }}">پاک کردن</a></div>
    </aside>

    <div>
      <div class="row between wrap mb">
        <div><h1 style="font-size:22px;margin:0">{{ request('province') ?: (request('city') ?: (config('mija.regions.'.request('region').'.name') ?: 'همه‌ی اقامتگاه‌ها')) }}</h1><div class="muted small">{{ fa($items->total()) }} اقامتگاه یافت شد @if($in && $out)· {{ jdate($in, 'j F') }} تا {{ jdate($out, 'j F') }}@endif</div></div>
        <div class="row" style="gap:8px">
          <button type="button" class="btn btn-sm btn-light filters-toggle" data-toggle-filters><x-icon name="filter" class="sm"/> فیلتر</button>
          <select name="sort" onchange="this.form.submit()" style="width:auto;min-height:36px;padding-block:5px"><option value="">پیشنهادی</option><option value="cheap" @selected(request('sort')=='cheap')>ارزان‌ترین</option><option value="expensive" @selected(request('sort')=='expensive')>گران‌ترین</option><option value="rating" @selected(request('sort')=='rating')>بالاترین امتیاز</option><option value="new" @selected(request('sort')=='new')>جدیدترین</option></select>
        </div>
      </div>
      @if($sponsored->count())<div class="sponsored"><div class="sp-lbl"><x-i3 name="p_trophy" class="sm"/> پیشنهادهای ویژه (تبلیغ)</div><div class="grid-cards">@foreach($sponsored as $ad)@php $p = $ad->property; @endphp<a class="ad-wrap" href="{{ route('ad.go', $ad) }}"><span class="ad-tag">تبلیغ</span>@include('partials.pcard', ['p' => $p, 'noLink' => true])</a>@endforeach</div></div>@endif
      @if($items->count())
        <div class="grid-cards">@foreach($items as $p)@include('partials.pcard')@endforeach</div>
        @include('partials.pager', ['paginator' => $items])
      @else
        <div class="card empty"><x-icon name="search"/><h3>اقامتگاهی پیدا نشد</h3><p>فیلترها یا تاریخ سفر را تغییر دهید.</p><a class="btn" href="{{ route('search') }}">پاک کردن فیلترها</a></div>
      @endif
    </div>
  </div>
  </form>
</div>
@endsection
