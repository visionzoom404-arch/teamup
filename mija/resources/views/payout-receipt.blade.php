@extends('layouts.app')
@section('title', $po->status === 'paid' ? 'رسید واریز' : 'فاکتور درخواست برداشت')
@section('nofooter', '1')
@section('bare')
@php
  $st = $po->status; $no = 'MJ-P'.str_pad($po->id, 5, '0', STR_PAD_LEFT);
  $stLabel = ['paid' => 'واریز شد', 'pending' => 'در انتظار بررسی', 'rejected' => 'رد شد'][$st] ?? $st;
  $rcData = ['no' => $no, 'status' => $stLabel, 'ok' => $st === 'paid', 'amount' => toman($po->amount), 'host' => $po->host->name, 'owner' => $po->bank_owner ?: $po->host->name, 'sheba' => $po->sheba, 'track' => $po->tracking_code ?: '—', 'req' => jdate($po->created_at, 'j F Y — H:i'), 'paid' => $po->processed_at ? jdate($po->processed_at, 'j F Y — H:i') : '—', 'title' => $st === 'paid' ? 'رسید واریز' : 'فاکتور برداشت'];
@endphp
<div class="container inv-wrap">
  @include('partials.flash')
  @if($st === 'paid' && session('ok'))<div data-confetti hidden></div>@endif
  <article class="inv rcpt" id="invoice">
    <header class="inv-h"><img src="{{ asset('img/mija-logo-h.png') }}" alt="می‌جا" height="38"><div class="inv-no"><small>{{ $st === 'paid' ? 'شماره‌ی رسید' : 'شماره‌ی فاکتور' }}</small><b class="ltr">{{ $no }}</b><small>{{ jdate($po->processed_at ?? $po->created_at, 'j F Y — H:i') }}</small></div></header>
    <div class="stamp {{ ['paid' => 'ok', 'pending' => 'wait', 'rejected' => 'bad'][$st] ?? 'wait' }}"><x-icon name="{{ ['paid' => 'check', 'pending' => 'clock', 'rejected' => 'x'][$st] ?? 'clock' }}"/>{{ ['paid' => 'واریز شد', 'pending' => 'در انتظار بررسی', 'rejected' => 'رد شد'][$st] ?? $st }}</div>
    <div class="rt-steps {{ $st }}"><div class="on"><i><x-icon name="check"/></i><span>ثبت درخواست</span></div><div class="{{ $st === 'pending' ? 'cur' : 'on' }}"><i><x-icon name="{{ $st === 'rejected' ? 'x' : 'clock' }}"/></i><span>بررسی مدیر</span></div><div class="{{ $st === 'paid' ? 'on' : '' }}"><i><x-icon name="wallet"/></i><span>واریز به حساب</span></div></div>
    <div class="rcpt-amt"><small>{{ $st === 'paid' ? 'مبلغ واریزی' : 'مبلغ درخواستی' }}</small><b data-count>{{ toman($po->amount) }}</b></div>
    <table class="inv-t"><tbody>
      <tr><td>دریافت‌کننده</td><td>{{ $po->host->name }}</td></tr>
      <tr><td>صاحب حساب</td><td>{{ $po->bank_owner ?: $po->host->name }}</td></tr>
      <tr><td>شماره شبا</td><td class="ltr">{{ $po->sheba }}</td></tr>
      @if($po->tracking_code)<tr><td>کد پیگیری بانکی</td><td class="ltr">{{ $po->tracking_code }}</td></tr>@endif
      <tr><td>تاریخ درخواست</td><td>{{ jdate($po->created_at, 'j F Y — H:i') }}</td></tr>
      <tr><td>{{ $st === 'paid' ? 'تاریخ واریز' : 'زمان تخمینی واریز' }}</td><td>{{ $po->processed_at ? jdate($po->processed_at, 'j F Y — H:i') : fa(setting('payout_hours_min')).' تا '.fa(setting('payout_hours_max')).' ساعت کاری' }}</td></tr>
      @if($po->note)<tr><td>توضیح</td><td>{{ $po->note }}</td></tr>@endif
    </tbody><tfoot><tr class="tot"><td>{{ $st === 'paid' ? 'مانده‌ی در انتظار تسویه' : 'کسر‌شده از کیف پول' }}</td><td>{{ $st === 'paid' ? toman(0) : toman($po->amount) }}</td></tr></tfoot></table>
    @if($po->receipt)<div class="rcpt-img"><small>تصویر رسید بانکی</small><a href="{{ asset('storage/'.$po->receipt) }}" target="_blank"><img src="{{ asset('storage/'.$po->receipt) }}" alt="رسید بانکی"></a></div>@endif
    <p class="inv-note">{{ $st === 'rejected' ? 'مبلغ این درخواست به کیف پول شما بازگشته است.' : 'این سند به‌صورت خودکار توسط می‌جا صادر شده است.' }}</p>
    <div class="inv-actions noprint"><button class="btn btn-sm" type="button" id="rc-dl"><x-icon name="download" class="sm"/> دانلود تصویر</button><button class="btn btn-light btn-sm" onclick="print()"><x-icon name="receipt" class="sm"/> چاپ / PDF</button><a class="btn btn-light btn-sm" href="{{ auth()->user()->isAdmin() ? route('admin.payouts') : route('host.wallet') }}">بازگشت</a></div>
  </article>
</div>
<script>
(function(){var b=document.getElementById('rc-dl');if(!b)return;
var D=@json($rcData);
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}
b.addEventListener('click',async function(){try{await Promise.all([document.fonts.load('700 30px Vazir'),document.fonts.load('400 24px Vazir')])}catch(e){}
var W=1080,H=1360,c=document.createElement('canvas');c.width=W;c.height=H;var x=c.getContext('2d');x.direction='rtl';
var g=x.createLinearGradient(0,0,W,H);g.addColorStop(0,'#FFE7B8');g.addColorStop(1,'#FFD58A');x.fillStyle=g;x.fillRect(0,0,W,H);
x.save();x.shadowColor='rgba(150,80,0,.35)';x.shadowBlur=50;x.shadowOffsetY=24;x.fillStyle='#fff';rr(x,60,60,W-120,H-120,48);x.fill();x.restore();
x.save();rr(x,60,60,W-120,H-120,48);x.clip();var hg=x.createLinearGradient(60,60,W-60,300);hg.addColorStop(0,'#F9C05A');hg.addColorStop(1,'#E07F08');x.fillStyle=hg;x.fillRect(60,60,W-120,250);x.restore();
var R=W-110,L=110;x.textAlign='right';x.fillStyle='#fff';x.font='700 54px Vazir';x.fillText(D.title,R,155);x.font='400 28px Vazir';x.fillText('می‌جا — رزرو ویلا و کلبه',R,205);
x.textAlign='left';x.direction='ltr';x.font='700 38px Vazir';x.fillText(D.no,L,155);x.direction='rtl';
x.textAlign='center';x.fillStyle=D.ok?'#1F8A5B':'#B8760A';x.font='700 40px Vazir';x.fillText(D.status,W/2,400);
x.fillStyle='#2A1A02';x.font='700 76px Vazir';x.fillText(D.amount,W/2,510);
var rows=[['دریافت‌کننده',D.host],['صاحب حساب',D.owner],['شماره شبا',D.sheba,1],['کد پیگیری بانکی',D.track,1],['تاریخ درخواست',D.req],['تاریخ واریز',D.paid]],y=600;
rows.forEach(function(r){x.strokeStyle='rgba(0,0,0,.08)';x.lineWidth=2;x.beginPath();x.moveTo(L,y+34);x.lineTo(R,y+34);x.stroke();x.textAlign='right';x.direction='rtl';x.fillStyle='#8A6A3A';x.font='400 27px Vazir';x.fillText(r[0],R,y);x.textAlign='left';x.direction=r[2]?'ltr':'rtl';x.fillStyle='#2A1A02';x.font='700 29px Vazir';x.fillText(r[1],L,y);y+=96});
x.textAlign='center';x.direction='rtl';x.fillStyle='#8A6A3A';x.font='400 24px Vazir';x.fillText('این سند به‌صورت خودکار توسط می‌جا صادر شده است — mija.click',W/2,H-110);
c.toBlob(function(bl){var a=document.createElement('a');a.href=URL.createObjectURL(bl);a.download='mija-'+D.no+'.png';document.body.appendChild(a);a.click();setTimeout(function(){URL.revokeObjectURL(a.href);a.remove()},1500)},'image/png')})})();
</script>
@endsection
