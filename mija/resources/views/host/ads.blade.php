@extends('layouts.panel')
@section('title', 'تبلیغات')
@section('content')
@php $u = auth()->user(); $types = config('mija.ad_types');
  $price = ['banner' => (int) setting('ad_banner_day'), 'click' => (int) setting('ad_click_cpc'), 'ladder' => (int) setting('ad_ladder'), 'sms' => (int) setting('ad_sms_each'), 'push' => (int) setting('ad_push_each')];
  $icons = ['banner' => 'p_camera', 'click' => 'p_search', 'ladder' => 'p_trophy', 'sms' => 'p_phone', 'push' => 'p_bell'];
  $where = ['banner' => 'صفحه‌ی اول سایت، اسلایدر بنرها (می‌چرخد)', 'click' => 'بالای نتایج جستجو، بخش «پیشنهادهای ویژه»', 'ladder' => 'بالاترین ردیف فهرست جستجو برای ۲۴ ساعت', 'sms' => 'پیامک روی گوشی مهمانان هدف', 'push' => 'اعلان داخل سایت و اپلیکیشن مهمانان'];
  $oldType = old('type', 'click'); @endphp
<div class="page-h"><div><h1>تبلیغات</h1><div class="muted">اقامتگاه خود را بیشتر دیده شود.</div></div><div class="badge gold" style="font-size:13px;padding:5px 14px">موجودی کیف پول: {{ toman($u->wallet) }}</div></div>
@unless($on)<div class="alert warn"><x-icon name="info"/>بخش تبلیغات فعلاً توسط مدیر سایت غیرفعال شده است.</div>@endunless
@include('partials.flash')
@include('partials.help', ['title' => 'انواع تبلیغات', 'text' => '<b>بنری:</b> بنر اقامتگاه شما چند روز در اسلایدر صفحه‌ی اول می‌چرخد. <b>کلیکی:</b> اقامتگاه شما بالای نتایج جستجو دیده می‌شود و فقط به‌ازای هر کلیک پول کسر می‌شود. <b>نردبان:</b> اقامتگاه برای ۲۴ ساعت به بالای فهرست می‌آید. <b>پیامکی و اعلان:</b> پیام شما برای مهمانان هدف ارسال می‌شود. در پیش‌نمایش زنده، شکل و جایگاه دقیق تبلیغ را قبل از پرداخت می‌بینید. کنار همه‌ی تبلیغ‌ها برچسب ریز «AD» نمایش داده می‌شود.'])

@if($on)
<div class="ad-types">
  @foreach($types as $k => [$name, $desc])
  <button type="button" class="ad-type {{ $oldType === $k ? 'on' : '' }}" data-ad="{{ $k }}"><x-i3 name="{{ $icons[$k] }}"/><b>{{ $name }}</b><small>{{ $desc }}</small><em>{{ $k === 'click' ? toman($price[$k]).' / کلیک' : ($k === 'banner' ? toman($price[$k]).' / روز' : ($k === 'ladder' ? toman($price[$k]).' / ۲۴ ساعت' : toman($price[$k]).' / هر مخاطب')) }}</em></button>
  @endforeach
</div>
@if($props->isEmpty())<div class="alert warn"><x-icon name="info"/>برای تبلیغ، ابتدا یک اقامتگاه تأییدشده لازم است.</div>
@else
<div class="ad-wrapx mt">
<form method="post" action="{{ route('host.ads.store') }}" class="card" id="ad-form" enctype="multipart/form-data">@csrf
  <input type="hidden" name="type" id="ad-type" value="{{ $oldType }}">
  <h3>ثبت تبلیغ <span id="ad-title-t" class="badge orange">{{ $types[$oldType][0] }}</span></h3>
  <div class="grid2">
    <div class="field"><label>اقامتگاه</label><select name="property_id" id="ad-prop">@foreach($props as $pr)<option value="{{ $pr->id }}" data-t="{{ $pr->title }}" data-c="{{ $pr->province }}، {{ $pr->city }}" data-p="{{ toman($pr->startingPrice(), false) }}" data-i="{{ $pr->cover() }}" @selected((string) old('property_id') === (string) $pr->id)>{{ $pr->title }}</option>@endforeach</select>@error('property_id')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field" id="f-units"><label id="units-l">تعداد روز</label><input class="input" dir="ltr" type="number" name="units" id="units" min="1" value="{{ old('units', 200000) }}">@error('units')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field" data-for="banner"><label>شروع بنر</label><select name="starts_on"><option value="{{ today()->toDateString() }}">از امروز</option><option value="{{ today()->addDay()->toDateString() }}">از فردا</option></select></div>
    <div class="field" data-for="sms push"><label>مخاطبان</label><select name="audience"><option value="all">همه‌ی مهمانان</option>@foreach(config('mija.regions') as $rk => $rg)<option value="{{ $rk }}">علاقه‌مند به {{ $rg['name'] }}</option>@endforeach</select></div>
    <div class="field" data-for="banner click sms push"><label>عنوان کوتاه</label><input class="input" name="title" id="ad-t" maxlength="80" value="{{ old('title') }}" placeholder="مثلاً: ویلای استخردار با ۲۰٪ تخفیف">@error('title')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field" data-for="banner click sms push"><label>متن تبلیغ</label><input class="input" name="text" id="ad-x" maxlength="200" value="{{ old('text') }}" placeholder="متن کوتاه تبلیغ">@error('text')<div class="msg-e">{{ $message }}</div>@enderror</div>
  </div>

  <div class="bn-box" data-for="banner">
    <div class="bn-mode"><label><input type="radio" name="banner_mode" value="upload" @checked(old('banner_mode', 'upload') === 'upload')><span><x-icon name="image" class="sm"/> بنر خودم را بارگذاری می‌کنم</span></label><label><input type="radio" name="banner_mode" value="support" @checked(old('banner_mode') === 'support')><span><x-icon name="chat" class="sm"/> پشتیبانی برایم طراحی کند</span></label></div>
    <div id="bn-upload">
      <div class="bn-size"><div class="bn-frame"><i>۱۲۰۰ × ۴۲۰</i><span class="safe">محدوده‌ی امن متن</span></div>
        <ul><li><b>اندازه‌ی پیشنهادی:</b> ۱۲۰۰ × ۴۲۰ پیکسل (نسبت ≈ ۲٫۹ به ۱)</li><li><b>حداقل:</b> ۹۰۰ × ۲۸۰ پیکسل — فرمت JPG، PNG یا WebP — حداکثر ۳ مگابایت</li><li>متن و لوگو را در <b>وسط ۶۰٪</b> تصویر بگذارید؛ در گوشی لبه‌ها کمی بریده می‌شود.</li></ul></div>
      <div class="field"><label>فایل بنر</label><input class="input" type="file" name="image" id="bn-file" accept="image/png,image/jpeg,image/webp">@error('image')<div class="msg-e">{{ $message }}</div>@enderror</div>
    </div>
    <div id="bn-support" hidden><div class="field"><label>چه نوشته‌ای و با چه حال‌وهوایی روی بنر باشد؟</label><textarea class="input" name="support_text" rows="3" maxlength="500" placeholder="مثلاً: ویلای استخردار با ویوی جنگل، ۲۰٪ تخفیف تا آخر هفته؛ رنگ‌ها گرم و نارنجی">{{ old('support_text') }}</textarea>@error('support_text')<div class="msg-e">{{ $message }}</div>@enderror<div class="help">پشتیبانی بنر را از روی عکس‌های اقامتگاه‌تان می‌سازد و به شما اطلاع می‌دهد؛ هزینه همان هزینه‌ی بنر است.</div></div></div>
  </div>

  <div class="ad-cost"><span>هزینه‌ی کل:</span><b id="ad-cost">—</b><small id="ad-note" class="muted"></small></div>
  @error('type')<div class="msg-e">{{ $message }}</div>@enderror
  <button class="btn btn-lg">پرداخت از کیف پول و ثبت تبلیغ</button>
</form>

{{-- پیش‌نمایش زنده: چه نوشته‌ای، کجای سایت، چه شکلی --}}
<aside class="adp card" aria-label="پیش‌نمایش زنده">
  <div class="adp-h"><b><x-icon name="eye" class="sm"/> پیش‌نمایش زنده</b><span class="badge gold" id="adp-sample">نمونه</span></div>
  <div class="adp-where"><x-icon name="pin" class="sm"/> جایگاه: <b id="adp-where">{{ $where[$oldType] }}</b></div>

  {{-- بنری: صفحه‌ی اول --}}
  <div class="adp-v" data-pv="banner"><div class="mk-site"><div class="mk-bar"><i></i><i></i><i></i><span></span></div><div class="mk-body">
    <div class="mk-line w60"></div><div class="mk-search"></div>
    <div class="mk-slot"><span class="mk-tag">جایگاه بنر شما</span><div class="mk-banner"><img id="pv-b-img" alt=""><span class="ad-tag">AD</span><div class="mk-txt"><b id="pv-b-t">عنوان بنر شما</b><small id="pv-b-x">متن کوتاه تبلیغ</small></div><div class="mk-dots"><i class="on"></i><i></i><i></i></div></div></div>
    <div class="mk-cats"><i></i><i></i><i></i><i></i></div><div class="mk-line w40"></div></div></div></div>

  {{-- کلیکی: نتایج جستجو --}}
  <div class="adp-v" data-pv="click"><div class="mk-site"><div class="mk-bar"><i></i><i></i><i></i><span></span></div><div class="mk-body">
    <div class="mk-filter"><i></i><i></i><i></i></div>
    <div class="mk-slot"><span class="mk-tag">جایگاه تبلیغ کلیکی</span><div class="mk-sp"><small class="mk-spl">پیشنهادهای ویژه</small>
      <div class="mk-card"><img id="pv-c-img" alt=""><span class="ad-tag">AD</span><div><b id="pv-c-t">عنوان تبلیغ شما</b><em id="pv-c-x">متن کوتاه تبلیغ</em><small id="pv-c-c">استان، شهر</small><strong id="pv-c-p">از ۰ تومان</strong></div></div></div></div>
    <div class="mk-cards"><i></i><i></i></div></div></div></div>

  {{-- نردبان --}}
  <div class="adp-v" data-pv="ladder"><div class="mk-site"><div class="mk-bar"><i></i><i></i><i></i><span></span></div><div class="mk-body">
    <div class="mk-rows"><div class="mk-row up"><img id="pv-l-img" alt=""><div><b id="pv-l-t">اقامتگاه شما</b><small id="pv-l-c"></small></div><span class="mk-lad">نردبان ⬆</span></div>
      <div class="mk-row"><i></i><div><u></u><u class="s"></u></div></div><div class="mk-row"><i></i><div><u></u><u class="s"></u></div></div><div class="mk-row"><i></i><div><u></u><u class="s"></u></div></div></div></div></div></div>

  {{-- پیامکی --}}
  <div class="adp-v" data-pv="sms"><div class="ph"><div class="ph-notch"></div><div class="ph-scr sms"><div class="ph-top">پیام‌ها · <b>می‌جا</b></div>
    <div class="sms-b"><p id="pv-s-t"></p><a>https://mija.click/p/…</a><small>می‌جا</small></div><div class="sms-time">همین حالا</div></div></div></div>

  {{-- اعلان --}}
  <div class="adp-v" data-pv="push"><div class="ph"><div class="ph-notch"></div><div class="ph-scr lock"><div class="lock-t">۱۰:۴۵</div><div class="lock-d">امروز</div>
    <div class="ph-notif lk"><img src="{{ asset('icons/icon-192.png') }}" alt=""><div><small>می‌جا · همین حالا</small><b id="pv-p-t">عنوان اعلان</b><span id="pv-p-x">متن اعلان</span></div></div></div></div></div>
  <p class="xs muted" style="margin:10px 0 0">این فقط پیش‌نمایش است؛ شکل نهایی روی دستگاه‌های مختلف کمی متفاوت می‌شود. برچسب کوچک قرمز «AD» روی همه‌ی تبلیغ‌ها نمایش داده می‌شود.</p>
</aside>
</div>
<script>(function(){var P={!! json_encode($price) !!},min={{ (int) setting('ad_click_min') }},T={!! json_encode(collect($types)->map(fn($t) => $t[0]), JSON_UNESCAPED_UNICODE) !!},W={!! json_encode($where, JSON_UNESCAPED_UNICODE) !!};
var $=function(i){return document.getElementById(i)},f=$('ad-form'),ty=$('ad-type'),un=$('units'),L=$('units-l'),c=$('ad-cost'),nt=$('ad-note'),ft=$('f-units'),pr=$('ad-prop'),tt=$('ad-t'),xx=$('ad-x'),file=$('bn-file');
var DEF={banner:7,click:200000,ladder:1,sms:100,push:200};
function fa(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬').replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})}
function opt(){return pr.options[pr.selectedIndex]||{dataset:{}}}
var userImg=null;
function pv(){var k=ty.value,o=opt().dataset,t=tt.value.trim(),x=xx.value.trim(),sample=!t&&!x;
 var T0=t||(o.t||'عنوان تبلیغ شما'),X0=x||(k==='sms'?'پیشنهاد ویژه برای رزرو آخر هفته':'متن کوتاه تبلیغ شما اینجا نوشته می‌شود');
 document.querySelectorAll('.adp-v').forEach(function(e){e.hidden=e.dataset.pv!==k});$('adp-where').textContent=W[k];$('adp-sample').hidden=!sample&&k!=='ladder';
 $('pv-b-img').src=userImg||o.i;$('pv-b-t').textContent=T0;$('pv-b-x').textContent=X0;
 $('pv-c-img').src=o.i;$('pv-c-t').textContent=T0;$('pv-c-x').textContent=X0;$('pv-c-c').textContent=o.c;$('pv-c-p').textContent='از '+o.p+' تومان';
 $('pv-l-img').src=o.i;$('pv-l-t').textContent=o.t;$('pv-l-c').textContent=o.c;
 $('pv-s-t').textContent=(t?t+' — ':'')+X0;$('pv-p-t').textContent=T0;$('pv-p-x').textContent=X0}
function upd(){var k=ty.value,u=+un.value||0,cost=0;
 f.querySelectorAll('[data-for]').forEach(function(e){e.hidden=e.dataset.for.split(' ').indexOf(k)<0});ft.hidden=k==='ladder';
 if(k==='banner'){cost=u*P.banner;L.textContent='تعداد روز';nt.textContent=''}
 else if(k==='click'){cost=Math.max(u,min);L.textContent='بودجه‌ی کلیک (تومان)';nt.textContent=' ≈ '+fa(Math.floor(cost/P.click))+' کلیک؛ باقی‌مانده بازمی‌گردد'}
 else if(k==='ladder'){cost=P.ladder;nt.textContent='۲۴ ساعت در بالای فهرست'}
 else{cost=u*(k==='sms'?P.sms:P.push);L.textContent=k==='sms'?'تعداد پیامک':'تعداد اعلان';nt.textContent='پس از تأیید مدیر ارسال می‌شود'}
 c.textContent=fa(cost)+' تومان';$('ad-title-t').textContent=T[k];pv()}
document.querySelectorAll('.ad-type').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.ad-type').forEach(function(x){x.classList.toggle('on',x===b)});ty.value=b.dataset.ad;un.value=DEF[b.dataset.ad];upd()})});
[un,tt,xx,pr].forEach(function(e){e.addEventListener('input',upd);e.addEventListener('change',upd)});
if(file)file.addEventListener('change',function(){var fl=file.files[0];if(!fl){userImg=null;pv();return}var r=new FileReader();r.onload=function(){userImg=r.result;pv()};r.readAsDataURL(fl)});
document.querySelectorAll('[name=banner_mode]').forEach(function(r){r.addEventListener('change',function(){var s=document.querySelector('[name=banner_mode]:checked').value==='support';$('bn-upload').hidden=s;$('bn-support').hidden=!s})});
(function(){var s=document.querySelector('[name=banner_mode]:checked');if(s&&s.value==='support'){$('bn-upload').hidden=true;$('bn-support').hidden=false}})();
if(!{{ old('units') ? 'true' : 'false' }})un.value=DEF[ty.value];upd()})()</script>
@endif
@endif

<div class="card mt"><h3>تبلیغات من</h3>
  @forelse($ads as $a)
  <div class="item-row" style="margin-bottom:8px"><div class="grow"><div class="row between wrap"><b>{{ $a->typeLabel() }} — {{ $a->property?->title }}</b>@if($a->type === 'banner' && $a->image)<a href="{{ $a->bannerUrl() }}" target="_blank" class="ad-th"><img src="{{ $a->bannerUrl() }}" alt=""></a>@endif @if($a->support_request && ! $a->image)<span class="badge gold" style="padding:0 8px">پشتیبانی در حال ساخت بنر</span>@endif<span class="badge {{ ['pending' => 'gold', 'active' => 'green', 'finished' => 'dark', 'rejected' => 'red', 'paused' => 'gray'][$a->status] }}">{{ ['pending' => 'در انتظار تأیید', 'active' => 'فعال', 'finished' => 'پایان یافته', 'rejected' => 'رد شده', 'paused' => 'متوقف'][$a->status] }}</span></div>
    <div class="small muted" style="margin-top:4px">بودجه {{ toman($a->budget) }} · مصرف‌شده {{ toman($a->spent) }} @if($a->type === 'click')· نمایش {{ fa($a->impressions) }} · کلیک {{ fa($a->clicks) }}@endif @if(in_array($a->type, ['sms', 'push']) && $a->sent)· ارسال‌شده {{ fa($a->sent) }}@endif @if($a->ends_on && $a->type !== 'click')· تا {{ jdate($a->ends_on, 'j F') }}@endif</div>
    @if($a->admin_note)<div class="xs" style="color:var(--sale)">{{ $a->admin_note }}</div>@endif</div>
    @if(in_array($a->status, ['pending', 'active']) && $a->type !== 'ladder')<form method="post" action="{{ route('host.ads.stop', $a) }}" onsubmit="return confirm('تبلیغ متوقف و باقی‌مانده‌ی بودجه بازگردانده شود؟')">@csrf<button class="btn btn-sm btn-ghost">توقف</button></form>@endif</div>
  @empty<p class="muted small">هنوز تبلیغی ثبت نکرده‌اید.</p>@endforelse</div>
@endsection
