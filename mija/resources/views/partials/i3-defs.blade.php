{{-- تعریف گرادیان‌ها و فیلتر سایه‌ی آیکن‌های سه‌بعدی (یک‌بار در هر صفحه) --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <defs>
    <filter id="i3s" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="1.6" stdDeviation="1.1" flood-color="#2a1030" flood-opacity=".28"/></filter>
    @foreach([
      'gR' => ['#FFD58A', '#F39C1E', '#C9700A'], 'gS' => ['#FF8D86', '#D92D20', '#8F1A12'], 'gO' => ['#FFE0A0', '#F7A92F', '#D9770A'], 'gG' => ['#FFF3C8', '#FFCB55', '#E39A12'],
      'gB' => ['#FFFFFF', '#FFF1D6', '#F6D79C'], 'gN' => ['#E7B96A', '#C98A25', '#8F5A10'], 'gK' => ['#5A5A62', '#2A2A2F', '#121214'],
      'gW' => ['#FFFFFF', '#FFF8EA', '#EDDCB8'], 'gWood' => ['#E7B96A', '#B97A1C', '#7A4A08'], 'gWater' => ['#FFF3D6', '#FFD98F', '#F0A83A'], 'gL' => ['#F7CF72', '#D9921E', '#9A5F0A'],
      'gT' => ['#FFE7B0', '#F9B440', '#D9800C'], 'gClay' => ['#F4CE8A', '#D99A3C', '#A9690F'], 'gL2' => ['#6A6A74', '#34343B', '#17171A'], 'gTea' => ['#FFD58A', '#F08C14', '#B8620A'], 'gSkin' => ['#FFE9CC', '#F4C089', '#DB9558'],
    ] as $id => [$a, $b, $c])
    <linearGradient id="{{ $id }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{{ $a }}"/><stop offset=".55" stop-color="{{ $b }}"/><stop offset="1" stop-color="{{ $c }}"/></linearGradient>
    @endforeach
    <filter id="i3b" x="-30%" y="-100%" width="160%" height="300%"><feGaussianBlur stdDeviation="2.6"/></filter>
    @foreach([
      'pO' => ['#FFE2A8', '#FFB347', '#F0901A', '#A9600A'], 'pY' => ['#FFF6D0', '#FFD763', '#F5AE2A', '#B87608'], 'pC' => ['#FFFFFF', '#FFF9EC', '#F4E3BF', '#CDAF72'],
      'pR' => ['#FFDCA0', '#F9A83A', '#E6850E', '#8A4A06'], 'pT' => ['#FFEBC0', '#FFC25C', '#EE9418', '#9A5A08'], 'pB' => ['#FFFFFF', '#FFF2D8', '#F7D9A0', '#C79A4C'],
      'pK' => ['#8B8B94', '#4C4C54', '#25252A', '#0C0C0E'], 'pG' => ['#EFC77A', '#D39A35', '#A8701A', '#6A430A'], 'pW' => ['#FFFFFF', '#FBF8F2', '#E6DFD0', '#B5AB96'],
    ] as $id => [$a, $b, $c, $d])
    <radialGradient id="{{ $id }}" cx=".34" cy=".28" r=".85"><stop offset="0" stop-color="{{ $a }}"/><stop offset=".32" stop-color="{{ $b }}"/><stop offset=".68" stop-color="{{ $c }}"/><stop offset="1" stop-color="{{ $d }}"/></radialGradient>
    @endforeach
  </defs>
</svg>
