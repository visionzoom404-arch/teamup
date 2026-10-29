@php $fav = auth()->check() && auth()->user()->favorites->contains('id', $p->id); @endphp
<a href="{{ route('property', $p) }}" class="pcard">
  <div class="ph">
    <img src="{{ $p->cover() }}" alt="{{ $p->title }}" loading="lazy">
    <div class="tags">
      @if(max($p->weekly_discount, $p->monthly_discount) > 0)<span class="tag-sale">تا {{ fa(max($p->weekly_discount, $p->monthly_discount)) }}٪ تخفیف</span>@endif
      @if($p->featured)<span class="tag-glass gold"><x-icon name="star" class="sm"/> ویژه</span>@endif
      @if($p->instant_booking)<span class="tag-glass"><x-icon name="bolt" class="sm"/> رزرو فوری</span>@endif
      @if($p->host && $p->host->isSuperhost())<span class="tag-glass gold"><x-i3 name="p_crown" class="sm"/> میزبان برتر</span>@endif
    </div>
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
</a>
