<?php

namespace App\Support;

/** بارکد Code 39 برای ووچر (خوانا با اسکنر معمولی) */
class Code39
{
    private const P = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn', '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw', '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn',
        'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw', 'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn', 'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww', 'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn', 'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn',
        'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw', 'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn', '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '*' => 'nwnnwnwnn',
    ];

    /** @return int[] پهنای متوالی میله/فاصله (۱ یا ۳ واحد)، شروع با میله */
    public static function widths(string $text): array
    {
        $text = '*'.strtoupper(preg_replace('/[^A-Za-z0-9\-. ]/', '', $text)).'*';
        $w = [];
        foreach (str_split($text) as $i => $ch) {
            foreach (str_split(self::P[$ch] ?? self::P['-']) as $c) {
                $w[] = $c === 'w' ? 3 : 1;
            }
            if ($i < strlen($text) - 1) {
                $w[] = 1;   // فاصله‌ی بین نویسه‌ها
            }
        }

        return $w;
    }

    public static function svg(string $text, int $h = 54): string
    {
        $w = self::widths($text);
        $x = 0;
        $rects = '';
        foreach ($w as $i => $u) {
            if ($i % 2 === 0) {
                $rects .= '<rect x="'.$x.'" y="0" width="'.$u.'" height="'.$h.'"/>';
            }
            $x += $u;
        }

        return '<svg class="vc-bar" viewBox="0 0 '.$x.' '.$h.'" preserveAspectRatio="none" role="img" aria-label="بارکد '.e($text).'" fill="currentColor">'.$rects.'</svg>';
    }
}
