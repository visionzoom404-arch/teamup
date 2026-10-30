@extends('layouts.panel')
@section('title', 'تبلیغات')
@section('content')
@php $u = auth()->user(); $types = config('mija.ad_types');
  $price = ['banner' => (int) setting('ad_banner_day'), 'click' => (int) setting('ad_click_cpc'), 'ladder' => (int) setting('ad_ladder'), 'sms' => (int) setting('ad_sms_each'), 'push' => (int) setting('ad_push_each')];
  $icons = ['banner' => 'p_camera', 'click' => 'p_search', 'ladder' => 'p_trophy', 'sms' => 'p_phone', 'push' => 'p_bell']; @endphp
<div class="page-h"><div><h1>تبلیغات</h1><div class="muted">اقامتگاه خود را بیشتر دیده شود.</div></div><div class="badge gold" style="font-size:13px;padding:5px 14px">موجودی کیف پول: {{ toman($u->wallet) }}</div></div>
@unless($on)<div class="alert warn"><x-icon name="info"/>بخش تبلیغات فعلاً توسط مدیر سایت غیرفعال شده است.</div>@endunless
@include('partials.help', ['title' => 'انواع تبلیغات', 'text' => '<b>بنری:</b> بنر اقامتگاه شما چند روز در صفحه‌ی اول می‌چرخد. <b>کلیکی:</b> اقامتگاه شما بالای نتایج جستجو دیده می‌شود و فقط به‌ازای هر کلیک پول کسر می‌شود. <b>نردبان:</b> اقامتگاه برای ۲۴ ساعت به ابتدای فهرست می‌آید. <b>پیامکی:</b> پیامک تبلیغاتی به مهمانان علاقه‌مند ارسال می‌شود. <b>اعلان:</b> نوتیفیکیشن داخل سایت برای مهمانان. هزینه از کیف پول شما کسر می‌شود و بودجه‌ی مصرف‌نشده یا ردشده بازمی‌گردد.'])

@if($on)
<div class="ad-types">
  @foreach($types as $k => [$name, $desc])
  <button type="button" class="ad-type {{ $loop->first ? 'on' : '' }}" data-ad="{{ $k }}"><x-i3 name="{{ $icons[$k] }}"/><b>{{ $name }}</b><small>{{ $desc }}</small><em>{{ $k === 'click' ? toman($price[$k]).' / کلیک' : ($k === 'banner' ? toman($price[$k]).' / روز' : ($k === 'ladder' ? toman($price[$k]).' / ۲۴ ساعت' : toman($price[$k]).' / هر مخاطب')) }}</em></button>
  @endforeach
</div>
@if($props->isEmpty())<div class="alert warn"><x-icon name="info"/>برای تبلیغ، ابتدا یک اقامتگاه تأییدشده لازم است.</div>
@else
<form method="post" action="{{ route('host.ads.store') }}" class="card mt" id="ad-form">@csrf
  <input type="hidden" name="type" id="ad-type" value="banner">
  <h3>ثبت تبلیغ <span id="ad-title-t" class="badge orange">بنری</span></h3>
  <div class="grid2">
    <div class="field"><label>اقامتگاه</label><select name="property_id">@foreach($props as $pr)<option value="{{ $pr->id }}">{{ $pr->title }}</option>@endforeach</select>@error('property_id')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field" id="f-units"><label id="units-l">تعداد روز</label><input class="input" dir="ltr" type="number" name="units" id="units" min="1" value="7">@error('units')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field" data-for="banner"><label>شروع بنر</label><select name="starts_on"><option value="{{ today()->toDateString() }}">از امروز</option><option value="{{ today()->addDay()->toDateString() }}">از فردا</option></select></div>
    <div class="field" data-for="sms push"><label>مخاطبان</label><select name="audience"><option value="all">همه‌ی مهمانان</option>@foreach(config('mija.regions') as $rk => $rg)<option value="{{ $rk }}">علاقه‌مند به {{ $rg['name'] }}</option>@endforeach</select></div>
    <div class="field" data-for="banner sms push"><label>عنوان کوتاه</label><input class="input" name="title" maxlength="80" placeholder="مثلاً: ویلای استخردار با ۲۰٪ تخفیف">@error('title')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field" data-for="sms push"><label>متن پیام</label><input class="input" name="text" maxlength="200" placeholder="متن کوتاه تبلیغ">@error('text')<div class="msg-e">{{ $message }}</div>@enderror</div>
  </div>
  <div class="ad-cost"><span>هزینه‌ی کل:</span><b id="ad-cost">—</b><small id="ad-note" class="muted"></small></div>
  @error('type')<div class="msg-e">{{ $message }}</div>@enderror
  <button class="btn btn-lg">پرداخت از کیف پول و ثبت تبلیغ</button>
</form>
<script>(function(){var P={!! json_encode($price) !!},min={{ (int) setting('ad_click_min') }},T={!! json_encode(collect($types)->map(fn($t) => $t[0]), JSON_UNESCAPED_UNICODE) !!};
var f=document.getElementById('ad-form'),ty=document.getElementById('ad-type'),un=document.getElementById('units'),L=document.getElementById('units-l'),c=document.getElementById('ad-cost'),nt=document.getElementById('ad-note'),ft=document.getElementById('f-units');
function fa(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬').replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
function upd(){var k=ty.value,u=+un.value||0,cost=0;
 f.querySelectorAll('[data-for]').forEach(function(e){e.hidden=e.dataset.for.split(' ').indexOf(k)<0});ft.hidden=k==='ladder';
 if(k==='banner'){cost=u*P.banner;L.textContent='تعداد روز';nt.textContent=''}
 else if(k==='click'){cost=Math.max(u,min);L.textContent='بودجه‌ی کلیک (تومان)';nt.textContent=' ≈ '+fa(Math.floor(cost/P.click))+' کلیک؛ باقی‌مانده بازمی‌گردد'}
 else if(k==='ladder'){cost=P.ladder;nt.textContent='۲۴ ساعت در بالای فهرست'}
 else{cost=u*(k==='sms'?P.sms:P.push);L.textContent=k==='sms'?'تعداد پیامک':'تعداد اعلان';nt.textContent='پس از تأیید مدیر ارسال می‌شود'}
 c.textContent=fa(cost)+' تومان';document.getElementById('ad-title-t').textContent=T[k]}
document.querySelectorAll('.ad-type').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.ad-type').forEach(function(x){x.classList.toggle('on',x===b)});ty.value=b.dataset.ad;un.value={banner:7,click:200000,ladder:1,sms:100,push:200}[b.dataset.ad];upd()})});
un.addEventListener('input',upd);upd()})()</script>
@endif
@endif

<div class="card mt"><h3>تبلیغات من</h3>
  @forelse($ads as $a)
  <div class="item-row" style="margin-bottom:8px"><div class="grow"><div class="row between wrap"><b>{{ $a->typeLabel() }} — {{ $a->property?->title }}</b><span class="badge {{ ['pending' => 'gold', 'active' => 'green', 'finished' => 'dark', 'rejected' => 'red', 'paused' => 'gray'][$a->status] }}">{{ ['pending' => 'در انتظار تأیید', 'active' => 'فعال', 'finished' => 'پایان یافته', 'rejected' => 'رد شده', 'paused' => 'متوقف'][$a->status] }}</span></div>
    <div class="small muted" style="margin-top:4px">بودجه {{ toman($a->budget) }} · مصرف‌شده {{ toman($a->spent) }} @if($a->type === 'click')· نمایش {{ fa($a->impressions) }} · کلیک {{ fa($a->clicks) }}@endif @if(in_array($a->type, ['sms', 'push']) && $a->sent)· ارسال‌شده {{ fa($a->sent) }}@endif @if($a->ends_on && $a->type !== 'click')· تا {{ jdate($a->ends_on, 'j F') }}@endif</div>
    @if($a->admin_note)<div class="xs" style="color:var(--sale)">{{ $a->admin_note }}</div>@endif</div>
    @if(in_array($a->status, ['pending', 'active']) && $a->type !== 'ladder')<form method="post" action="{{ route('host.ads.stop', $a) }}" onsubmit="return confirm('تبلیغ متوقف و باقی‌مانده‌ی بودجه بازگردانده شود؟')">@csrf<button class="btn btn-sm btn-ghost">توقف</button></form>@endif</div>
  @empty<p class="muted small">هنوز تبلیغی ثبت نکرده‌اید.</p>@endforelse</div>
@endsection
