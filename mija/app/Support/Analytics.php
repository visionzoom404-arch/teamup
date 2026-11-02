<?php

namespace App\Support;

/** تشخیص ربات/دستگاه/مرورگر/منبع ورودی از User-Agent و Referer (بدون ذخیره‌ی IP) */
class Analytics
{
    private const BOTS = [
        'Googlebot' => 'Googlebot|Google-InspectionTool|GoogleOther|AdsBot-Google|Mediapartners', 'Bingbot' => 'bingbot|BingPreview', 'Yandex' => 'YandexBot|YandexImages',
        'Baidu' => 'Baiduspider', 'DuckDuckGo' => 'DuckDuckBot', 'Apple' => 'Applebot', 'Ahrefs' => 'AhrefsBot', 'Semrush' => 'SemrushBot', 'Facebook' => 'facebookexternalhit|meta-externalagent',
        'Twitter' => 'Twitterbot', 'Telegram' => 'TelegramBot', 'WhatsApp' => 'WhatsApp', 'LinkedIn' => 'LinkedInBot', 'ByteDance' => 'Bytespider', 'GPTBot' => 'GPTBot|ChatGPT-User|OAI-SearchBot',
        'ClaudeBot' => 'ClaudeBot|Claude-User', 'Petal' => 'PetalBot', 'Lighthouse' => 'Chrome-Lighthouse|PageSpeed',
    ];

    public static function bot(string $ua): ?string
    {
        if ($ua === '') {
            return 'بدون UA';
        }
        foreach (self::BOTS as $name => $re) {
            if (preg_match('~'.$re.'~i', $ua)) {
                return $name;
            }
        }

        return preg_match('~bot|crawl|spider|slurp|headless|curl/|wget|python-requests|go-http|okhttp/|monitor|scanner|httpclient|libwww|axios|node-fetch~i', $ua) ? 'سایر ربات‌ها' : null;
    }

    public static function device(string $ua, bool $app): string
    {
        if ($app) {
            return 'app';
        }
        if (preg_match('~iPad|Tablet|PlayBook|Silk~i', $ua) || (str_contains($ua, 'Android') && ! str_contains($ua, 'Mobile'))) {
            return 'tablet';
        }

        return preg_match('~Mobi|iPhone|iPod|Android|Windows Phone~i', $ua) ? 'mobile' : 'desktop';
    }

    public static function browser(string $ua, bool $app): string
    {
        if ($app) {
            return 'اپ می‌جا';
        }
        foreach (['Edge' => 'Edg(e|A|iOS)?/', 'Opera' => 'OPR/|Opera', 'Samsung' => 'SamsungBrowser', 'UC' => 'UCBrowser', 'Firefox' => 'Firefox/|FxiOS', 'Chrome' => 'Chrome/|CriOS', 'Safari' => 'Safari/'] as $n => $re) {
            if (preg_match('~'.$re.'~', $ua)) {
                return $n;
            }
        }

        return 'سایر';
    }

    public static function os(string $ua): string
    {
        return match (true) {
            (bool) preg_match('~iPhone|iPad|iPod~', $ua) => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') || str_contains($ua, 'X11') => 'Linux',
            default => 'سایر',
        };
    }

    /** [کلید منبع, میزبان ارجاع‌دهنده] */
    public static function source(?string $referer, ?string $utm, string $ownHost): array
    {
        $host = $referer ? strtolower((string) parse_url($referer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^(www|m|l|lm|mobile)\./', '', $host);
        $own = preg_replace('/^www\./', '', strtolower($ownHost));
        $map = [
            'google' => '~(^|\.)google\.~', 'bing' => '~bing\.com$~', 'yahoo' => '~yahoo\.~', 'duckduckgo' => '~duckduckgo\.com$~', 'yandex' => '~yandex\.~',
            'instagram' => '~instagram\.com$~', 'telegram' => '~(^|\.)(t\.me|telegram\.(me|org))$~', 'whatsapp' => '~whatsapp\.com$|^wa\.me$~', 'twitter' => '~(^|\.)(t\.co|twitter\.com|x\.com)$~',
            'facebook' => '~facebook\.com$|fb\.com$~', 'linkedin' => '~linkedin\.com$|lnkd\.in$~', 'aparat' => '~aparat\.com$~', 'eitaa' => '~eitaa\.com$~', 'bale' => '~bale\.ai$~', 'rubika' => '~rubika\.ir$~',
            'bazaar' => '~cafebazaar\.ir$~', 'myket' => '~myket\.ir$~', 'sms' => '~mija\.click$~',
        ];
        if ($utm) {
            $u = strtolower(trim($utm));
            foreach ($map as $k => $re) {
                if (str_contains($u, $k)) {
                    return [$k, $host ?: null];
                }
            }

            return [mb_substr($u, 0, 24), $host ?: null];
        }
        if ($host === '') {
            return ['direct', null];
        }
        if ($host === $own) {
            return ['internal', $host];
        }
        foreach ($map as $k => $re) {
            if ($k !== 'sms' && preg_match($re, $host)) {
                return [$k, $host];
            }
        }

        return ['other', mb_substr($host, 0, 90)];
    }

    public const SOURCE_NAMES = [
        'direct' => 'مستقیم / بدون ارجاع', 'google' => 'گوگل', 'bing' => 'بینگ', 'yahoo' => 'یاهو', 'duckduckgo' => 'داک‌داک‌گو', 'yandex' => 'یاندکس', 'instagram' => 'اینستاگرام', 'telegram' => 'تلگرام',
        'whatsapp' => 'واتس‌اپ', 'twitter' => 'ایکس / توییتر', 'facebook' => 'فیسبوک', 'linkedin' => 'لینکدین', 'aparat' => 'آپارات', 'eitaa' => 'ایتا', 'bale' => 'بله', 'rubika' => 'روبیکا',
        'bazaar' => 'کافه‌بازار', 'myket' => 'مایکت', 'other' => 'سایر سایت‌ها', 'sms' => 'پیامک',
    ];

    public const DEVICE_NAMES = ['mobile' => 'موبایل', 'tablet' => 'تبلت', 'desktop' => 'دسکتاپ', 'app' => 'اپلیکیشن اندروید'];
}
