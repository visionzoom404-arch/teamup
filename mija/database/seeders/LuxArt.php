<?php

namespace Database\Seeders;

/** تصویر جایگزین مینیمال و تونال برای اقامتگاه‌ها (تا زمان بارگذاری عکس واقعی) */
class LuxArt
{
    private const PAL = [
        0 => ['#16110C', '#4A3322', '#C98D52', '#F1C88D'], // غروب مسی
        1 => ['#1B2226', '#3F5560', '#9DB4BD', '#EAF0F1'], // مه‌آلود
        2 => ['#101712', '#2E4435', '#6F8F76', '#D6E2D3'], // جنگل
        3 => ['#2A2119', '#7B6048', '#CDB08A', '#F5E9D6'], // شن
        4 => ['#0B0D14', '#202843', '#5C6EA3', '#C9D2F0'], // شب
    ];

    public static function svg(string $kind, int $seed, int $variant): string
    {
        mt_srand($seed * 131 + 7);
        [$a, $b, $c, $d] = self::PAL[$variant % 5];
        $u = "l{$seed}{$variant}";
        $o = "<rect width='900' height='600' fill='url(#s$u)'/>";
        $o .= "<circle cx='".mt_rand(520, 780)."' cy='".mt_rand(190, 250)."' r='".mt_rand(150, 200)."' fill='url(#g$u)'/>";
        // لایه‌های دور
        foreach ([[360, '.55'], [410, '.8'], [455, '1']] as $i => [$base, $op]) {
            $d1 = "M0 $base";
            for ($x = 0; $x <= 900; $x += 90) {
                $d1 .= " L$x ".($base - mt_rand(8, 46 - $i * 10));
            }
            $o .= "<path d='$d1 L900 600 L0 600Z' fill='$b' opacity='".(.35 + $i * .25)."'/>";
        }
        $o .= "<rect y='470' width='900' height='130' fill='$a' opacity='.92'/>";
        $bx = mt_rand(230, 360);
        if ($kind === 'cabin' || $kind === 'forest') {
            $o .= "<path d='M$bx 480 L".($bx + 110)." 360 L".($bx + 220)." 480Z' fill='#0B0805'/><rect x='".($bx + 40)."' y='430' width='140' height='50' fill='#0B0805'/><path d='M".($bx + 80)." 480V440a30 24 0 0160 0v40z' fill='$d' opacity='.9'/>";
        } else {
            $o .= "<rect x='$bx' y='410' width='330' height='10' fill='#0B0805'/><rect x='".($bx + 14)."' y='420' width='302' height='60' fill='#100B07'/><rect x='".($bx + 30)."' y='432' width='96' height='42' fill='$d' opacity='.9'/><rect x='".($bx + 140)."' y='432' width='96' height='42' fill='$d' opacity='.6'/><rect x='".($bx + 70)."' y='372' width='190' height='9' fill='#0B0805'/><rect x='".($bx + 82)."' y='381' width='166' height='29' fill='#100B07'/><rect x='".($bx + 96)."' y='388' width='64' height='18' fill='$d' opacity='.55'/>";
            if ($kind !== 'mountain') {
                $o .= "<rect x='".($bx - 40)."' y='488' width='410' height='8' fill='$c' opacity='.7'/><rect x='".($bx - 40)."' y='496' width='410' height='30' fill='$c' opacity='.14'/>";
            }
        }
        for ($i = 0; $i < 5; $i++) {
            $x = mt_rand(40, 860);
            $h = mt_rand(70, 140);
            $o .= "<ellipse cx='$x' cy='".(486 - $h / 2)."' rx='".mt_rand(7, 11)."' ry='".($h / 2)."' fill='#0B0805' opacity='.9'/>";
        }
        $o .= "<rect width='900' height='600' fill='url(#v$u)'/>";

        return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 900 600' preserveAspectRatio='xMidYMid slice'><defs>"
            ."<linearGradient id='s$u' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='$a'/><stop offset='.6' stop-color='$b'/><stop offset='1' stop-color='$c'/></linearGradient>"
            ."<radialGradient id='g$u'><stop offset='0' stop-color='$d' stop-opacity='.8'/><stop offset='1' stop-color='$d' stop-opacity='0'/></radialGradient>"
            ."<radialGradient id='v$u' cx='.5' cy='.5' r='.75'><stop offset='.6' stop-color='#000' stop-opacity='0'/><stop offset='1' stop-color='#000' stop-opacity='.4'/></radialGradient>"
            .'</defs>'.$o.'</svg>';
    }
}
