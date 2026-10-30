<?php

namespace App\Support;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * ارسال پیامک از طریق پنل ippanel (REST).
 * توکن و شماره‌ی خط از «پنل مدیریت ← تنظیمات ← پیامک» وارد می‌شود.
 * اگر ارسال خاموش یا توکن خالی باشد، پیام فقط در گزارش پیامک‌ها ثبت می‌شود.
 * هر خطا به فارسی در Sms::$lastError می‌آید تا به کاربر/مدیر نشان داده شود.
 */
class Sms
{
    public static ?string $lastError = null;

    private const BASE = 'https://rest.ippanel.com/v1';

    public static function e164(string $phone): string
    {
        $p = preg_replace('/\D/', '', $phone);

        return '+98'.ltrim(preg_replace('/^(98|0)/', '', $p), '0');
    }

    /** شماره‌ی خط ارسال‌کننده را به قالب ippanel (+98…) درمی‌آورد */
    public static function originator(): string
    {
        $o = trim((string) setting('sms_originator'));
        $d = preg_replace('/\D/', '', $o);
        if ($d === '') {
            return '';
        }
        $d = ltrim($d, '0');

        return '+'.(str_starts_with($d, '98') ? $d : '98'.$d);
    }

    public static function enabled(): bool
    {
        return (bool) (int) setting('sms_enabled') && trim((string) setting('sms_token')) !== '';
    }

    private static function base(): string
    {
        $b = rtrim((string) (setting('sms_base_url') ?: self::BASE), '/');

        return preg_replace('~/messages$~', '', $b);
    }

    private static function http()
    {
        return Http::withHeaders(['Authorization' => 'AccessKey '.trim((string) setting('sms_token'))])->timeout(10)->acceptJson();
    }

    public static function send(?string $phone, string $text, ?string $url = null, bool $signature = true): bool
    {
        self::$lastError = null;
        if (! $phone || ! preg_match('/^09\d{9}$/', $phone)) {
            self::$lastError = 'شماره‌ی گیرنده معتبر نیست.';

            return false;
        }
        $msg = $text.($url ? "\n".$url : '').($signature ? "\nمی‌جا" : '');
        $log = SmsLog::create(['phone' => $phone, 'message' => $msg, 'status' => 'logged']);

        if (! (int) setting('sms_enabled')) {
            self::$lastError = 'ارسال پیامک در تنظیمات خاموش است.';

            return false;
        }
        if (trim((string) setting('sms_token')) === '') {
            self::$lastError = 'توکن پنل پیامک وارد نشده است.';

            return false;
        }
        if (self::originator() === '') {
            self::$lastError = 'شماره‌ی خط ارسال‌کننده وارد نشده است.';

            return false;
        }
        try {
            $res = self::http()->post(self::base().'/messages', ['originator' => self::originator(), 'recipient' => [self::e164($phone)], 'message' => $msg]);
            $ok = $res->successful() && self::bodyOk($res->json());
            if (! $ok) {
                self::$lastError = self::explain($res->status(), $res->json(), $res->body());
            }
            $log->update(['status' => $ok ? 'sent' : 'failed', 'response' => mb_substr($ok ? $res->body() : (self::$lastError.' | '.$res->body()), 0, 250)]);

            return $ok;
        } catch (\Throwable $e) {
            self::$lastError = 'اتصال به سرور پنل پیامک برقرار نشد (اینترنت سرور یا آدرس پنل را بررسی کنید).';
            $log->update(['status' => 'failed', 'response' => mb_substr(self::$lastError.' | '.$e->getMessage(), 0, 250)]);

            return false;
        }
    }

    private static function bodyOk($j): bool
    {
        if (! is_array($j)) {
            return true;
        }
        $st = strtolower((string) ($j['status'] ?? 'ok'));

        return in_array($st, ['ok', 'success', 'sent'], true) && (int) ($j['code'] ?? 200) < 300;
    }

    /** پیام خطای ippanel را به فارسی قابل‌فهم تبدیل می‌کند */
    public static function explain(int $http, $json, string $raw = ''): string
    {
        $m = is_array($json) ? trim((string) ($json['error_message'] ?? ($json['message'] ?? (is_string($json['data'] ?? null) ? $json['data'] : '')))) : '';
        $hay = strtolower($m.' '.$raw);
        $code = is_array($json) ? (int) ($json['code'] ?? 0) : 0;
        if ($http === 401 || $http === 403 || $code === 401 || str_contains($hay, 'unauthor') || str_contains($hay, 'access key') || str_contains($hay, 'accesskey') || str_contains($hay, 'invalid token')) {
            return 'توکن (AccessKey) پنل پیامک اشتباه، منقضی یا بدون دسترسی است. توکن را از پنل ippanel دوباره بسازید.';
        }
        if (str_contains($hay, 'credit') || str_contains($hay, 'balance') || str_contains($hay, 'اعتبار') || $http === 402) {
            return 'اعتبار پنل پیامک تمام شده است؛ پنل را شارژ کنید.';
        }
        if (str_contains($hay, 'originator') || str_contains($hay, 'sender') || str_contains($hay, 'line') || str_contains($hay, 'خط')) {
            return 'شماره‌ی خط ارسال‌کننده اشتباه است یا برای این پنل فعال نیست (مثال درست: 3000505 یا +983000505).';
        }
        if (str_contains($hay, 'recipient') || str_contains($hay, 'receiver') || str_contains($hay, 'mobile') || str_contains($hay, 'number')) {
            return 'شماره‌ی گیرنده برای پنل پیامک نامعتبر است.';
        }
        if (str_contains($hay, 'filter') || str_contains($hay, 'forbidden') || str_contains($hay, 'ممنوع') || str_contains($hay, 'black')) {
            return 'متن پیامک شامل عبارت فیلترشده/ممنوع است و توسط اپراتور رد شد.';
        }
        if (str_contains($hay, 'message') && (str_contains($hay, 'empty') || str_contains($hay, 'long') || str_contains($hay, 'length'))) {
            return 'متن پیامک خالی یا بیش از حد طولانی است.';
        }
        if ($http >= 500) {
            return 'سرور پنل پیامک در حال حاضر خطا می‌دهد؛ چند دقیقه بعد دوباره تلاش کنید.';
        }
        if ($http === 429) {
            return 'تعداد درخواست‌ها به پنل پیامک زیاد بود؛ کمی بعد تلاش کنید.';
        }

        return 'پنل پیامک خطا برگرداند'.($m !== '' ? ': '.$m : " (کد $http)").'.';
    }

    /** اعتبار پنل (عدد) یا null و پیام خطا در lastError */
    public static function credit(): ?float
    {
        self::$lastError = null;
        try {
            $res = self::http()->get(self::base().'/credit');
            $j = $res->json();
            if ($res->successful() && self::bodyOk($j)) {
                $v = is_array($j) ? ($j['data']['credit'] ?? ($j['credit'] ?? ($j['data'] ?? null))) : null;

                return is_numeric($v) ? (float) $v : null;
            }
            self::$lastError = self::explain($res->status(), $j, $res->body());
        } catch (\Throwable $e) {
            self::$lastError = 'اتصال به سرور پنل پیامک برقرار نشد (اینترنت سرور یا آدرس پنل را بررسی کنید).';
        }

        return null;
    }

    /**
     * بررسی مرحله‌به‌مرحله‌ی تنظیمات؛ هر مرحله: [key, عنوان، وضعیت ok|fail|warn|skip، پیام فارسی]
     * اگر $test شماره‌ای باشد، پیامک آزمایشی هم می‌فرستد.
     */
    public static function diagnose(?string $test = null): array
    {
        $steps = [];
        $on = (bool) (int) setting('sms_enabled');
        $steps[] = ['enabled', 'روشن بودن ارسال', $on ? 'ok' : 'fail', $on ? 'ارسال پیامک فعال است.' : 'کلید «ارسال فعال باشد» خاموش است؛ تا روشن نشود پیامکی نمی‌رود.'];
        $tok = trim((string) setting('sms_token'));
        $tokOk = strlen($tok) >= 20;
        $steps[] = ['token', 'توکن (AccessKey)', $tok === '' ? 'fail' : ($tokOk ? 'ok' : 'warn'), $tok === '' ? 'توکن وارد نشده است. از پنل ippanel ← تنظیمات ← توکن بسازید و اینجا بچسبانید.' : ($tokOk ? 'توکن وارد شده است.' : 'توکن خیلی کوتاه به نظر می‌رسد؛ احتمالاً ناقص کپی شده است.')];
        $credit = null;
        $conn = 'skip';
        $connMsg = 'ابتدا توکن را وارد کنید.';
        if ($tok !== '') {
            $credit = self::credit();
            if ($credit !== null) {
                $conn = 'ok';
                $connMsg = 'اتصال برقرار است. اعتبار پنل: '.fa(number_format($credit)).' (واحد پنل).';
                Cache::put('mija.sms.credit', ['v' => $credit, 'at' => now()->toIso8601String()], 3600);
            } else {
                $conn = 'fail';
                $connMsg = self::$lastError ?: 'اتصال به پنل ناموفق بود.';
                Cache::forget('mija.sms.credit');
            }
        }
        $steps[] = ['conn', 'اتصال به پنل و اعتبار', $conn, $connMsg];
        $o = self::originator();
        $oOk = $o !== '' && preg_match('/^\+98\d{6,12}$/', $o);
        $steps[] = ['line', 'شماره‌ی خط ارسال‌کننده', $o === '' ? 'fail' : ($oOk ? 'ok' : 'warn'), $o === '' ? 'شماره‌ی خط وارد نشده است (مثلاً 3000505).' : ($oOk ? 'خط ارسال: '.$o : 'قالب شماره‌ی خط عجیب است؛ فقط عدد خط پنل را بنویسید (مثلاً 3000505).')];
        if ($test) {
            $okAll = $on && $tokOk && $conn === 'ok' && $oOk;
            if (! $okAll) {
                $steps[] = ['test', 'پیامک آزمایشی', 'skip', 'تا مراحل بالا درست نشود پیامک آزمایشی ارسال نمی‌شود.'];
            } else {
                $sent = self::send($test, 'پیامک آزمایشی از پنل مدیریت می‌جا', route('home'));
                $steps[] = ['test', 'پیامک آزمایشی', $sent ? 'ok' : 'fail', $sent ? 'پیامک آزمایشی به '.fa($test).' ارسال شد؛ گوشی را بررسی کنید.' : (self::$lastError ?: 'ارسال ناموفق بود.')];
            }
        }

        return $steps;
    }
}
