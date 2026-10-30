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

if (! function_exists('site_image')) {
    /** تصویر سراسری سایت (مثلاً hero) که از طریق mija:photos بارگذاری شده؛ وگرنه null */
    function site_image(string $name): ?string
    {
        foreach (['jpg', 'webp', 'png'] as $ext) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists("site/$name.$ext")) {
                return asset("storage/site/$name.$ext").'?v='.\Illuminate\Support\Facades\Storage::disk('public')->lastModified("site/$name.$ext");
            }
        }

        return null;
    }
}

if (! function_exists('app_client')) {
    /** اگر درخواست از داخل اپلیکیشن اندروید می‌جا باشد: ['name'=>..,'code'=>..,'flavor'=>..]؛ وگرنه null */
    function app_client(): ?array
    {
        $req = request();
        if (! $req->attributes->has('mija.app_client')) {
            $ua = (string) $req->userAgent();
            $req->attributes->set('mija.app_client', preg_match('~MijaApp/([\d.]+) \(code (\d+); flavor (\w+)\)~', $ua, $m) ? ['name' => $m[1], 'code' => (int) $m[2], 'flavor' => $m[3]] : null);
        }

        return $req->attributes->get('mija.app_client');
    }
}

if (! function_exists('contact_lines')) {
    /** تنظیمات چندخطی «عنوان | مقدار» را به آرایه‌ی [[عنوان، مقدار], …] تبدیل می‌کند */
    function contact_lines(string $key): array
    {
        $out = [];
        foreach (preg_split('/\R/u', (string) setting($key)) as $ln) {
            $ln = trim($ln);
            if ($ln === '') {
                continue;
            }
            $p = array_map('trim', explode('|', $ln, 2));
            $out[] = count($p) === 2 && $p[1] !== '' ? [$p[0], $p[1]] : ['', $p[0]];
        }

        return $out;
    }
}
