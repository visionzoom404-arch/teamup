{{-- هنر مینیمال هیرو: غروب کویری و ولای مدرن (خطوط افقی، نور گرم) --}}
<div class="hero-art" aria-hidden="true">
<svg viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="ha-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0F0C09"/><stop offset=".42" stop-color="#2A1D13"/><stop offset=".74" stop-color="#7C4B27"/><stop offset="1" stop-color="#D99A5B"/></linearGradient>
    <radialGradient id="ha-sun" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#FFE2B3" stop-opacity=".95"/><stop offset=".35" stop-color="#F1B36E" stop-opacity=".45"/><stop offset="1" stop-color="#E2954C" stop-opacity="0"/></radialGradient>
    <linearGradient id="ha-d1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5A3820"/><stop offset="1" stop-color="#3B2414"/></linearGradient>
    <linearGradient id="ha-d2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3A2515"/><stop offset="1" stop-color="#22160C"/></linearGradient>
    <linearGradient id="ha-d3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1D130B"/><stop offset="1" stop-color="#0E0905"/></linearGradient>
    <linearGradient id="ha-glass" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFD9A0"/><stop offset="1" stop-color="#E5A55E"/></linearGradient>
    <linearGradient id="ha-pool" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#C9854A" stop-opacity=".9"/><stop offset=".5" stop-color="#F3C48A"/><stop offset="1" stop-color="#C9854A" stop-opacity=".9"/></linearGradient>
  </defs>
  <rect width="1600" height="900" fill="url(#ha-sky)"/>
  <g class="layer" data-depth="6">
    <circle cx="1130" cy="600" r="420" fill="url(#ha-sun)"/>
    <circle cx="1130" cy="600" r="46" fill="#FFEBC9" opacity=".95"/>
    @for($i = 0; $i < 60; $i++)<circle cx="{{ ($i * 263) % 1600 }}" cy="{{ ($i * 97) % 340 }}" r="{{ .6 + ($i % 3) * .4 }}" fill="#FFF3DC" opacity="{{ .25 + ($i % 5) / 10 }}"/>@endfor
  </g>
  <g class="layer" data-depth="12"><path d="M0 640 C220 600 360 640 560 612 S980 590 1180 626 1480 600 1600 620 V900 H0Z" fill="url(#ha-d1)"/></g>
  <g class="layer" data-depth="20"><path d="M0 700 C260 664 420 704 640 684 S1060 650 1300 690 1520 676 1600 690 V900 H0Z" fill="url(#ha-d2)"/></g>
  <g class="layer" data-depth="32">
    <path d="M0 770 H1600 V900 H0Z" fill="url(#ha-d3)"/>
    {{-- ولا: حجم‌های افقی و شیشه‌ی گرم --}}
    <g transform="translate(250 0)">
      <rect x="120" y="640" width="640" height="14" fill="#0B0704"/>
      <rect x="150" y="654" width="580" height="116" fill="#17100A"/>
      <rect x="180" y="672" width="170" height="86" fill="url(#ha-glass)" opacity=".92"/><rect x="368" y="672" width="170" height="86" fill="url(#ha-glass)" opacity=".7"/><rect x="556" y="672" width="140" height="86" fill="url(#ha-glass)" opacity=".85"/>
      <path d="M180 672v86M265 672v86M368 672v86M453 672v86M556 672v86M626 672v86" stroke="#17100A" stroke-width="3"/>
      <rect x="260" y="590" width="360" height="12" fill="#0B0704"/><rect x="280" y="602" width="320" height="52" fill="#120C07"/>
      <rect x="300" y="614" width="110" height="34" fill="url(#ha-glass)" opacity=".55"/><rect x="430" y="614" width="140" height="34" fill="url(#ha-glass)" opacity=".35"/>
      <rect x="60" y="770" width="760" height="10" fill="url(#ha-pool)" opacity=".75"/>
      <rect x="60" y="780" width="760" height="40" fill="url(#ha-pool)" opacity=".18"/>
    </g>
    <g fill="#0B0704"><ellipse cx="170" cy="700" rx="14" ry="78"/><ellipse cx="214" cy="716" rx="11" ry="62"/><ellipse cx="1330" cy="706" rx="13" ry="72"/><ellipse cx="1372" cy="722" rx="10" ry="56"/></g>
  </g>
</svg>
</div>
