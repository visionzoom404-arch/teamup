<?php

namespace Database\Seeders;

/** تصویر جایگزین: لوگوی اصلی می‌جا (بدون تغییر) روی زمینه‌ی کرم/طلایی تا زمان بارگذاری عکس واقعی */
class LogoArt
{
    private static ?string $mark = null;

    public static function svg(string|int $kind = 0, int $seed = 0, int $variant = 0): string
    {
        if (is_int($kind)) { // فراخوانی کوتاه: svg(seed, variant)
            [$variant, $seed] = [$seed, $kind];
        }
        self::$mark ??= base64_encode(file_get_contents(__DIR__.'/logo-mark.png'));
        $tones = [['#FFFFFF', '#FFF1D6', '#F8DDAA'], ['#FFFDF8', '#FFEFD0', '#F6D391'], ['#FFFFFF', '#FFF4DE', '#F9E1B4'], ['#FFFCF5', '#FFEBC6', '#F4CB82'], ['#FFFFFF', '#FFF6E4', '#FAE6C0']];
        [$a, $b, $c] = $tones[($seed + $variant) % 5];
        $scale = [1, .86, 1.12, .94, 1.04][($seed + $variant * 2) % 5];
        $dx = [0, -34, 30, -14, 18][($variant + $seed) % 5];
        $lines = '';
        for ($i = 1; $i < 7; $i++) {
            $lines .= "<circle cx='450' cy='320' r='".($i * 62)."' fill='none' stroke='#E8B25A' stroke-opacity='".(.28 - $i * .03)."' stroke-width='1'/>";
        }
        $h = 250 * $scale;
        $w = $h * 479 / 555;

        return "<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' viewBox='0 0 900 600' preserveAspectRatio='xMidYMid slice'><defs>"
            ."<linearGradient id='bg' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='$a'/><stop offset='.55' stop-color='$b'/><stop offset='1' stop-color='$c'/></linearGradient>"
            ."<filter id='bl' x='-30%' y='-100%' width='160%' height='300%'><feGaussianBlur stdDeviation='6'/></filter></defs>"
            ."<rect width='900' height='600' fill='url(#bg)'/>$lines"
            .'<ellipse cx="'.(450 + $dx).'" cy="'.(320 + $h / 2 + 8).'" rx="'.($w * .42).'" ry="10" fill="#7a4a00" opacity=".22" filter="url(#bl)"/>'
            .'<image x="'.(450 + $dx - $w / 2).'" y="'.(320 - $h / 2).'" width="'.$w.'" height="'.$h.'" xlink:href="data:image/png;base64,'.self::$mark.'"/></svg>';
    }
}
