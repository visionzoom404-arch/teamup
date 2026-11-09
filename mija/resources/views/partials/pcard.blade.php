@php $fav = auth()->check() && auth()->user()->favorites->contains('id', $p->id); $wrap = ($noLink ?? false) ? 'div' : 'a'; @endphp
<{{ $wrap }} @if(! ($noLink ?? false)) href="{{ route('property', $p) }}" @endif class="pcard">
  <div class="ph">
    <img src="{{ $p->cover() }}" alt="{{ $p->title }}" loading="lazy">
    <span class="pp">@if(isset($p->quote_total)){{ toman($p->quote_total, false) }}@else{{ toman($p->startingPrice(), false) }}@endif <small>تومان</small></span>
    @php $disc = max($p->weekly_discount, $p->monthly_discount); $sh = $p->host && $p->host->isSuperhost(); @endphp
    <div class="tags">@if($disc > 0)<span class="tag-sale">تا {{ fa($disc) }}٪ تخفیف</span>@endif</div>
    @if($p->featured || $p->instant_booking || $sh)
    <div class="pc-marks">
      @if($p->featured)<span class="pc-mk gold" title="ویژه" aria-label="ویژه"><x-icon name="star"/></span>@endif
      @if($p->instant_booking)<span class="pc-mk" title="رزرو فوری" aria-label="رزرو فوری"><x-icon name="bolt"/></span>@endif
      @if($sh)<span class="pc-mk gold" title="میزبان برتر" aria-label="میزبان برتر"><x-i3 name="p_crown"/></span>@endif
    </div>
    @endif
    @auth @if(auth()->user()->isGuest())
    <button type="button" class="fav {{ $fav ? 'on' : '' }}" data-fav="{{ route('favorite', $p) }}" aria-label="علاقه‌مندی"><x-icon name="heart"/></button>
    @endif @endauth
  </div>
  <div class="pb">
    <div class="row between"><h3>{{ $p->title }}</h3>@if($p->reviews_count)<span class="stars"><x-icon name="star"/>{{ fa(number_format($p->rating_avg, 1)) }}</span>@endif</div>
    <div class="muted small row" style="gap:4px"><x-icon name="pin" class="sm"/>{{ $p->province }}، {{ $p->city }} · {{ $p->typeLabel() }}</div>
    <div class="meta"><span><x-icon name="users" class="sm"/>تا {{ fa($p->capacity_max) }} نفر</span><span><x-icon name="bed" class="sm"/>{{ fa($p->bedrooms) }} خواب</span>@if($p->area)<span><x-icon name="ruler" class="sm"/>{{ fa($p->area) }} متر</span>@endif</div>
    @if(isset($p->quote_total))<div class="price"><small>{{ fa($p->quote_nights) }} شب، قیمت نهایی</small><div><b>{{ toman($p->quote_total, false) }}</b> <small>تومان</small></div></div>@else<div class="price"><small>شروع از هر شب</small><div><b>{{ toman($p->startingPrice(), false) }}</b> <small>تومان</small></div></div>@endif
  </div>
</{{ $wrap }}>
