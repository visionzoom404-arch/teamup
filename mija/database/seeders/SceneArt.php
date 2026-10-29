<?php

namespace Database\Seeders;

/**
 * تصاویر مصور باکیفیت (SVG) برای اقامتگاه‌های نمونه — تا زمان بارگذاری عکس واقعی.
 * kind: forest | cabin | sea | mountain | villa    time: 0=طلوع/غروب طلایی 1=روز 2=عصر 3=شب
 */
class SceneArt
{
    private const SKY = [
        0 => ['#ffd9a0', '#ff9d6c', '#c9486b', '#ffe9b8'],
        1 => ['#7ec8ee', '#bfe6f8', '#fff4dc', '#fff6c8'],
        2 => ['#2d3a6b', '#c4506f', '#ffa15c', '#ffd68a'],
        3 => ['#0b1230', '#1c2a5a', '#3b4f8c', '#e9eeff'],
    ];

    public static function svg(string $kind, int $seed, int $variant): string
    {
        mt_srand($seed * 7919 + 13);
        $t = $variant % 4;
        [$c1, $c2, $c3, $sun] = self::SKY[$t];
        $night = $t === 3;
        $uid = 'a'.$seed.$variant;
        $o = '';

        // آسمان + هاله‌ی خورشید
        $sx = mt_rand(140, 760);
        $sy = $t === 1 ? mt_rand(70, 130) : mt_rand(210, 270);
        $o .= "<rect width='900' height='600' fill='url(#sky$uid)'/>";
        $o .= "<circle cx='$sx' cy='$sy' r='210' fill='url(#glow$uid)'/>";
        $o .= "<circle cx='$sx' cy='$sy' r='".($night ? 34 : 46)."' fill='$sun' ".($night ? "" : "opacity='.95'")."/>";
        if ($night) {
            $o .= "<circle cx='".($sx + 14)."' cy='".($sy - 8)."' r='30' fill='#0e1738'/>"; // هلال
            for ($i = 0; $i < 70; $i++) {
                $o .= "<circle cx='".mt_rand(0, 900)."' cy='".mt_rand(0, 330)."' r='".(mt_rand(4, 14) / 10)."' fill='#fff' opacity='".(mt_rand(4, 10) / 10)."'/>";
            }
        } else {
            for ($i = 0; $i < 4; $i++) {
                $o .= self::cloud(mt_rand(20, 760), mt_rand(60, 210), mt_rand(60, 130), $t === 2 ? '#ffc6a0' : '#ffffff', $t === 1 ? .9 : .55);
            }
        }

        // کوه‌های لایه‌ای با مه
        $mt = $night ? ['#16224d', '#101a3e', '#0a1230'] : ($t === 2 ? ['#7a5a8f', '#5b4a7e', '#3f3a66'] : ($kind === 'mountain' ? ['#9db8d6', '#7c9cc0', '#5f84ad'] : ['#a9c0b4', '#86a596', '#678a7b']));
        foreach ([[300, 150, 0], [350, 120, 1], [400, 90, 2]] as [$base, $amp, $i]) {
            $d = "M0 $base";
            for ($x = 0; $x <= 900; $x += 60) {
                $d .= ' L'.$x.' '.($base - mt_rand(10, $amp) - (($x / 60) % 2 ? 0 : 25));
            }
            $o .= "<path d='$d L900 600 L0 600Z' fill='{$mt[$i]}'/>";
            $o .= "<rect y='".($base - 40)."' width='900' height='70' fill='url(#fog$uid)' opacity='.55'/>";
        }

        // دریا
        if ($kind === 'sea') {
            $o .= "<rect y='360' width='900' height='240' fill='url(#sea$uid)'/>";
            for ($i = 0; $i < 22; $i++) {
                $y = mt_rand(372, 560);
                $x = mt_rand(0, 860);
                $o .= "<path d='M$x $y q14 -7 28 0 t28 0' stroke='#fff' stroke-width='2' fill='none' opacity='".(mt_rand(2, 6) / 10)."'/>";
            }
            $o .= "<rect x='".($sx - 18)."' y='360' width='36' height='220' fill='url(#shine$uid)' opacity='.55'/>";
        }

        // زمین سبز و جنگل
        $g = $night ? ['#0f2b25', '#0a1d1a'] : ($t === 2 ? ['#3f5b46', '#2b4234'] : ($kind === 'sea' ? ['#d9bd84', '#c5a56a'] : ['#4f9b62', '#2f7048']));
        $o .= "<path d='M0 440 Q220 400 450 430 T900 420 V600 H0Z' fill='{$g[0]}'/>";
        $o .= "<path d='M0 500 Q300 465 560 495 T900 480 V600 H0Z' fill='{$g[1]}'/>";
        $pine = $night ? ['#0c2a22', '#0a2019'] : ($t === 2 ? ['#27493a', '#1d382d'] : ['#1f6b43', '#2c8a55']);
        $count = $kind === 'sea' ? 5 : 22;
        for ($i = 0; $i < $count; $i++) {
            $o .= self::pine(mt_rand(10, 890), mt_rand(425, 520), mt_rand(60, 140), $pine[mt_rand(0, 1)]);
        }

        // ساختمان
        $bx = mt_rand(250, 380);
        $by = 470;
        $glow = $night || $t === 2 ? '#ffd36a' : '#bfe6f8';
        if ($kind === 'cabin' || ($kind === 'forest' && $variant % 2 === 0)) {
            $o .= self::cabin($bx, $by, $glow, $night || $t === 2);
        } elseif ($kind === 'mountain') {
            $o .= self::aframe($bx, $by, $glow, $night || $t === 2);
        } else {
            $o .= self::villa($bx, $by, $glow, $night || $t === 2, $kind === 'sea' || $variant % 2 === 0);
        }

        // پرنده و ذره‌های نور
        if (! $night) {
            for ($i = 0; $i < 3; $i++) {
                $x = mt_rand(100, 800);
                $y = mt_rand(90, 200);
                $o .= "<path d='M$x $y q8 -9 16 0 q8 -9 16 0' stroke='#33415c' stroke-width='2' fill='none' opacity='.55'/>";
            }
        }
        // وینیت
        $o .= "<rect width='900' height='600' fill='url(#vig$uid)'/>";

        $defs = "<defs>
<linearGradient id='sky$uid' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='$c1'/><stop offset='.55' stop-color='$c2'/><stop offset='1' stop-color='$c3'/></linearGradient>
<radialGradient id='glow$uid'><stop offset='0' stop-color='$sun' stop-opacity='".($night ? .35 : .85)."'/><stop offset='1' stop-color='$sun' stop-opacity='0'/></radialGradient>
<linearGradient id='fog$uid' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='#fff' stop-opacity='0'/><stop offset='1' stop-color='".($night ? '#3b4f8c' : '#fff')."' stop-opacity='.7'/></linearGradient>
<linearGradient id='sea$uid' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='".($night ? '#16306b' : '#58c0ea')."'/><stop offset='1' stop-color='".($night ? '#0a1a44' : '#1f86c4')."'/></linearGradient>
<linearGradient id='shine$uid' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='$sun'/><stop offset='1' stop-color='$sun' stop-opacity='0'/></linearGradient>
<radialGradient id='vig$uid' cx='.5' cy='.5' r='.75'><stop offset='.6' stop-color='#000' stop-opacity='0'/><stop offset='1' stop-color='#000' stop-opacity='.28'/></radialGradient>
<linearGradient id='wall$uid' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='#fffaf0'/><stop offset='1' stop-color='#e8dcc6'/></linearGradient>
<linearGradient id='wood$uid' x1='0' y1='0' x2='0' y2='1'><stop offset='0' stop-color='#a8693f'/><stop offset='1' stop-color='#74431f'/></linearGradient>
</defs>";

        return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 900 600' preserveAspectRatio='xMidYMid slice'>".str_replace(["wall$uid", "wood$uid"], ["wall$uid", "wood$uid"], $defs).str_replace(['url(#wall)', 'url(#wood)'], ["url(#wall$uid)", "url(#wood$uid)"], $o).'</svg>';
    }

    private static function cloud(int $x, int $y, int $w, string $c, float $op): string
    {
        $h = $w / 3;

        return "<g opacity='$op' fill='$c'><ellipse cx='".($x + $w / 2)."' cy='$y' rx='".($w / 2)."' ry='".($h / 2)."'/><ellipse cx='".($x + $w * .3)."' cy='".($y - $h / 3)."' rx='".($w * .22)."' ry='".($h * .5)."'/><ellipse cx='".($x + $w * .62)."' cy='".($y - $h / 2.4)."' rx='".($w * .25)."' ry='".($h * .56)."'/></g>";
    }

    private static function pine(int $x, int $y, int $h, string $c): string
    {
        $w = $h * .42;
        $d = '';
        for ($i = 0; $i < 4; $i++) {
            $ty = $y - $h + $i * ($h * .24);
            $ww = $w * (.45 + $i * .22);
            $d .= "M$x ".($ty - $h * .02)." L".($x - $ww)." ".($ty + $h * .26)." L".($x + $ww)." ".($ty + $h * .26)."Z ";
        }

        return "<path d='$d' fill='$c'/><rect x='".($x - 3)."' y='$y' width='6' height='".($h * .12)."' fill='#3a2a1c'/>";
    }

    private static function windowRects(array $pos, string $glow, bool $lit): string
    {
        $o = '';
        foreach ($pos as [$x, $y, $w, $h]) {
            $o .= "<rect x='$x' y='$y' width='$w' height='$h' rx='3' fill='$glow' ".($lit ? "" : "opacity='.85'")."/>";
            if ($lit) {
                $o .= "<rect x='".($x - 6)."' y='".($y - 6)."' width='".($w + 12)."' height='".($h + 12)."' rx='8' fill='$glow' opacity='.25'/>";
            }
            $o .= "<path d='M".($x + $w / 2)." $y v$h M$x ".($y + $h / 2)." h$w' stroke='#5a3a22' stroke-width='2' opacity='.6'/>";
        }

        return $o;
    }

    private static function cabin(int $x, int $y, string $glow, bool $lit): string
    {
        $o = "<ellipse cx='".($x + 110)."' cy='".($y + 8)."' rx='150' ry='14' fill='#000' opacity='.22'/>";
        $o .= "<rect x='".($x + 150)."' y='".($y - 150)."' width='24' height='70' fill='#6b6b72'/>";
        for ($i = 0; $i < 4; $i++) {
            $o .= "<circle cx='".($x + 162 + $i * 8)."' cy='".($y - 165 - $i * 26)."' r='".(12 + $i * 6)."' fill='#fff' opacity='".(.5 - $i * .1)."'/>";
        }
        $o .= "<rect x='$x' y='".($y - 90)."' width='220' height='90' fill='url(#wood)'/>";
        for ($i = 1; $i < 7; $i++) {
            $o .= "<path d='M$x ".($y - 90 + $i * 13)." h220' stroke='#5a3216' stroke-width='1.5' opacity='.55'/>";
        }
        $o .= "<path d='M".($x - 22)." ".($y - 80)." L".($x + 110)." ".($y - 190)." L".($x + 242)." ".($y - 80)." L".($x + 220)." ".($y - 74)." L".($x + 110)." ".($y - 166)." L$x ".($y - 74)."Z' fill='#3a2415'/>";
        $o .= "<path d='M".($x + 110)." ".($y - 190)." L".($x + 242)." ".($y - 80)." L".($x + 110)." ".($y - 166)."Z' fill='#4b2f1b'/>";
        $o .= self::windowRects([[$x + 26, $y - 62, 44, 38], [$x + 150, $y - 62, 44, 38]], $glow, $lit);
        $o .= "<rect x='".($x + 90)."' y='".($y - 66)."' width='42' height='66' rx='4' fill='#3a2415'/><circle cx='".($x + 124)."' cy='".($y - 32)."' r='3' fill='#ffd36a'/>";

        return $o;
    }

    private static function aframe(int $x, int $y, string $glow, bool $lit): string
    {
        $o = "<ellipse cx='".($x + 100)."' cy='".($y + 6)."' rx='130' ry='12' fill='#000' opacity='.22'/>";
        $o .= "<path d='M$x $y L".($x + 100)." ".($y - 230)." L".($x + 200)." {$y}Z' fill='#2a2c33'/>";
        $o .= "<path d='M".($x + 100)." ".($y - 230)." L".($x + 200)." {$y} L".($x + 140)." {$y}Z' fill='#1d1f25'/>";
        $o .= "<path d='M".($x + 100)." ".($y - 196)." L".($x + 160)." ".($y - 20)." L".($x + 40)." ".($y - 20)."Z' fill='$glow' opacity='".($lit ? 1 : .8)."'/>";
        if ($lit) {
            $o .= "<path d='M".($x + 100)." ".($y - 196)." L".($x + 160)." ".($y - 20)." L".($x + 40)." ".($y - 20)."Z' fill='$glow' opacity='.3' transform='translate(0 0) scale(1.12) translate(".(-$x * .12 - 12)." ".(-$y * .12 + 6).")'/>";
        }
        $o .= "<path d='M".($x + 100)." ".($y - 196)." V".($y - 20)." M".($x + 70)." ".($y - 110)." H".($x + 130)." ' stroke='#2a2c33' stroke-width='3'/>";
        $o .= "<rect x='".($x - 30)."' y='".($y - 18)."' width='260' height='18' rx='3' fill='#74431f'/>";

        return $o;
    }

    private static function villa(int $x, int $y, string $glow, bool $lit, bool $pool): string
    {
        $o = "<ellipse cx='".($x + 120)."' cy='".($y + 8)."' rx='190' ry='16' fill='#000' opacity='.22'/>";
        // طبقه‌ی همکف و بالا (مدرن، سقف تخت)
        $o .= "<rect x='$x' y='".($y - 80)."' width='260' height='80' fill='url(#wall)'/>";
        $o .= "<rect x='".($x + 30)."' y='".($y - 150)."' width='190' height='72' fill='#f4ebd8'/>";
        $o .= "<rect x='".($x - 10)."' y='".($y - 90)."' width='280' height='12' fill='#2a2c33'/><rect x='".($x + 20)."' y='".($y - 160)."' width='210' height='12' fill='#2a2c33'/>";
        $o .= self::windowRects([[$x + 12, $y - 66, 70, 52], [$x + 178, $y - 66, 70, 52], [$x + 56, $y - 140, 54, 42], [$x + 130, $y - 140, 54, 42]], $glow, $lit);
        $o .= "<rect x='".($x + 100)."' y='".($y - 62)."' width='52' height='62' rx='3' fill='#74431f'/>";
        // بالکن شیشه‌ای
        $o .= "<rect x='".($x + 30)."' y='".($y - 96)."' width='190' height='6' fill='#8a8f99' opacity='.7'/>";
        if ($pool) {
            $o .= "<path d='M".($x - 150)." ".($y + 14)." h240 l30 30 h-300 z' fill='#5fd0f2'/><path d='M".($x - 130)." ".($y + 20)." h200 l20 20 h-240 z' fill='#a8ecff' opacity='.7'/>";
            $o .= "<path d='M".($x - 110)." ".($y + 28)." q20 -5 40 0 t40 0 t40 0' stroke='#fff' stroke-width='2' fill='none' opacity='.8'/>";
            $o .= "<rect x='".($x + 120)."' y='".($y + 22)."' width='46' height='9' rx='4' fill='#fff' opacity='.9'/><rect x='".($x + 176)."' y='".($y + 22)."' width='46' height='9' rx='4' fill='#fff' opacity='.9'/>";
        }

        return $o;
    }
}
