{{-- تعریف گرادیان‌ها و فیلتر سایه‌ی آیکن‌های سه‌بعدی (یک‌بار در هر صفحه) --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <defs>
    <filter id="i3s" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="1.6" stdDeviation="1.1" flood-color="#2a1030" flood-opacity=".28"/></filter>
    @foreach([
      'gR' => ['#FFA262', '#EA580C', '#B8420A'], 'gS' => ['#FF8383', '#E5202E', '#A50F22'], 'gO' => ['#FFC27A', '#FF7A1A', '#D9520A'], 'gG' => ['#FFF0B0', '#FFC83D', '#E39A00'],
      'gB' => ['#C6EDFF', '#5BBDF0', '#2A86C4'], 'gN' => ['#9DF0C0', '#2FAE6B', '#177746'], 'gK' => ['#6A6E7C', '#2A2C33', '#101116'],
      'gW' => ['#FFFFFF', '#F1F3F9', '#C9D0DF'], 'gWood' => ['#E0A46C', '#A8693F', '#6A3A1A'], 'gT' => ['#8CF0E2', '#1FBBB0', '#0B7F88'], 'gClay' => ['#F7C998', '#E0904F', '#B2652B'], 'gL2' => ['#6F90E6', '#2B4AA6', '#16296A'], 'gTea' => ['#FFC074', '#E8741E', '#A63E0F'], 'gSkin' => ['#FFE0C2', '#F2B48A', '#D98A5E'], 'gWater' => ['#C4F4FF', '#4CC9F0', '#1D8CCF'], 'gL' => ['#B6F5A8', '#3FBF5A', '#1B8638'],
    ] as $id => [$a, $b, $c])
    <linearGradient id="{{ $id }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{{ $a }}"/><stop offset=".55" stop-color="{{ $b }}"/><stop offset="1" stop-color="{{ $c }}"/></linearGradient>
    @endforeach
    <filter id="i3b" x="-30%" y="-100%" width="160%" height="300%"><feGaussianBlur stdDeviation="2.6"/></filter>
    @foreach([
      'pO' => ['#FFD9A0', '#FFA23D', '#F26B0F', '#A9430A'], 'pY' => ['#FFF7C8', '#FFD84F', '#FFB21A', '#B87400'], 'pC' => ['#FFFFFF', '#FFF4E4', '#F2DDBF', '#CDB085'],
      'pR' => ['#FFC0B5', '#FF6552', '#E5202E', '#85101A'], 'pT' => ['#C2FFF6', '#45DACB', '#12A89E', '#06626A'], 'pB' => ['#DDF4FF', '#82CFF6', '#3A9AD6', '#1B5A92'],
      'pK' => ['#9A9EAC', '#555967', '#272A32', '#0B0C10'], 'pG' => ['#DAFFC8', '#84DC5C', '#3FA83A', '#1B6528'], 'pW' => ['#FFFFFF', '#F6F8FC', '#DCE2EE', '#AAB4C8'],
    ] as $id => [$a, $b, $c, $d])
    <radialGradient id="{{ $id }}" cx=".34" cy=".28" r=".85"><stop offset="0" stop-color="{{ $a }}"/><stop offset=".32" stop-color="{{ $b }}"/><stop offset=".68" stop-color="{{ $c }}"/><stop offset="1" stop-color="{{ $d }}"/></radialGradient>
    @endforeach
  </defs>
</svg>
