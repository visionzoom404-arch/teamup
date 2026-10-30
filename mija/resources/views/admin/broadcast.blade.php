@extends('layouts.panel')
@section('title', 'ارسال اعلان')
@section('content')
<div class="page-h"><div><h1>ارسال اعلان و پیام</h1><div class="muted">به همه یا فقط به گروه مشخصی؛ در سایت، اپلیکیشن و (اختیاری) پیامک</div></div></div>
@include('partials.help', ['title' => 'چطور کار می‌کند؟', 'text' => 'گروه مخاطب را انتخاب کنید؛ می‌توانید با «استان» یا «شماره‌های مشخص» دقیق‌ترش کنید. اعلان در بخش «اعلان‌ها»ی سایت می‌نشیند و برای کاربرانی که اپلیکیشن را نصب کرده‌اند اعلان گوشی هم می‌رود. با تیک «پیامک» همزمان پیامک هم ارسال می‌شود (هزینه از اعتبار پنل پیامک کسر می‌شود).'])
@include('partials.flash')
<form method="post" action="{{ route('admin.broadcast.send') }}" class="card bc" id="bc-form" onsubmit="return confirm('اعلان ارسال شود؟')">@csrf
  <h3>۱) مخاطبان</h3>
  <div class="aud-grid">
    @foreach($audiences as $k => [$label, $ic])
    <label class="aud"><input type="radio" name="to" value="{{ $k }}" @checked(old('to', 'all') === $k)><span class="aud-b"><x-icon name="{{ $ic }}" class="sm"/><b>{{ $label }}</b>@if($k !== 'phones')<em>{{ fa($counts[$k]) }} نفر</em>@else<em>دستی</em>@endif</span></label>
    @endforeach
  </div>
  <div class="grid2" style="margin-top:12px">
    <div class="field" id="f-prov"><label>فقط استان (اختیاری)</label><select name="province"><option value="">همه‌ی استان‌ها</option>@foreach($provinces as $pv)<option @selected(old('province') === $pv)>{{ $pv }}</option>@endforeach</select></div>
    <div class="field" id="f-phones" hidden><label>شماره‌ها (هر خط یا با ویرگول)</label><textarea class="input" dir="ltr" name="phones" rows="2" placeholder="09121234567">{{ old('phones') }}</textarea></div>
  </div>
  <div class="aud-count" id="aud-count"><x-icon name="users" class="sm"/> این پیام برای <b id="aud-n">—</b> نفر ارسال می‌شود</div>
  <h3 style="margin-top:18px">۲) متن</h3>
  <div class="grid2"><div class="field"><label>عنوان</label><input class="input" name="title" maxlength="80" value="{{ old('title') }}" required id="bc-title"></div>
  <div class="field"><label>لینک (اختیاری)</label><input class="input" dir="ltr" name="url" value="{{ old('url') }}" placeholder="{{ url('/') }}"></div></div>
  <div class="field"><label>متن</label><textarea class="input" name="body" maxlength="250" rows="3" id="bc-body">{{ old('body') }}</textarea></div>
  <div class="bc-prev" aria-hidden="true"><div class="ph-notif"><img src="{{ asset('icons/icon-192.png') }}" alt=""><div><small>می‌جا · همین حالا</small><b id="pv-t">عنوان اعلان</b><span id="pv-b">متن اعلان اینجا دیده می‌شود</span></div></div></div>
  <label class="check" style="margin-top:12px"><input type="checkbox" name="sms" value="1" @checked(old('sms'))> همزمان پیامک هم ارسال شود @if(\App\Support\Sms::enabled())<span class="badge green">پیامک فعال</span>@else<span class="badge red">پیامک غیرفعال — ابتدا تنظیمات پیامک را کامل کنید</span>@endif</label>
  <button class="btn btn-lg mt">ارسال اعلان</button>
</form>
@if($history->count())
<div class="card mt"><h3>ارسال‌های اخیر</h3>
@foreach($history as $h)@php $m = json_decode((string) $h->meta, true) ?: []; @endphp
<div class="row between" style="padding:9px 0;border-bottom:1px solid var(--line)"><div><b class="small">{{ $m['title'] ?? '—' }}</b><div class="xs muted">{{ jdate($h->created_at, 'j F Y · H:i') }} · گروه: {{ $audiences[$h->subject][0] ?? $h->subject }}</div></div><span class="badge gold">{{ fa($m['n'] ?? 0) }} نفر</span></div>
@endforeach</div>
@endif
<script>
(function(){var f=document.getElementById('bc-form'),n=document.getElementById('aud-n'),fp=document.getElementById('f-phones'),t=document.getElementById('bc-title'),b=document.getElementById('bc-body'),pt=document.getElementById('pv-t'),pb=document.getElementById('pv-b');
var fa=function(x){return String(x).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})};
function q(){var fd=new FormData(f);var p=new URLSearchParams();['to','province','phones'].forEach(function(k){p.append(k,fd.get(k)||'')});fp.hidden=fd.get('to')!=='phones';
 fetch("{{ route('admin.broadcast.count') }}?"+p,{headers:{Accept:'application/json'}}).then(function(r){return r.json()}).then(function(j){n.textContent=fa(j.n)}).catch(function(){})}
f.addEventListener('change',q);f.querySelector('[name=phones]').addEventListener('input',q);
t.addEventListener('input',function(){pt.textContent=t.value||'عنوان اعلان'});b.addEventListener('input',function(){pb.textContent=b.value||'متن اعلان اینجا دیده می‌شود'});
q();})();
</script>
@endsection
