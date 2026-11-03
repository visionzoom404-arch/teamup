@extends('layouts.app')
@section('title', 'ووچر رزرو '.$b->code)
@section('nofooter', '1')
@section('bare')
@php
  $p = $b->property; $host = $p->host;
  $methodName = $pay ? \App\Support\Gateways::label($pay->gateway === 'card' ? 'card' : $pay->gateway) : 'پرداخت‌شده';
  $v = [
    'code' => $b->code, 'title' => $p->title, 'place' => $p->province.'، '.$p->city.' · '.$p->typeLabel(), 'address' => $p->address,
    'in' => jdate($b->check_in, 'l j F Y'), 'out' => jdate($b->check_out, 'l j F Y'), 'inTime' => fa($p->check_in_time), 'outTime' => fa($p->check_out_time),
    'nights' => fa($b->nights).' شب', 'guests' => fa($b->adults).' بزرگسال'.($b->children ? ' و '.fa($b->children).' کودک' : ''),
    'guest' => $b->guest->name, 'host' => $host->name, 'hostPhone' => fa($host->phone),
    'amount' => toman($b->total), 'method' => $methodName, 'ref' => $pay && $pay->ref_id ? (string) $pay->ref_id : '—', 'paidAt' => $pay && $pay->paid_at ? jdate($pay->paid_at, 'j F Y — H:i') : '',
    'cover' => $p->cover(), 'bars' => \App\Support\Code39::widths($b->code),
  ];
@endphp
@if(session('celebrate'))<div data-confetti hidden></div>@endif
<div class="container vc-wrap">
  @include('partials.flash')
  <div class="vc-hero">
    <svg class="vc-check" viewBox="0 0 80 80" aria-hidden="true"><circle cx="40" cy="40" r="34" fill="none" stroke="currentColor" stroke-width="5"/><path d="M24 41l11 11 21-24" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <h1>رزرو شما قطعی شد</h1>
    <p>این ووچر را ذخیره کنید یا هنگام ورود به میزبان نشان دهید.</p>
  </div>

  <article class="vc" id="voucher">
    <header class="vc-top">
      <img src="{{ asset('img/mija-logo-h.png') }}" alt="می‌جا" height="34">
      <div><small>ووچر رزرو</small><b class="ltr">{{ $b->code }}</b></div>
    </header>
    <div class="vc-body">
      <div class="vc-prop"><img src="{{ $p->cover() }}" alt=""><div><b>{{ $p->title }}</b><span>{{ $v['place'] }}</span></div></div>
      <div class="vc-facts">
        <div><small>ورود</small><b>{{ $v['in'] }}</b><em>{{ $v['inTime'] }}</em></div>
        <div><small>خروج</small><b>{{ $v['out'] }}</b><em>{{ $v['outTime'] }}</em></div>
        <div><small>مدت اقامت</small><b>{{ $v['nights'] }}</b><em>{{ $v['guests'] }}</em></div>
        <div><small>به نام</small><b>{{ $v['guest'] }}</b><em>مهمان</em></div>
      </div>
      <div class="vc-host"><div><small>میزبان</small><b>{{ $host->name }}</b></div><a class="vc-tel ltr" href="tel:{{ $host->phone }}"><x-icon name="phone" class="sm"/> {{ fa($host->phone) }}</a></div>
      <div class="vc-addr"><x-icon name="pin" class="sm"/><span>{{ $p->address }}</span></div>
    </div>
    <div class="vc-tear" aria-hidden="true"><i></i><u></u><i></i></div>
    <footer class="vc-stub">
      <div class="vc-pay"><div><small>مبلغ پرداختی</small><b>{{ $v['amount'] }}</b></div><div><small>روش</small><b>{{ $v['method'] }}</b></div><div><small>کد پیگیری</small><b class="ltr">{{ $v['ref'] }}</b></div></div>
      <div class="vc-barbox">{!! \App\Support\Code39::svg($b->code) !!}<span class="ltr">{{ $b->code }}</span></div>
      @if($v['paidAt'])<small class="vc-date">پرداخت: {{ $v['paidAt'] }}</small>@endif
    </footer>
    <span class="vc-shine" aria-hidden="true"></span>
  </article>

  <div class="vc-actions noprint">
    <button type="button" class="btn btn-lg" id="vc-dl"><x-icon name="download"/> دانلود ووچر (تصویر)</button>
    <button type="button" class="btn btn-light" onclick="print()"><x-icon name="receipt" class="sm"/> چاپ / PDF</button>
    <a class="btn btn-light" href="{{ $isGuest ? route('guest.bookings.show', $b) : route('booking.invoice', $b) }}">مشاهده‌ی رزرو</a>
    <a class="btn btn-light" href="{{ route('booking.invoice', $b) }}">فاکتور</a>
  </div>
</div>
<script>
(function(){
  var V=@json($v);
  var btn=document.getElementById('vc-dl');if(!btn)return;
  function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}
  function img(src){return new Promise(function(ok){var i=new Image();i.onload=function(){ok(i)};i.onerror=function(){ok(null)};i.src=src})}
  async function draw(){
    try{await Promise.all([document.fonts.load('700 30px Vazir'),document.fonts.load('400 24px Vazir')])}catch(e){}
    var W=1080,H=1640,c=document.createElement('canvas');c.width=W;c.height=H;var x=c.getContext('2d');x.direction='rtl';
    var g=x.createLinearGradient(0,0,W,H);g.addColorStop(0,'#FFE7B8');g.addColorStop(.5,'#FFF6E4');g.addColorStop(1,'#FFD58A');x.fillStyle=g;x.fillRect(0,0,W,H);
    x.save();x.shadowColor='rgba(150,80,0,.35)';x.shadowBlur=50;x.shadowOffsetY=24;x.fillStyle='#fff';rr(x,60,60,W-120,H-120,48);x.fill();x.restore();
    x.save();rr(x,60,60,W-120,H-120,48);x.clip();
    var hg=x.createLinearGradient(60,60,W-60,330);hg.addColorStop(0,'#F9C05A');hg.addColorStop(1,'#E07F08');x.fillStyle=hg;x.fillRect(60,60,W-120,260);
    x.fillStyle='rgba(255,255,255,.14)';x.beginPath();x.arc(180,90,150,0,7);x.fill();x.beginPath();x.arc(W-140,330,120,0,7);x.fill();
    x.restore();
    var R=W-110,L=110;x.textAlign='right';
    x.fillStyle='#fff';x.font='700 54px Vazir';x.fillText('ووچر رزرو',R,155);
    x.font='400 28px Vazir';x.fillText('می‌جا — رزرو ویلا و کلبه',R,205);
    x.textAlign='left';x.direction='ltr';x.font='700 40px Vazir';x.fillText(V.code,L,155);x.direction='rtl';x.font='400 24px Vazir';x.fillText('کد رزرو',L,200);
    // عکس اقامتگاه
    var im=await img(V.cover);
    x.save();rr(x,L,370,W-220,300,32);x.clip();x.fillStyle='#FFE9C2';x.fillRect(L,370,W-220,300);
    if(im){var s=Math.max((W-220)/im.width,300/im.height),dw=im.width*s,dh=im.height*s;x.drawImage(im,L+((W-220)-dw)/2,370+(300-dh)/2,dw,dh)}
    var ov=x.createLinearGradient(0,470,0,670);ov.addColorStop(0,'rgba(0,0,0,0)');ov.addColorStop(1,'rgba(40,20,0,.7)');x.fillStyle=ov;x.fillRect(L,470,W-220,200);x.restore();
    x.textAlign='right';x.fillStyle='#fff';x.font='700 40px Vazir';
    function fit(t,max,font){x.font=font;while(x.measureText(t).width>max&&t.length>4){t=t.slice(0,-2)}return t}
    x.fillText(fit(V.title,W-280,'700 40px Vazir'),R-20,610);x.font='400 26px Vazir';x.fillText(fit(V.place,W-280,'400 26px Vazir'),R-20,648);
    // واقعیت‌ها
    var fy=720,cw=(W-220-24)/2,items=[['ورود',V.in,V.inTime],['خروج',V.out,V.outTime],['مدت اقامت',V.nights,V.guests],['به نام',V.guest,'مهمان']];
    items.forEach(function(it,i){var cx=R-(i%2)*(cw+24),cy=fy+Math.floor(i/2)*150;
      x.fillStyle='#FFF6E4';rr(x,cx-cw,cy,cw,128,24);x.fill();x.strokeStyle='rgba(224,138,18,.25)';x.lineWidth=2;x.stroke();
      x.textAlign='right';x.fillStyle='#8A6A3A';x.font='400 24px Vazir';x.fillText(it[0],cx-22,cy+38);
      x.fillStyle='#2A1A02';x.font=fit(it[1],cw-44,'700 29px Vazir');x.fillText(fit(it[1],cw-44,'700 29px Vazir'),cx-22,cy+78);
      x.fillStyle='#8A6A3A';x.font='400 22px Vazir';x.fillText(fit(it[2]||'',cw-44,'400 22px Vazir'),cx-22,cy+112)});
    // میزبان و آدرس
    var hy=1030;x.fillStyle='#FFF1D6';rr(x,L,hy,W-220,120,24);x.fill();
    x.textAlign='right';x.fillStyle='#8A6A3A';x.font='400 24px Vazir';x.fillText('میزبان',R-22,hy+38);x.fillStyle='#2A1A02';x.font='700 32px Vazir';x.fillText(fit(V.host,400,'700 32px Vazir'),R-22,hy+84);
    x.textAlign='left';x.direction='ltr';x.fillStyle='#B4590A';x.font='700 34px Vazir';x.fillText(V.hostPhone,L+24,hy+75);x.direction='rtl';
    x.textAlign='right';x.fillStyle='#4A3A22';x.font='400 25px Vazir';
    var words=(V.address||'').split(' '),line='',ly=hy+175,lines=0;
    for(var k=0;k<words.length&&lines<2;k++){var t=line?line+' '+words[k]:words[k];if(x.measureText(t).width>W-260&&line){x.fillText(line,R,ly);ly+=38;lines++;line=words[k]}else line=t}
    if(lines<2&&line)x.fillText(line,R,ly);
    // خط پاره
    var ty=1230;x.setLineDash([14,12]);x.strokeStyle='rgba(180,100,10,.5)';x.lineWidth=3;x.beginPath();x.moveTo(110,ty);x.lineTo(W-110,ty);x.stroke();x.setLineDash([]);
    x.fillStyle='#FFE7B8';x.beginPath();x.arc(60,ty,34,0,7);x.fill();x.beginPath();x.arc(W-60,ty,34,0,7);x.fill();
    // پرداخت
    var py=1290,pw=(W-220-40)/3;[['مبلغ پرداختی',V.amount],['روش',V.method],['کد پیگیری',V.ref]].forEach(function(it,i){var px=R-i*(pw+20);
      x.textAlign='right';x.fillStyle='#8A6A3A';x.font='400 22px Vazir';x.fillText(it[0],px,py);x.fillStyle='#2A1A02';x.font=fit(it[1],pw,'700 27px Vazir');if(i===2)x.direction='ltr',x.textAlign='right';x.fillText(fit(it[1],pw,'700 27px Vazir'),px,py+42);x.direction='rtl'});
    // بارکد
    var bw=V.bars.reduce(function(a,b){return a+b},0),unit=Math.min(4,(W-320)/bw),bx=(W-bw*unit)/2,by=1370,pos=bx;
    x.fillStyle='#1b1204';V.bars.forEach(function(u,i){if(i%2===0)x.fillRect(pos,by,u*unit,110);pos+=u*unit});
    x.textAlign='center';x.direction='ltr';x.fillStyle='#4A3A22';x.font='700 28px Vazir';x.fillText(V.code,W/2,by+150);
    x.font='400 22px Vazir';x.fillStyle='#8A6A3A';x.fillText(V.paidAt?('پرداخت: '+V.paidAt):'mija.click',W/2,by+190);
    return c;
  }
  btn.addEventListener('click',async function(){
    var t=btn.innerHTML;btn.disabled=true;btn.textContent='در حال ساخت تصویر…';
    try{var c=await draw();c.toBlob(function(b){var a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='mija-voucher-'+V.code+'.png';document.body.appendChild(a);a.click();setTimeout(function(){URL.revokeObjectURL(a.href);a.remove()},1500);btn.disabled=false;btn.innerHTML=t},'image/png')}
    catch(e){btn.disabled=false;btn.innerHTML=t;alert('ساخت تصویر ناموفق بود؛ از «چاپ / PDF» استفاده کنید.')}
  });
})();
</script>
@endsection
