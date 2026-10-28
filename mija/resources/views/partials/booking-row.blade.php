{{-- $b: Booking، $to: لینک جزئیات، $who: نام طرف مقابل --}}
<a href="{{ $to }}" class="item-row">
  <img class="thumb" src="{{ $b->property->cover() }}" alt="">
  <div class="grow">
    <div class="row between wrap"><b>{{ $b->property->title }}</b><span class="badge {{ $b->statusColor() }}">{{ $b->statusLabel() }}</span></div>
    <div class="small muted" style="margin-top:4px">{{ jdate($b->check_in, 'l j F') }} ← {{ jdate($b->check_out, 'l j F') }} · {{ fa($b->nights) }} شب · {{ fa($b->guestsCount()) }} نفر</div>
    <div class="row between wrap small" style="margin-top:6px"><span class="muted">{{ $who ?? '' }} <span class="ltr">{{ $b->code }}</span></span><b>{{ toman($b->total) }}</b></div>
  </div>
</a>
