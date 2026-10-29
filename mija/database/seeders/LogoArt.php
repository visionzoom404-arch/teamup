<?php

namespace Database\Seeders;

/** تصویر جایگزین: لوگوی سه‌بعدی می‌جا روی زمینه‌ی کرم/طلایی (تا زمان بارگذاری عکس واقعی) */
class LogoArt
{
    public static function svg(string|int $kind = 0, int $seed = 0, int $variant = 0): string
    {
        if (is_int($kind)) { // فراخوانی کوتاه: svg(seed, variant)
            [$variant, $seed] = [$seed, $kind];
        }
        $tones = [['#FFFFFF', '#FFF1D6', '#F8DDAA'], ['#FFFDF8', '#FFEFD0', '#F6D391'], ['#FFFFFF', '#FFF4DE', '#F9E1B4'], ['#FFFCF5', '#FFEBC6', '#F4CB82'], ['#FFFFFF', '#FFF6E4', '#FAE6C0']];
        [$a, $b, $c] = $tones[($seed + $variant) % 5];
        $scale = [1, .86, 1.12, .94, 1.04][($seed + $variant * 2) % 5];
        $dx = [0, -34, 30, -14, 18][($variant + $seed) % 5];
        $pin = 'M80 136C55 112 41 94 41 72a39 39 0 1178 0c0 22-14 40-39 64z';
        $ext = '';
        for ($i = 9; $i >= 1; $i--) {
            $ext .= "<path d='$pin' transform='translate(".($i * .9).' '.($i * 1.1).")' fill='url(#gd)'/>";
        }
        $lines = '';
        for ($i = 1; $i < 7; $i++) {
            $lines .= "<circle cx='450' cy='330' r='".($i * 62)."' fill='none' stroke='#E8B25A' stroke-opacity='".(.28 - $i * .03)."' stroke-width='1'/>";
        }

        return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 900 600' preserveAspectRatio='xMidYMid slice'><defs>"
            ."<linearGradient id='bg' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='$a'/><stop offset='.55' stop-color='$b'/><stop offset='1' stop-color='$c'/></linearGradient>"
            ."<linearGradient id='gf' x1='.1' y1='0' x2='.9' y2='1'><stop offset='0' stop-color='#FFE29A'/><stop offset='.38' stop-color='#F9B83F'/><stop offset='.75' stop-color='#EE9317'/><stop offset='1' stop-color='#D9770A'/></linearGradient>"
            ."<linearGradient id='gd' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='#C97A12'/><stop offset='1' stop-color='#8F4E05'/></linearGradient>"
            ."<linearGradient id='gc' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='#fff'/><stop offset='1' stop-color='#FFEBC4'/></linearGradient>"
            ."<radialGradient id='hl' cx='.3' cy='.2' r='.7'><stop offset='0' stop-color='#fff' stop-opacity='.85'/><stop offset='1' stop-color='#fff' stop-opacity='0'/></radialGradient>"
            ."<filter id='bl' x='-30%' y='-100%' width='160%' height='300%'><feGaussianBlur stdDeviation='4'/></filter></defs>"
            ."<rect width='900' height='600' fill='url(#bg)'/>$lines"
            ."<g transform='translate(".(450 - 80 * 2.2 * $scale + $dx).' '.(300 - 80 * 2.2 * $scale).") scale(".(2.2 * $scale).")'>"
            ."<ellipse cx='86' cy='146' rx='42' ry='7' fill='#7a4a00' opacity='.3' filter='url(#bl)'/>$ext"
            ."<path d='$pin' fill='url(#gf)'/><path d='$pin' fill='none' stroke='#fff' stroke-opacity='.55' stroke-width='1.6'/>"
            ."<path d='M52 56a30 30 0 0124-24' stroke='#fff' stroke-width='5' fill='none' stroke-linecap='round' opacity='.7'/>"
            ."<ellipse cx='62' cy='46' rx='22' ry='14' fill='url(#hl)' transform='rotate(-30 62 46)'/>"
            ."<g transform='translate(0 -2)'><path d='M58 76L80 55l22 21' fill='none' stroke='#fff' stroke-width='7' stroke-linecap='round' stroke-linejoin='round'/>"
            ."<path d='M65 75h30v20a4 4 0 01-4 4H69a4 4 0 01-4-4z' fill='url(#gc)'/><path d='M74 99V88a6 6 0 0112 0v11z' fill='url(#gf)'/></g></g></svg>";
    }
}
