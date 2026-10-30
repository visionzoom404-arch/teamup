<?php

namespace App\Support;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;

/**
 * ارسال پیامک از طریق پنل ippanel (REST).
 * توکن و شماره‌ی ارسال از «پنل مدیریت ← تنظیمات ← پیامک» وارد می‌شود.
 * اگر ارسال خاموش یا توکن خالی باشد، پیام فقط در گزارش پیامک‌ها ثبت می‌شود.
 */
class Sms
{
    public static function e164(string $phone): string
    {
        $p = preg_replace('/\D/', '', $phone);

        return '+98'.ltrim(preg_replace('/^(98|0)/', '', $p), '0');
    }

    public static function send(?string $phone, string $text, ?string $url = null): bool
    {
        if (! $phone || ! preg_match('/^09\d{9}$/', $phone)) {
            return false;
        }
        $msg = $text.($url ? "\n".$url : '')."\nمی‌جا";
        $log = SmsLog::create(['phone' => $phone, 'message' => $msg, 'status' => 'logged']);

        $token = trim((string) setting('sms_token'));
        if (! (int) setting('sms_enabled') || $token === '') {
            return false;
        }
        try {
            $res = Http::withHeaders(['Authorization' => 'AccessKey '.$token])->timeout(8)->acceptJson()->post(
                setting('sms_base_url') ?: 'https://rest.ippanel.com/v1/messages',
                ['originator' => self::originator(), 'recipient' => [self::e164($phone)], 'message' => $msg]
            );
            $log->update(['status' => $res->successful() ? 'sent' : 'failed', 'response' => mb_substr($res->body(), 0, 250)]);

            return $res->successful();
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'response' => mb_substr($e->getMessage(), 0, 250)]);

            return false;
        }
    }

    private static function originator(): string
    {
        return trim((string) setting('sms_originator'));
    }
}
