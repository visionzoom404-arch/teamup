@extends('layouts.panel')
@section('title', 'تقویم '.$p->title)
@section('content')
<div class="page-h"><div><a href="{{ route('host.properties.index') }}" class="small muted">← اقامتگاه‌ها</a><h1>تقویم و قیمت‌گذاری</h1><div class="muted">{{ $p->title }}</div></div><a class="btn btn-sm btn-blue" href="{{ route('host.pricing', $p) }}"><x-icon name="tag" class="sm"/> سیاست نرخ‌گذاری</a></div>
@include('partials.help', ['title' => 'راهنمای تقویم', 'text' => 'قیمت زیر هر روز، دریافتی خالص شما برای همان شب است (با اعمال سیاست نرخ‌گذاری). روی روزها بزنید (انتخاب چندتایی) و قیمت دلخواه بگذارید یا آن‌ها را ببندید/باز کنید. رزروهای قطعی با نام مهمان نشان داده می‌شوند.'])
<div class="card">
  <div class="row between" style="margin-bottom:14px">
    <a class="btn btn-sm btn-light btn-icon" href="{{ route('host.calendar', [$p, 'ym' => $next]) }}" aria-label="ماه بعد"><x-icon name="chev-r" class="sm"/></a>
    <h3 style="margin:0">{{ \App\Support\Jalali::MONTHS[$jm - 1] }} {{ fa($jy) }}</h3>
    <a class="btn btn-sm btn-light btn-icon" href="{{ route('host.calendar', [$p, 'ym' => $prev]) }}" aria-label="ماه قبل"><x-icon name="chev-l" class="sm"/></a>
  </div>
  <div class="cal" id="host-cal">
    @foreach(['ش','ی','د','س','چ','پ','ج'] as $d)<div class="cal-dow">{{ $d }}</div>@endforeach
    @for($i = 0; $i < $offset; $i++)<div class="cal-empty"></div>@endfor
    @foreach($cells as $d => $c)
      @php $col = ($offset + $d - 1) % 7; @endphp
      <div class="cal-cell {{ $c['past'] ? 'past' : '' }} {{ $col === 6 ? 'fri' : '' }} {{ $c['blocked'] ? 'blocked' : '' }} {{ $c['booking'] ? 'booked' : '' }}" data-date="{{ $c['date'] }}">
        <b>{{ fa($d) }}</b>
        @if($c['booking'])<span class="who">{{ $c['booking']->guest->name }}</span>
        @elseif($c['blocked'])<span class="xs">بسته</span>
        @else<span class="pr">{{ fa(number_format($c['eff'] / 1000)) }}هز</span>@if($c['discount'])<span class="fl-tag">{{ fa($c['discount']) }}٪−</span>@endif @endif
      </div>
    @endforeach
  </div>
  <div class="row wrap xs muted" style="gap:14px;margin-top:14px"><span><i style="display:inline-block;width:12px;height:12px;border-radius:4px;background:linear-gradient(135deg,#FF8A5A,#FFB21A);vertical-align:-2px"></i> رزرو شده</span><span><i style="display:inline-block;width:12px;height:12px;border-radius:4px;background:repeating-linear-gradient(45deg,#bbb 0 3px,#eee 3px 6px);vertical-align:-2px"></i> بسته</span><span style="color:var(--orange)">● قیمت دلخواه (هزار تومان)</span></div>
</div>

<form method="post" action="{{ route('host.calendar.update', $p) }}" class="cal-bar card glass" id="cal-bar">
  @csrf <div id="cal-dates"></div><input type="hidden" name="action" id="cal-action" value="block">
  <div class="row wrap" style="gap:8px">
    <b><span id="cal-count">۰</span> روز انتخاب شد</b><span class="grow"></span>
    <input class="input" style="max-width:160px;padding:7px 12px" type="number" name="price" placeholder="قیمت (تومان)" dir="ltr">
    <button class="btn btn-sm btn-gold" data-cal-action="price">اعمال قیمت</button>
    <button class="btn btn-sm btn-dark" data-cal-action="block">بستن</button>
    <button class="btn btn-sm btn-green" data-cal-action="open">باز کردن</button>
    <button class="btn btn-sm btn-ghost" data-cal-action="reset">بازنشانی</button>
    <button type="button" class="btn btn-sm btn-light btn-icon" id="cal-clear" aria-label="لغو"><x-icon name="x" class="sm"/></button>
  </div>
</form>
<p class="small muted mt">روی روزها بزنید (انتخاب چندتایی)، سپس عملیات دلخواه را انجام دهید. پنج‌شنبه و جمعه به‌طور خودکار قیمت آخر هفته دارند.</p>
@endsection
