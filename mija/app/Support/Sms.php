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

    /** آخرین خطا: نوع (auth|credit|pattern|transient|other) */
    public static ?string $lastKind = null;

    /** ارسال پیامک عادی (متن آزاد). دقت: بعضی خطوط ippanel متن آزاد را نمی‌پذیرند. */
    public static function send(?string $phone, string $text, ?string $url = null, bool $signature = true): bool
    {
        $msg = $text.($url ? "\n".$url : '').($signature ? "\nمی‌جا" : '');

        return self::raw($phone, $msg);
    }

    /** ارسال متن آماده (بدون تغییر) با ثبت در گزارش و تلاش مجدد خودکار در خطاهای موقت */
    public static function raw(?string $phone, string $msg, int $attempt = 0, ?string $kind = null): bool
    {
        self::$lastError = self::$lastKind = null;
        if (! $phone || ! preg_match('/^09\d{9}$/', $phone)) {
            self::$lastError = 'شماره‌ی گیرنده معتبر نیست.';

            return false;
        }
        $log = SmsLog::create(['phone' => $phone, 'message' => $msg, 'status' => 'logged', 'attempts' => $attempt, 'via' => 'plain',
            'payload' => json_encode(['t' => 'raw', 'p' => $phone, 'm' => $msg, 'k' => $kind], JSON_UNESCAPED_UNICODE)]);

        return self::deliverPlain($log, $phone, $msg);
    }

    private static function preflight(): ?string
    {
        if (! (int) setting('sms_enabled')) {
            return 'ارسال پیامک در تنظیمات خاموش است.';
        }
        if (trim((string) setting('sms_token')) === '') {
            return 'توکن پنل پیامک وارد نشده است.';
        }
        if (self::originator() === '') {
            return 'شماره‌ی خط ارسال‌کننده وارد نشده است.';
        }

        return null;
    }

    private static function deliverPlain(SmsLog $log, string $phone, string $msg): bool
    {
        if ($e = self::preflight()) {
            self::$lastError = $e;

            return false;
        }
        try {
            $res = self::http()->post(self::base().'/messages', ['originator' => self::originator(), 'recipient' => [self::e164($phone)], 'message' => $msg]);
            $ok = $res->successful() && self::bodyOk($res->json());
            if (! $ok) {
                self::$lastError = self::explain($res->status(), $res->json(), $res->body());
                self::$lastKind = self::kind($res->status(), $res->json(), $res->body(), false);
            }
            self::finish($log, $ok, $ok ? $res->body() : (self::$lastError.' | '.$res->body()), 'plain');

            return $ok;
        } catch (\Throwable $e) {
            self::$lastError = 'اتصال به سرور پنل پیامک برقرار نشد (اینترنت سرور یا آدرس پنل را بررسی کنید).';
            self::$lastKind = 'transient';
            self::finish($log, false, self::$lastError.' | '.$e->getMessage(), 'plain');

            return false;
        }
    }

    /** وضعیت لاگ و زمان تلاش بعدی (برای خطاهای موقت: ۲، ۸ و ۳۰ دقیقه بعد) */
    private static function finish(SmsLog $log, bool $ok, string $resp, string $via): void
    {
        $retry = ! $ok && self::$lastKind === 'transient' && $log->attempts < 3;
        $log->update([
            'status' => $ok ? 'sent' : 'failed', 'via' => $via, 'response' => mb_substr($resp, 0, 250),
            'retry_at' => $retry ? now()->addMinutes([2, 8, 30][$log->attempts] ?? 30) : null,
        ]);
    }

    /**
     * ارسال پیامک یک رویداد: اگر «کد پترنِ» تأییدشده‌ی همین رویداد در تنظیمات هست با پترن می‌رود
     * (POST /messages/patterns/send)، وگرنه — یا اگر ippanel پترن را نپذیرفت — به‌صورت عادی.
     * در پترن هرگز لینک نیست؛ در پیامک عادی فقط اگر «لینک کوتاه» روشن باشد، لینک کوتاه‌شده می‌آید.
     */
    public static function event(string $key, ?string $phone, array $vars = [], ?string $url = null, int $attempt = 0): bool
    {
        self::$lastError = self::$lastKind = null;
        $ev = SmsEvents::get($key) ?? SmsEvents::get('general');
        $key = SmsEvents::get($key) ? $key : 'general';
        if (! $phone || ! preg_match('/^09\d{9}$/', $phone)) {
            self::$lastError = 'شماره‌ی گیرنده معتبر نیست.';

            return false;
        }
        $values = [];
        foreach (SmsEvents::vars($key) as $name) {
            $v = trim(preg_replace('/\s+/u', ' ', (string) ($vars[$name] ?? '')));
            $values[$name] = mb_substr($v === '' ? '-' : $v, 0, $key === 'ad_promo' ? 140 : 60);
        }
        $text = SmsEvents::render($key, $values);
        $plain = $text;
        if ($url && SmsEvents::withLink()) {
            try {
                $plain .= "\n".\App\Models\ShortLink::make($url);
            } catch (\Throwable) {
            }
        }
        $log = SmsLog::create(['phone' => $phone, 'message' => $plain, 'status' => 'logged', 'attempts' => $attempt,
            'payload' => json_encode(['t' => 'event', 'k' => $key, 'p' => $phone, 'v' => $vars, 'u' => $url], JSON_UNESCAPED_UNICODE)]);

        if ($e = self::preflight()) {
            self::$lastError = $e;

            return false;
        }
        $code = SmsEvents::patternCode($key);
        $fallback = (int) setting('sms_plain_fallback');
        if ($code !== '') {
            try {
                $res = self::http()->post(self::base().'/messages/patterns/send', ['pattern_code' => $code, 'originator' => self::originator(), 'recipient' => self::e164($phone), 'values' => (object) $values]);
                $ok = $res->successful() && self::bodyOk($res->json());
                if ($ok) {
                    self::finish($log, true, 'pattern '.$code.' | '.$res->body(), 'pattern');

                    return true;
                }
                self::$lastError = self::explain($res->status(), $res->json(), $res->body(), true);
                self::$lastKind = self::kind($res->status(), $res->json(), $res->body(), true);
                if (self::$lastKind === 'pattern' && $fallback) {
                    $why = self::$lastError;
                    if (self::deliverPlain($log, $phone, $plain)) {
                        return true;
                    }
                    self::$lastError = $why.' | ارسال عادی هم ناموفق: '.self::$lastError;

                    return false;
                }
                self::finish($log, false, self::$lastError.' | pattern '.$code.' | '.$res->body(), 'pattern');

                return false;
            } catch (\Throwable $e) {
                self::$lastError = 'اتصال به سرور پنل پیامک برقرار نشد (اینترنت سرور یا آدرس پنل را بررسی کنید).';
                self::$lastKind = 'transient';
                self::finish($log, false, self::$lastError.' | '.$e->getMessage(), 'pattern');

                return false;
            }
        }
        if ($fallback) {
            return self::deliverPlain($log, $phone, $plain);
        }
        self::$lastError = 'کد پترنِ «'.$ev[0].'» وارد نشده و ارسال عادی خاموش است.';
        $log->update(['status' => 'failed', 'response' => self::$lastError]);

        return false;
    }

    /** پیامک‌های ناموفقِ موقت (قطعی اینترنت/خطای سرور) دوباره ارسال می‌شوند؛ هر پیام حداکثر ۳ بار */
    public static function retryDue(int $limit = 15): int
    {
        $n = 0;
        SmsLog::where('status', 'failed')->whereNotNull('retry_at')->where('retry_at', '<=', now())->orderBy('id')->limit($limit)->get()->each(function ($l) use (&$n) {
            $l->update(['retry_at' => null, 'status' => 'retried']);
            $p = json_decode((string) $l->payload, true) ?: [];
            $a = (int) $l->attempts + 1;
            if (($p['t'] ?? '') === 'event') {
                self::event($p['k'], $p['p'], $p['v'] ?? [], $p['u'] ?? null, $a);
            } elseif (($p['t'] ?? '') === 'raw') {
                self::raw($p['p'], $p['m'], $a, $p['k'] ?? null);
            }
            $n++;
        });

        return $n;
    }

    /** نوع خطا برای تصمیم‌گیری (تلاش مجدد / ارسال عادی) */
    private static function kind(int $http, $json, string $raw, bool $pattern): string
    {
        $m = is_array($json) ? trim((string) ($json['error_message'] ?? ($json['message'] ?? (is_string($json['data'] ?? null) ? $json['data'] : '')))) : '';
        $hay = strtolower($m.' '.$raw);
        if ($http === 401 || $http === 403 || str_contains($hay, 'unauthor') || str_contains($hay, 'access key') || str_contains($hay, 'accesskey')) {
            return 'auth';
        }
        if (str_contains($hay, 'credit') || str_contains($hay, 'balance') || str_contains($hay, 'اعتبار') || $http === 402) {
            return 'credit';
        }
        if ($http >= 500 || $http === 429) {
            return 'transient';
        }
        if ($pattern && (str_contains($hay, 'pattern') || str_contains($hay, 'الگو') || str_contains($hay, 'template') || str_contains($hay, 'variable') || str_contains($hay, 'values') || $http === 404 || $http === 400)) {
            return 'pattern';
        }

        return 'other';
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
    public static function explain(int $http, $json, string $raw = '', bool $pattern = false): string
    {
        $m = is_array($json) ? trim((string) ($json['error_message'] ?? ($json['message'] ?? (is_string($json['data'] ?? null) ? $json['data'] : '')))) : '';
        $hay = strtolower($m.' '.$raw);
        $code = is_array($json) ? (int) ($json['code'] ?? 0) : 0;
        if ($pattern && (str_contains($hay, 'pattern') || str_contains($hay, 'الگو') || str_contains($hay, 'template'))) {
            if (str_contains($hay, 'approv') || str_contains($hay, 'pending') || str_contains($hay, 'تایید') || str_contains($hay, 'تأیید')) {
                return 'این پترن هنوز توسط ippanel/اپراتور تأیید نشده است؛ پس از تأیید دوباره امتحان کنید.';
            }
            if (str_contains($hay, 'variable') || str_contains($hay, 'value') || str_contains($hay, 'متغیر')) {
                return 'نام یا تعداد متغیرهای پترن با متن ساخته‌شده در ippanel یکی نیست؛ متن پترن را دقیقاً از همین صفحه کپی کنید.';
            }
            return 'کد پترن اشتباه است یا در پنل ippanel وجود ندارد؛ کد را دوباره از بخش «الگوها» کپی کنید.';
        }
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

    /** وضعیت اتصال و اعتبار با کش کوتاه (برای نمایش خودکار هنگام باز شدن تب پیامک) */
    public static function status(bool $fresh = false): array
    {
        if (trim((string) setting('sms_token')) === '') {
            return ['state' => 'none', 'credit' => null, 'error' => null, 'at' => null];
        }
        $key = 'mija.sms.status.'.md5((string) setting('sms_token').(string) setting('sms_base_url'));
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, 60, function () {
            $c = self::credit();

            return ['state' => $c !== null ? 'ok' : 'fail', 'credit' => $c, 'error' => $c !== null ? null : self::$lastError, 'at' => now()->toIso8601String()];
        });
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
    public static function diagnose(?string $test = null, string $event = 'login_code'): array
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
                $ev = SmsEvents::get($event) ?? SmsEvents::get('login_code');
                $sample = $ev[5] ?? [];
                $sent = self::event($event, $test, $sample, route('home'));
                $steps[] = ['test', 'پیامک آزمایشی ('.$ev[0].')', $sent ? 'ok' : 'fail', $sent ? 'پیامک آزمایشی به '.fa($test).' ارسال شد؛ گوشی را بررسی کنید.' : (self::$lastError ?: 'ارسال ناموفق بود.')];
            }
        }

        return $steps;
    }
}
