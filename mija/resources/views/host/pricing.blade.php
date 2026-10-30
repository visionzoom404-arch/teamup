@extends('layouts.panel')
@section('title', 'سیاست نرخ‌گذاری')
@section('content')
@php
  $cm = \App\Support\Pricing::commissionFor($p->host);
  $v = fn ($k, $d = null) => old($k, $p->$k ?? $d);
  $dow = $p->price_dow ?? []; $mon = $p->price_months ?? []; $sea = $p->price_seasons ?? [];
  $isAdmin = auth()->user()->isAdmin(); $pre = $isAdmin ? 'admin.' : 'host.';
@endphp
<div class="page-h"><div><a href="{{ route($pre.'properties.edit', $p) }}" class="small muted">← {{ $p->title }}</a><h1>سیاست نرخ‌گذاری</h1></div>
  @unless($isAdmin)<a class="btn btn-sm btn-light" href="{{ route('host.calendar', $p) }}"><x-icon name="calendar" class="sm"/> تقویم</a>@endunless</div>

@include('partials.help', ['title' => 'قیمتی که وارد می‌کنید همان دریافتی خالص شماست', 'text' => 'هر عددی که برای شب وارد می‌کنید، همان مبلغی است که شما دریافت می‌کنید. می‌جا کمیسیون خود را به قیمتِ نمایش‌داده‌شده به مهمان اضافه می‌کند و جداگانه نشان نمی‌دهد. زیر هر کادر می‌بینید مهمان چه عددی را می‌بیند. <br><b>اولویت قیمت‌ها:</b> قیمت دلخواه همان روز در تقویم ← ایام پیک ← قیمت ماهانه ← قیمت فصلی ← قیمت روز هفته ← قیمت پایه (میان‌هفته/آخر هفته). در حالت «دستی» فقط قیمت پایه و قیمت‌هایی که خودتان روی تقویم می‌گذارید اعمال می‌شود.', 'open' => true])

<form method="post" action="{{ route($pre.'pricing.update', $p) }}" class="stack" id="price-form" data-cm="{{ $cm }}">@csrf
  <div class="card"><h3>مدیریت چرخه‌ی قیمت</h3>
    <div class="grid2">
      <label class="pm"><input type="radio" name="pricing_mode" value="auto" @checked($v('pricing_mode', 'auto') === 'auto')><div class="pm-b"><b><x-icon name="bolt" class="sm"/> خودکار</b><small>قیمت‌ها را براساس روز هفته، ماه، فصل و ایام پیک خودم اعمال کن</small></div></label>
      <label class="pm"><input type="radio" name="pricing_mode" value="manual" @checked($v('pricing_mode') === 'manual')><div class="pm-b"><b><x-icon name="edit" class="sm"/> دستی</b><small>فقط قیمت پایه + قیمت روزهایی که خودم در تقویم تعیین می‌کنم</small></div></label>
    </div></div>

  <div class="card"><h3>قیمت پایه (تومان در هر شب)</h3>
    <div class="grid3">
      <div class="field"><label>میان‌هفته (شنبه تا چهارشنبه)</label><input class="input" dir="ltr" type="number" name="price_weekday" value="{{ $v('price_weekday') }}" required data-net></div>
      <div class="field"><label>آخر هفته (پنجشنبه و جمعه)</label><input class="input" dir="ltr" type="number" name="price_weekend" value="{{ $v('price_weekend') }}" required data-net></div>
      <div class="field"><label>ایام پیک (نوروز و تعطیلات)</label><input class="input" dir="ltr" type="number" name="price_peak" value="{{ $v('price_peak') }}" data-net><div class="help">بازه‌های پیک را مدیر سایت تعیین می‌کند.</div></div>
    </div></div>

  <div class="card"><h3>نفرات و نفر اضافه</h3>
    <div class="grid3">
      <div class="field"><label>پایه‌ی نفرات (ظرفیت استاندارد)</label><input class="input" type="number" min="1" name="capacity_base" value="{{ $v('capacity_base') }}" required><div class="help">حداکثر ظرفیت: {{ fa($p->capacity_max) }} نفر</div></div>
      <div class="field"><label>هزینه‌ی هر نفر اضافه (در شب)</label><input class="input" dir="ltr" type="number" name="extra_guest_fee" value="{{ $v('extra_guest_fee', 0) }}" data-net><div class="help">مثلاً پایه ۴ نفر و هر نفر بیشتر ۷۰۰ هزار تومان.</div></div>
    </div></div>

  <div class="card"><h3>قیمت به‌تفکیک روز هفته</h3><p class="small muted">خالی بگذارید تا قیمت پایه اعمال شود.</p>
    <div class="price-grid dow">@foreach(config('mija.dows') as $i => $name)<div class="field"><label>{{ $name }}</label><input class="input" dir="ltr" type="number" name="price_dow[{{ $i }}]" value="{{ old('price_dow.'.$i, $dow[$i] ?? '') }}" data-net></div>@endforeach</div></div>

  <div class="card"><h3>قیمت ماهانه (ماه شمسی)</h3>
    <div class="price-grid mon">@foreach(\App\Support\Jalali::MONTHS as $i => $name)<div class="field"><label>{{ $name }}</label><input class="input" dir="ltr" type="number" name="price_months[{{ $i + 1 }}]" value="{{ old('price_months.'.($i + 1), $mon[$i + 1] ?? '') }}" data-net></div>@endforeach</div></div>

  <div class="card"><h3>قیمت فصلی</h3>
    <div class="price-grid sea">@foreach(config('mija.seasons') as $k => $name)<div class="field"><label>{{ $name }}</label><input class="input" dir="ltr" type="number" name="price_seasons[{{ $k }}]" value="{{ old('price_seasons.'.$k, $sea[$k] ?? '') }}" data-net></div>@endforeach</div></div>

  <div class="row pf-nav glass"><button class="btn btn-lg">ذخیره‌ی سیاست قیمت‌گذاری</button></div>
</form>

<div class="card mt"><h3><x-i3 name="p_bolt" class="sm"/> تخفیف فوری (لحظه‌آخری)</h3>
  <p class="small muted">اگر امروز یا فردا اقامتگاهتان خالی مانده، با تخفیف فوری در بخش «تخفیف لحظه‌آخری» صفحه‌ی اول نمایش داده می‌شود.</p>
  <form method="post" action="{{ route($pre.'flash', $p) }}" class="row wrap" style="gap:8px">@csrf
    <select name="days" style="width:auto"><option value="today">فقط امروز</option><option value="tomorrow">فقط فردا</option><option value="both">امروز و فردا</option></select>
    <select name="pct" style="width:auto">@foreach([10,15,20,25,30,40,50] as $d)<option value="{{ $d }}">{{ fa($d) }}٪ تخفیف</option>@endforeach</select>
    <button class="btn btn-gold"><x-icon name="bolt" class="sm"/> فعال کردن</button></form>
  @if($flash->count())<div class="row wrap" style="gap:8px;margin-top:10px">@foreach($flash as $f)<span class="badge red">{{ jdate($f->date, 'l j F') }} — {{ fa($f->discount_pct) }}٪</span>@endforeach
    <form method="post" action="{{ route($pre.'flash', $p) }}">@csrf<input type="hidden" name="days" value="clear"><button class="btn btn-sm btn-ghost">برداشتن همه</button></form></div>@endif
</div>

<div class="card mt"><h3><x-i3 name="p_calendar" class="sm"/> همگام‌سازی تقویم</h3>
  <p class="small muted">این لینک iCal را در گوگل‌کالندر یا سایت‌های دیگر وارد کنید تا رزروها و روزهای بسته‌ی این اقامتگاه همگام شود.</p>
  <div class="copybox"><span class="ltr small" id="ical">{{ route('ical', [$p, \App\Http\Controllers\Host\PropertyController::icalToken($p)]) }}</span><button type="button" class="btn btn-sm" data-copy="#ical"><x-icon name="share" class="sm"/> کپی</button></div>
</div>
<script>(function(){var f=document.getElementById('price-form'),cm=+f.dataset.cm;function fa(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬').replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
f.querySelectorAll('[data-net]').forEach(function(i){var s=document.createElement('small');s.className='gp';i.insertAdjacentElement('afterend',s);function u(){var v=+i.value;s.textContent=v>0?'مهمان می‌بیند: '+fa(Math.ceil(v/(1-cm/100)/1000)*1000)+' تومان':''}i.addEventListener('input',u);u()})})()</script>
@endsection
