{{-- صحنه‌ی متحرک غروب و کلبه — لایه‌های موازی (parallax) --}}
<div class="scene" aria-hidden="true">
<svg viewBox="0 0 900 760" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="as-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6d0b1f"/><stop offset=".32" stop-color="#d6162b"/><stop offset=".62" stop-color="#ff7a1a"/><stop offset="1" stop-color="#ffd76a"/></linearGradient>
    <radialGradient id="as-sun"><stop offset="0" stop-color="#fff6c8"/><stop offset=".35" stop-color="#ffd76a" stop-opacity=".9"/><stop offset="1" stop-color="#ff9a1f" stop-opacity="0"/></radialGradient>
    <linearGradient id="as-m1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#a3203f"/><stop offset="1" stop-color="#d4503a"/></linearGradient>
    <linearGradient id="as-m2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6a1634"/><stop offset="1" stop-color="#9c2a38"/></linearGradient>
    <linearGradient id="as-m3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3a0f26"/><stop offset="1" stop-color="#5b1a30"/></linearGradient>
    <linearGradient id="as-fog" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd76a" stop-opacity="0"/><stop offset="1" stop-color="#ffb347" stop-opacity=".55"/></linearGradient>
    <linearGradient id="as-lake" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ff9a4a"/><stop offset="1" stop-color="#7a1530"/></linearGradient>
    <filter id="as-blur"><feGaussianBlur stdDeviation="6"/></filter>
  </defs>
  <rect width="900" height="760" fill="url(#as-sky)"/>
  <g class="layer" data-depth="6"><circle class="sun-glow" cx="620" cy="330" r="260" fill="url(#as-sun)"/><circle cx="620" cy="330" r="52" fill="#fff3c2"/></g>
  <g class="layer" data-depth="10">
    <g class="cloud c1" fill="#ffe0a8" opacity=".75"><ellipse cx="160" cy="150" rx="90" ry="22"/><ellipse cx="120" cy="136" rx="46" ry="20"/><ellipse cx="200" cy="132" rx="52" ry="22"/></g>
    <g class="cloud c2" fill="#ffd0a0" opacity=".55"><ellipse cx="640" cy="90" rx="110" ry="24"/><ellipse cx="600" cy="74" rx="52" ry="22"/><ellipse cx="690" cy="72" rx="58" ry="24"/></g>
    <g class="cloud c3" fill="#ffe6bd" opacity=".5"><ellipse cx="420" cy="230" rx="70" ry="16"/><ellipse cx="394" cy="220" rx="34" ry="14"/></g>
  </g>
  <g class="layer" data-depth="14"><path d="M0 470 L90 380 L170 440 L270 330 L360 430 L450 360 L560 450 L660 350 L760 440 L900 380 V560 H0Z" fill="url(#as-m1)"/><rect y="430" width="900" height="130" fill="url(#as-fog)"/></g>
  <g class="layer" data-depth="22"><path d="M0 520 L110 430 L200 500 L310 410 L410 500 L520 420 L640 510 L740 440 L900 520 V640 H0Z" fill="url(#as-m2)"/><rect y="500" width="900" height="110" fill="url(#as-fog)" opacity=".7"/></g>
  <g class="layer" data-depth="30"><path d="M0 580 L130 500 L240 570 L360 490 L470 570 L600 500 L720 575 L820 520 L900 570 V700 H0Z" fill="url(#as-m3)"/></g>
  <g class="layer" data-depth="40">
    <path d="M0 640 Q250 600 470 630 T900 620 V760 H0Z" fill="#2a0c1f"/>
    <g fill="#1c0714">
      <path d="M60 640 l-34 0 l14 -48 l-10 0 l24 -52 l24 52 l-10 0 l14 48z"/>
      <path d="M130 650 l-40 0 l16 -56 l-12 0 l28 -62 l28 62 l-12 0 l16 56z"/>
      <path d="M760 640 l-38 0 l16 -52 l-12 0 l26 -58 l26 58 l-12 0 l16 52z"/>
      <path d="M830 650 l-44 0 l18 -60 l-12 0 l30 -66 l30 66 l-12 0 l18 60z"/>
      <path d="M690 655 l-30 0 l12 -42 l-9 0 l21 -46 l21 46 l-9 0 l12 42z"/>
    </g>
  </g>
  <g class="layer" data-depth="52">
    <ellipse cx="450" cy="668" rx="170" ry="12" fill="#000" opacity=".35"/>
    <rect x="520" y="540" width="22" height="64" fill="#2b1420"/>
    <circle class="smoke s1" cx="531" cy="528" r="10" fill="#ffd9b0" opacity=".5"/><circle class="smoke s2" cx="531" cy="528" r="10" fill="#ffd9b0" opacity=".5"/><circle class="smoke s3" cx="531" cy="528" r="10" fill="#ffd9b0" opacity=".5"/>
    <rect x="360" y="590" width="190" height="78" fill="#3b1a26"/>
    <path d="M338 598 L455 512 L572 598 L552 604 L455 534 L358 604Z" fill="#150610"/>
    <path d="M455 512 L572 598 L455 534Z" fill="#240a18"/>
    <rect class="win w1" x="384" y="610" width="40" height="34" rx="3" fill="#ffd76a"/><rect class="win w2" x="486" y="610" width="40" height="34" rx="3" fill="#ffc24a"/>
    <path d="M404 610v34M384 627h40M506 610v34M486 627h40" stroke="#3b1a26" stroke-width="2"/>
    <rect x="437" y="620" width="36" height="48" rx="4" fill="#1c0714"/><circle cx="466" cy="646" r="2.5" fill="#ffd76a"/>
    <rect class="winglow" x="372" y="604" width="64" height="46" rx="10" fill="#ffd76a" opacity=".22" filter="url(#as-blur)"/>
  </g>
  <g class="layer" data-depth="64"><g fill="#0f040b"><path d="M-10 700 l-50 0 l20 -70 l-14 0 l40 -90 l40 90 l-14 0 l20 70z" transform="translate(70 40) scale(1.4)"/><path d="M-10 700 l-50 0 l20 -70 l-14 0 l40 -90 l40 90 l-14 0 l20 70z" transform="translate(790 60) scale(1.5)"/></g></g>
  <g class="flies">@for($i = 0; $i < 18; $i++)<circle class="fly" cx="{{ 60 + ($i * 47) % 800 }}" cy="{{ 430 + ($i * 71) % 260 }}" r="{{ 1.6 + ($i % 3) * .6 }}" fill="#ffe08a" style="animation-delay:-{{ $i * .7 }}s;animation-duration:{{ 5 + $i % 5 }}s"/>@endfor</g>
</svg>

@unless($bare ?? false)
<div class="pin-float"><span class="ring"></span><span class="ring r2"></span><img src="{{ asset('img/mija-mark.svg') }}" alt=""></div>

<div class="chip chip-a glass-dark"><b>۴٫۹</b><span class="stars-mini">@for($i=0;$i<5;$i++)<x-i3 name="star" class="sm"/>@endfor</span><small>رضایت مهمانان</small></div>
<div class="chip chip-b glass-dark"><x-i3 name="bolt" class="lg"/><div><b>رزرو فوری</b><small>بدون انتظار تأیید</small></div></div>
<div class="chip chip-c glass-dark"><x-i3 name="shield" class="lg"/><div><b>پرداخت امن</b><small>ضمانت بازگشت وجه</small></div></div>

<div class="scene-copy">
  <div class="rot" id="rot">
    <p class="on">ویلای رویایی‌ات، فقط چند لمس آن‌طرف‌تر</p>
    <p>کلبه‌ای دنج در دل جنگل برای آرامش تو</p>
    <p>مستقیم از میزبان، با قیمت شفاف</p>
  </div>
  <div class="dots" id="rot-dots"><i class="on"></i><i></i><i></i></div>
</div>
@endunless
</div>
