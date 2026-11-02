@extends('layouts.panel')
@section('title', 'آمار بازدید')
@push('vendor')<script src="{{ asset('vendor/chart.umd.min.js') }}"></script>@endpush
@section('content')
@php
  $n = fn ($x) => fa(number_format((int) $x));
  $labels = collect($series)->pluck('label')->all();
  $c1 = ['type' => 'bar', 'labels' => $labels, 'data' => collect($series)->pluck('views')->all(), 'unit' => ' بازدید'];
  $c2 = ['type' => 'line', 'labels' => $labels, 'data' => collect($series)->pluck('uniq')->all(), 'unit' => ' نفر', 'color' => 'rgba(47,111,85,.35)', 'border' => '#2F6F55'];
  $c3 = ['type' => 'bar', 'labels' => array_map(fn ($h) => fa($h), range(0, 23)), 'data' => array_values($hours), 'unit' => ' بازدید'];
  $d1 = ['labels' => $devices->map(fn ($d) => $devNames[$d->k] ?? $d->k)->all(), 'data' => $devices->pluck('u')->all()];
  $max = fn ($c, $k = 'v') => max(1, (int) collect($c)->max($k));
  $ago = fn ($t) => \Illuminate\Support\Carbon::parse($t)->diffForHumans();
  $pct = fn ($a, $b) => $b ? fa(round($a / $b * 100, 1)).'٪' : '—';
  $trend = $yesterday ? round(($today - $yesterday) / $yesterday * 100) : null;
@endphp
<div class="page-h"><div><h1>آمار بازدید سایت</h1><div class="muted">بازدیدها، منبع ورودی‌ها، دستگاه‌ها و رفتار کاربران — فقط انسان؛ ربات‌ها جدا نشان داده می‌شوند</div></div>
  <div class="tabs" style="margin:0">@foreach([1 => 'امروز', 7 => '۷ روز', 14 => '۱۴ روز', 30 => '۳۰ روز', 90 => '۹۰ روز', 365 => 'یک سال'] as $d => $l)<a href="{{ route('admin.analytics', ['days' => $d]) }}" class="{{ $range === $d ? 'on' : '' }}">{{ $l }}</a>@endforeach</div></div>

@unless($hasData)
<div class="alert"><x-icon name="info"/><div>هنوز بازدیدی ثبت نشده است. از همین لحظه که کاربران صفحه‌ها را باز کنند، اینجا پر می‌شود. (بازدیدهای خود مدیران شمرده نمی‌شود.)</div></div>
@endunless

<div class="tiles">
  <div class="tile card"><div class="ic3"><x-i3 name="trend"/></div><b>{{ $n($views) }}</b><span>بازدید صفحه ({{ $range === 1 ? 'امروز' : fa($range).' روز اخیر' }})</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="family"/></div><b>{{ $n($uniq) }}</b><span>بازدیدکننده یکتا</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="sparkle"/></div><b>{{ $n($new) }}</b><span>بازدیدکننده‌ی جدید</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_compass"/></div><b>{{ $uniq ? fa(round($views / $uniq, 1)) : '—' }}</b><span>میانگین صفحه برای هر نفر</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_calendar"/></div><b>{{ $n($today) }}</b><span>امروز ({{ $n($todayU) }} نفر)@if($trend !== null)<em class="an-tr {{ $trend >= 0 ? 'up' : 'dn' }}">{{ $trend >= 0 ? '↑' : '↓' }}{{ fa(abs($trend)) }}٪ نسبت به دیروز</em>@endif</span></div>
  <div class="tile card"><div class="ic3"><x-i3 name="p_bell"/></div><b>{{ $n($online) }}</b><span>آنلاین در ۵ دقیقه‌ی اخیر</span></div>
</div>

<div class="grid2 mb" style="gap:18px;align-items:start">
  <div class="card"><h3><x-i3 name="trend" class="sm"/> بازدید صفحه به تفکیک روز</h3><div class="chart-box"><canvas data-chart="{{ json_encode($c1, JSON_UNESCAPED_UNICODE) }}"></canvas></div></div>
  <div class="card"><h3><x-i3 name="family" class="sm"/> بازدیدکننده‌ی یکتا به تفکیک روز</h3><div class="chart-box"><canvas data-chart="{{ json_encode($c2, JSON_UNESCAPED_UNICODE) }}"></canvas></div></div>
</div>

<div class="grid2 mb" style="gap:18px;align-items:start">
  <div class="card"><h3><x-i3 name="p_compass" class="sm"/> منبع ورودی (از کجا آمدند؟)</h3>
    @forelse($sources as $s)@php $w = round($s->v / $sourceTotal * 100); @endphp
    <div class="an-row"><span class="an-k">{{ $srcNames[$s->k] ?? $s->k }}</span><span class="an-bar"><i style="width:{{ max(3, $w) }}%"></i></span><span class="an-v">{{ $n($s->v) }} <small>({{ fa($w) }}٪)</small></span></div>
    @empty<div class="muted small">داده‌ای نیست.</div>@endforelse
    @if($refs->count())<hr class="divider"><div class="muted xs mb">سایت‌های ارجاع‌دهنده‌ی برتر</div>@foreach($refs as $f)<div class="an-row"><span class="an-k ltr">{{ $f->k }}</span><span class="an-bar"><i style="width:{{ max(3, round($f->v / $max($refs) * 100)) }}%"></i></span><span class="an-v">{{ $n($f->v) }}</span></div>@endforeach @endif
  </div>
  <div class="card"><h3><x-i3 name="p_phone" class="sm"/> دستگاه، مرورگر و سیستم‌عامل</h3>
    <div class="chart-box" style="height:190px"><canvas data-donut="{{ json_encode($d1, JSON_UNESCAPED_UNICODE) }}"></canvas></div>
    <div class="an-3">
      <div><div class="muted xs mb">مرورگر</div>@foreach($browsers as $b)<div class="an-mini"><span>{{ $b->k }}</span><b>{{ $n($b->u) }}</b></div>@endforeach</div>
      <div><div class="muted xs mb">سیستم‌عامل</div>@foreach($oses as $b)<div class="an-mini"><span>{{ $b->k }}</span><b>{{ $n($b->u) }}</b></div>@endforeach</div>
      <div><div class="muted xs mb">نوع کاربر</div><div class="an-mini"><span>ناشناس</span><b>{{ $n($roles['anon'] ?? 0) }}</b></div><div class="an-mini"><span>مهمان وارد‌شده</span><b>{{ $n($roles['guest'] ?? 0) }}</b></div><div class="an-mini"><span>میزبان</span><b>{{ $n($roles['host'] ?? 0) }}</b></div></div>
    </div>
  </div>
</div>

<div class="grid2 mb" style="gap:18px;align-items:start">
  <div class="card"><h3><x-i3 name="receipt" class="sm"/> پربازدیدترین صفحه‌ها</h3>
    @forelse($pages as $p)<div class="an-row"><span class="an-k" title="{{ $p->path }}">{{ \Illuminate\Support\Str::limit($p->label ?: $p->path, 46) }}</span><span class="an-bar"><i style="width:{{ max(3, round($p->v / $max($pages) * 100)) }}%"></i></span><span class="an-v">{{ $n($p->v) }} <small>/ {{ $n($p->u) }} نفر</small></span></div>@empty<div class="muted small">داده‌ای نیست.</div>@endforelse
  </div>
  <div class="card"><h3><x-i3 name="p_house" class="sm"/> پربازدیدترین اقامتگاه‌ها</h3>
    @forelse($props as $p)<div class="an-row"><span class="an-k">{{ \Illuminate\Support\Str::limit($titles[$p->k] ?? '—', 40) }}</span><span class="an-bar"><i style="width:{{ max(3, round($p->v / $max($props) * 100)) }}%"></i></span><span class="an-v">{{ $n($p->v) }} <small>/ {{ $n($p->u) }} نفر</small></span></div>@empty<div class="muted small">داده‌ای نیست.</div>@endforelse
    @if($searches->count())<hr class="divider"><div class="muted xs mb">عبارت‌هایی که کاربران جستجو کردند</div>@foreach($searches as $s)<div class="an-row"><span class="an-k">{{ $s->k }}</span><span class="an-bar"><i style="width:{{ max(3, round($s->v / $max($searches) * 100)) }}%"></i></span><span class="an-v">{{ $n($s->v) }}</span></div>@endforeach @endif
  </div>
</div>

<div class="grid2 mb" style="gap:18px;align-items:start">
  <div class="card"><h3><x-i3 name="p_calendar" class="sm"/> ساعت‌های پربازدید</h3><div class="chart-box"><canvas data-chart="{{ json_encode($c3, JSON_UNESCAPED_UNICODE) }}"></canvas></div></div>
  <div class="card"><h3><x-i3 name="p_coin" class="sm"/> از بازدید تا رزرو ({{ $range === 1 ? 'امروز' : fa($range).' روز' }})</h3>
    @php $f = $funnel; $steps = [['بازدیدکننده', $f['visitors']], ['دیدن حداقل یک اقامتگاه', $f['viewers']], ['ثبت‌نام جدید', $f['regs']], ['درخواست/رزرو ثبت‌شده', $f['books']], ['رزرو پرداخت‌شده', $f['paid']]]; $fm = max(1, $f['visitors']); @endphp
    @foreach($steps as $s)<div class="an-row"><span class="an-k">{{ $s[0] }}</span><span class="an-bar"><i style="width:{{ max(3, round(min(1, $s[1] / $fm) * 100)) }}%"></i></span><span class="an-v">{{ $n($s[1]) }}</span></div>@endforeach
    <div class="muted xs mt">نرخ رزرو به بازدیدکننده: <b>{{ $pct($f['books'], $f['visitors']) }}</b> · نرخ ثبت‌نام: <b>{{ $pct($f['regs'], $f['visitors']) }}</b></div>
  </div>
</div>

<div class="grid2 mb" style="gap:18px;align-items:start">
  <div class="card"><h3><x-i3 name="p_shield" class="sm"/> ربات‌های موتور جستجو (برای سرچ کنسول)</h3>
    @forelse($bots as $b)<div class="an-row"><span class="an-k">{{ $b->k }}</span><span class="an-bar"><i style="width:{{ max(3, round($b->v / $max($bots) * 100)) }}%"></i></span><span class="an-v">{{ $n($b->v) }} <small>آخرین: {{ $ago($b->last) }}</small></span></div>@empty<div class="muted small">هنوز ربات موتور جستجویی نیامده است. بعد از ثبت سایت در سرچ کنسول و ارسال sitemap، معمولاً Googlebot ظرف چند روز می‌آید.</div>@endforelse
    <div class="muted xs mt">بازدید ربات‌ها در آمار بالا حساب نمی‌شود.</div>
  </div>
  <div class="card"><h3><x-i3 name="bolt" class="sm"/> آخرین بازدیدها (زنده)</h3>
    <div class="an-feed">@forelse($recent as $v)<div class="an-f"><b>{{ \Illuminate\Support\Str::limit($v->label ?: $v->path, 38) }}</b><span>{{ $srcNames[$v->source] ?? ($v->source === 'internal' ? 'داخلی' : $v->source) }} · {{ $devNames[$v->device] ?? '' }} · {{ $v->browser }}@if($v->role) · {{ $v->role === 'host' ? 'میزبان' : 'مهمان' }}@endif</span><em>{{ $ago($v->created_at) }}</em></div>@empty<div class="muted small">—</div>@endforelse</div>
  </div>
</div>
@endsection
