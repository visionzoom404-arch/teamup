<?php

namespace Database\Seeders;

/** تصاویر مصور (SVG) برای اقامتگاه‌های نمونه — تا زمان بارگذاری عکس واقعی توسط میزبان */
class SceneArt
{
    public static function svg(string $kind, int $seed, int $variant): string
    {
        mt_srand($seed + 11);
        $skies = [
            'forest' => ['#bfe6f5', '#fff1cf'], 'cabin' => ['#ffd9a8', '#ffb199'], 'sea' => ['#8fd3f4', '#fff3d1'],
            'mountain' => ['#c4e3ff', '#ffe9d1'], 'villa' => ['#ffe0a3', '#ff9a7a'],
        ];
        [$s1, $s2] = $skies[$kind] ?? $skies['villa'];
        if ($variant % 2 === 1) {
            [$s1, $s2] = [$s2, $s1];
        }
        $g = ['forest' => '#2f7d52', 'cabin' => '#3d6b4a', 'sea' => '#e8c98a', 'mountain' => '#5b8a6a', 'villa' => '#5aa469'][$kind] ?? '#4c8a5c';
        $o = '';
        // کوه‌ها
        $o .= '<path d="M0 210 L90 120 L170 190 L260 100 L350 180 L430 130 L520 200 L600 160 L600 260 L0 260Z" fill="'.($kind === 'mountain' ? '#8aa7c4' : '#9bb8a5').'" opacity=".7"/>';
        if ($kind === 'sea') {
            $o .= '<rect y="190" width="600" height="70" fill="#4fb2e6"/><path d="M0 205 Q40 195 80 205 T160 205 T240 205 T320 205 T400 205 T480 205 T560 205 T640 205" stroke="#fff" stroke-width="3" fill="none" opacity=".7"/>';
        }
        $o .= '<path d="M0 250 Q150 215 300 240 T600 235 V400 H0Z" fill="'.$g.'"/>';
        // درختان
        for ($i = 0; $i < 9; $i++) {
            $x = mt_rand(10, 590);
            $h = mt_rand(50, 110);
            $y = mt_rand(238, 290);
            $c = ['#1f6b43', '#2b8a55', '#17573a'][mt_rand(0, 2)];
            if ($kind !== 'sea' || $i < 3) {
                $o .= "<path d=\"M$x $y l-".($h / 3)." 0 l".($h / 6)." -".($h / 2)." l-".($h / 8)." 0 l".($h / 4)." -".($h / 2)." l".($h / 4)." ".($h / 2)." l-".($h / 8)." 0 l".($h / 6)." ".($h / 2)." z\" fill=\"$c\"/>";
            }
        }
        // ساختمان
        $bx = 190 + ($variant * 17) % 60;
        if ($kind === 'cabin' || $kind === 'forest' && $variant % 2 === 0) {
            $o .= "<path d=\"M$bx 330 V260 L".($bx + 90)." 190 L".($bx + 180)." 260 V330Z\" fill=\"#8a5a3a\"/><path d=\"M".($bx - 14)." 268 L".($bx + 90)." 178 L".($bx + 194)." 268\" stroke=\"#4a2f1d\" stroke-width=\"14\" fill=\"none\" stroke-linejoin=\"round\"/><rect x=\"".($bx + 68)."\" y=\"270\" width=\"44\" height=\"60\" rx=\"4\" fill=\"#4a2f1d\"/><rect x=\"".($bx + 24)."\" y=\"262\" width=\"34\" height=\"34\" rx=\"4\" fill=\"#ffd76a\"/><rect x=\"".($bx + 122)."\" y=\"262\" width=\"34\" height=\"34\" rx=\"4\" fill=\"#ffd76a\"/>";
        } else {
            $o .= "<rect x=\"$bx\" y=\"250\" width=\"200\" height=\"90\" rx=\"6\" fill=\"#fffaf0\"/><path d=\"M".($bx - 12)." 252 L".($bx + 100)." 196 L".($bx + 212)." 252Z\" fill=\"#c0392b\"/><rect x=\"".($bx + 20)."\" y=\"272\" width=\"38\" height=\"36\" rx=\"4\" fill=\"#7cc8f0\"/><rect x=\"".($bx + 142)."\" y=\"272\" width=\"38\" height=\"36\" rx=\"4\" fill=\"#7cc8f0\"/><rect x=\"".($bx + 80)."\" y=\"280\" width=\"40\" height=\"60\" rx=\"4\" fill=\"#8a5a3a\"/>";
            if (in_array($kind, ['villa', 'sea']) && $variant !== 1) {
                $o .= '<ellipse cx="'.($bx - 60).'" cy="330" rx="90" ry="22" fill="#6fd0f5"/><ellipse cx="'.($bx - 60).'" cy="326" rx="78" ry="15" fill="#a8e6fb" opacity=".8"/>';
            }
        }
        // خورشید
        $o .= '<circle cx="'.mt_rand(60, 520).'" cy="'.mt_rand(50, 90).'" r="'.mt_rand(26, 38).'" fill="#ffd76a" opacity=".95"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="s" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="'.$s1.'"/><stop offset="1" stop-color="'.$s2.'"/></linearGradient></defs><rect width="600" height="400" fill="url(#s)"/>'.$o.'</svg>';
    }
}
