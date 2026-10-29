{{-- صحنه‌ی روشن و متحرک «صبح نارنجی» — لایه‌های موازی (parallax). بدون وابستگی به عکس --}}
<div class="scene" aria-hidden="true">
<svg viewBox="0 0 900 760" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="as-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFF8EE"/><stop offset=".38" stop-color="#FFE3C0"/><stop offset=".72" stop-color="#FFB877"/><stop offset="1" stop-color="#FF9A4D"/></linearGradient>
    <radialGradient id="as-sun"><stop offset="0" stop-color="#fff"/><stop offset=".3" stop-color="#FFF0C2" stop-opacity=".95"/><stop offset="1" stop-color="#FFC266" stop-opacity="0"/></radialGradient>
    <linearGradient id="as-m1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFD9B5"/><stop offset="1" stop-color="#FFC592"/></linearGradient>
    <linearGradient id="as-m2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFBF8A"/><stop offset="1" stop-color="#F59F5E"/></linearGradient>
    <linearGradient id="as-m3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#EE9355"/><stop offset="1" stop-color="#D9742F"/></linearGradient>
    <linearGradient id="as-gr" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9BCB6E"/><stop offset="1" stop-color="#5FA14E"/></linearGradient>
    <linearGradient id="as-gr2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#78B45A"/><stop offset="1" stop-color="#3F8A47"/></linearGradient>
    <linearGradient id="as-fog" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#FFF3E2" stop-opacity=".85"/></linearGradient>
    <linearGradient id="as-wall" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFFDF8"/><stop offset="1" stop-color="#F3E3CC"/></linearGradient>
    <linearGradient id="as-roof" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF9A4D"/><stop offset="1" stop-color="#D9560A"/></linearGradient>
    <linearGradient id="as-dome" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8CF0E2"/><stop offset=".6" stop-color="#1FBBB0"/><stop offset="1" stop-color="#0B7F88"/></linearGradient>
    <linearGradient id="as-pool" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#B8F3FF"/><stop offset="1" stop-color="#34B9E8"/></linearGradient>
    <linearGradient id="as-ball" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#FF9A3D"/><stop offset=".33" stop-color="#fff"/><stop offset=".66" stop-color="#F26B0F"/><stop offset="1" stop-color="#fff"/></linearGradient>
    <filter id="as-blur"><feGaussianBlur stdDeviation="7"/></filter>
    <filter id="as-sh" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="8" stdDeviation="8" flood-color="#8a3a00" flood-opacity=".28"/></filter>
  </defs>
  <rect width="900" height="760" fill="url(#as-sky)"/>

  <g class="layer" data-depth="5">
    <circle class="sun-glow" cx="650" cy="300" r="300" fill="url(#as-sun)"/>
    <g class="rays" style="transform-origin:650px 300px"><g stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".55"><path d="M650 170v-40M650 430v40M520 300h-40M780 300h40M558 208l-28-28M742 392l28 28M558 392l-28 28M742 208l28-28"/></g></g>
    <circle cx="650" cy="300" r="58" fill="#FFFBEA"/><circle cx="650" cy="300" r="58" fill="none" stroke="#fff" stroke-width="6" opacity=".6"/>
  </g>

  <g class="layer" data-depth="9">
    <g class="cloud c1" fill="#fff" opacity=".95"><ellipse cx="170" cy="150" rx="96" ry="24"/><ellipse cx="128" cy="134" rx="48" ry="22"/><ellipse cx="212" cy="130" rx="56" ry="24"/></g>
    <g class="cloud c2" fill="#fff" opacity=".8"><ellipse cx="640" cy="90" rx="110" ry="24"/><ellipse cx="600" cy="74" rx="52" ry="22"/><ellipse cx="690" cy="72" rx="58" ry="24"/></g>
    <g class="cloud c3" fill="#fff" opacity=".7"><ellipse cx="430" cy="240" rx="72" ry="16"/><ellipse cx="404" cy="230" rx="34" ry="14"/></g>
    <g class="balloon" filter="url(#as-sh)">
      <path d="M0 0C-34 -6 -44 -52 -26 -82 -10 -104 10 -104 26 -82 44 -52 34 -6 0 0z" transform="translate(300 250)" fill="url(#as-ball)"/>
      <path d="M-26 -82C-14 -60 -12 -26 0 0M26 -82C14 -60 12 -26 0 0" stroke="#fff" stroke-width="2" fill="none" opacity=".7" transform="translate(300 250)"/>
      <path d="M-9 8L-12 26M9 8L12 26" stroke="#8a4a12" stroke-width="1.6" transform="translate(300 250)"/>
      <rect x="289" y="276" width="22" height="16" rx="3" fill="#B26A2E"/>
    </g>
  </g>
  <g class="birds" opacity=".7" stroke="#5a3a20" stroke-width="2.4" fill="none" stroke-linecap="round"><path class="bird b1" d="M120 260q9 -10 18 0q9 -10 18 0"/><path class="bird b2" d="M170 232q7 -8 14 0q7 -8 14 0"/><path class="bird b3" d="M90 300q6 -8 12 0q6 -8 12 0"/></g>

  <g class="layer" data-depth="14"><path d="M0 470 L90 390 L170 440 L270 345 L360 430 L450 372 L560 450 L660 362 L760 440 L900 392 V560 H0Z" fill="url(#as-m1)"/><rect y="430" width="900" height="130" fill="url(#as-fog)"/></g>
  <g class="layer" data-depth="22"><path d="M0 520 L110 440 L200 505 L310 425 L410 505 L520 435 L640 510 L740 450 L900 520 V640 H0Z" fill="url(#as-m2)"/><rect y="500" width="900" height="110" fill="url(#as-fog)" opacity=".75"/></g>
  <g class="layer" data-depth="30"><path d="M0 585 L130 515 L240 575 L360 505 L470 575 L600 515 L720 580 L820 530 L900 575 V700 H0Z" fill="url(#as-m3)"/></g>

  <g class="layer" data-depth="40">
    <path d="M0 640 Q250 598 470 628 T900 616 V760 H0Z" fill="url(#as-gr)"/>
    <g fill="#2E7D5B"><path d="M60 650 l-34 0 l14 -48 l-10 0 l24 -52 l24 52 l-10 0 l14 48z"/><path d="M130 656 l-40 0 l16 -56 l-12 0 l28 -62 l28 62 l-12 0 l16 56z"/><path d="M770 650 l-38 0 l16 -52 l-12 0 l26 -58 l26 58 l-12 0 l16 52z"/><path d="M838 658 l-44 0 l18 -60 l-12 0 l30 -66 l30 66 l-12 0 l18 60z"/></g>
    <g fill="#3F9A6B"><path d="M690 660 l-30 0 l12 -42 l-9 0 l21 -46 l21 46 l-9 0 l12 42z"/><path d="M100 664 l-28 0 l11 -38 l-8 0 l19 -42 l19 42 l-8 0 l11 38z"/></g>
  </g>

  <g class="layer" data-depth="52"><g transform="translate(-95 0)">
    <ellipse cx="440" cy="672" rx="190" ry="13" fill="#6a3000" opacity=".25"/>
    {{-- ولا --}}
    <rect x="320" y="580" width="180" height="88" rx="6" fill="url(#as-wall)"/>
    <rect x="352" y="534" width="116" height="50" rx="5" fill="url(#as-wall)"/>
    <rect x="308" y="572" width="204" height="12" rx="4" fill="url(#as-roof)"/><rect x="340" y="526" width="140" height="11" rx="4" fill="url(#as-roof)"/>
    <path d="M394 520a16 16 0 0132 0z" fill="url(#as-dome)"/><rect x="408" y="500" width="4" height="12" fill="#FFB21A"/><circle cx="410" cy="498" r="3.4" fill="#FFB21A"/>
    <path d="M336 646v-34a13 13 0 0126 0v34zM458 646v-34a13 13 0 0126 0v34z" fill="#BFE8FF"/><path d="M336 624h26M458 624h26" stroke="#d9a76a" stroke-width="2"/>
    <path d="M394 668v-38a16 16 0 0132 0v38z" fill="#F26B0F"/><circle cx="418" cy="650" r="2.4" fill="#FFE59A"/>
    <path d="M372 560v-14a9 9 0 0118 0v14zM430 560v-14a9 9 0 0118 0v14z" fill="#BFE8FF"/>
    {{-- استخر --}}
    <path d="M520 666l-14 18h130l-30 -18z" fill="url(#as-pool)"/><path d="M528 674q14 -5 28 0t28 0t28 0" stroke="#fff" stroke-width="2.4" fill="none" opacity=".85"/>
  </g></g>
  <g class="layer" data-depth="64">
    <path d="M0 700 Q200 676 420 700 T900 692 V760 H0Z" fill="url(#as-gr2)"/>
    <g class="sway" stroke="#3F8A47" stroke-width="3" stroke-linecap="round">@for($i = 0; $i < 16; $i++)<g style="transform-origin:{{ 40 + $i * 56 }}px 740px;animation-delay:-{{ ($i % 5) * .6 }}s"><path d="M{{ 40 + $i * 56 }} 740v-{{ 22 + ($i % 4) * 8 }}"/><circle cx="{{ 40 + $i * 56 }}" cy="{{ 716 - ($i % 4) * 8 }}" r="6" fill="{{ $i % 3 == 0 ? '#FFB21A' : ($i % 3 == 1 ? '#fff' : '#FF8A2A') }}" stroke="none"/></g>@endfor</g>
  </g>
  <g class="flies">@for($i = 0; $i < 14; $i++)<circle class="fly" cx="{{ 60 + ($i * 61) % 800 }}" cy="{{ 480 + ($i * 71) % 220 }}" r="{{ 1.6 + ($i % 3) * .6 }}" fill="#FFF3B0" style="animation-delay:-{{ $i * .7 }}s;animation-duration:{{ 5 + $i % 5 }}s"/>@endfor</g>
</svg>

@unless($bare ?? false)
<div class="pin-float"><span class="ring"></span><span class="ring r2"></span><img src="{{ asset('img/mija-mark.svg') }}" alt=""></div>
<div class="chip chip-a glass-w"><b>۴٫۹</b><span class="stars-mini">@for($i=0;$i<5;$i++)<x-i3 name="p_star" class="sm"/>@endfor</span><small>رضایت مهمانان</small></div>
<div class="chip chip-b glass-w"><x-i3 name="p_bolt" class="lg"/><div><b>رزرو فوری</b><small>بدون انتظار تأیید</small></div></div>
<div class="chip chip-c glass-w"><x-i3 name="p_shield" class="lg"/><div><b>پرداخت امن</b><small>ضمانت بازگشت وجه</small></div></div>
<div class="scene-copy">
  <div class="rot" id="rot">
    <p class="on">جای خوب، سفر خوب</p>
    <p>ویلایی که دلت می‌خواد، همین نزدیکی</p>
    <p>مستقیم از میزبان، با قیمت شفاف</p>
  </div>
  <div class="dots" id="rot-dots"><i class="on"></i><i></i><i></i></div>
</div>
@endunless
</div>
