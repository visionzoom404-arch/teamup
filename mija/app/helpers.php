<?php

use App\Support\Jalali;

if (! function_exists('fa')) {
    function fa(string|int|float|null $v): string
    {
        return Jalali::digits($v);
    }
}
if (! function_exists('toman')) {
    function toman(int|float|null $v, bool $unit = true): string
    {
        return str_replace(',', '٬', fa(number_format((int) $v))).($unit ? ' تومان' : '');
    }
}
if (! function_exists('jdate')) {
    function jdate($date, string $fmt = 'j F Y'): string
    {
        return Jalali::format($date, $fmt);
    }
}
if (! function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return \App\Models\Setting::get($key, $default ?? config('mija.'.$key));
    }
}
