<?php

namespace App\Support;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

/**
 * درگاه پرداخت بیت‌پی (bitpay.ir) — طبق مستندات رسمی:
 *  ۱) ارسال:  POST /payment/gateway-send  (api, amount[ریال], redirect, factorId, name, email, description) ← عدد مثبت = id_get
 *  ۲) انتقال کاربر:  /payment/gateway-{id_get}-get
 *  ۳) بازگشت: POST به redirect با trans_id و id_get
 *  ۴) تأیید:  POST /payment/gateway-result-second  (api, id_get, trans_id) ← 1 = موفق
 * کلید API (چند کلید، هر خط یکی) توسط مدیر در تنظیمات وارد می‌شود. حالت آزمایشی از آدرس payment-test استفاده می‌کند.
 */
class BitPay
{
    private const TEST_API = 'adxcv-zzadq-polkjsad-opp13opoz-1sdf455aadzmck1244567';

    /** @return array<int,string> */
    public static function keys(): array
    {
        return array_values(array_filter(array_map(fn ($l) => trim(explode('|', $l)[0]), preg_split('/\R/u', (string) setting('bitpay_api')))));
    }

    public static function test(): bool
    {
        return (bool) (int) setting('bitpay_test');
    }

    public static function enabled(): bool
    {
        return (bool) (int) setting('pay_bitpay_on') && (self::test() || self::keys() !== []);
    }

    private static function base(): string
    {
        return self::test() ? 'https://bitpay.ir/payment-test' : 'https://bitpay.ir/payment';
    }

    public static function explainSend(int $code): string
    {
        return match ($code) {
            -1 => 'کلید API بیت‌پی اشتباه است (با نوع API تعریف‌شده در بیت‌پی سازگار نیست).',
            -2 => 'مبلغ نامعتبر است (باید عدد و حداقل ۱٬۰۰۰ ریال باشد).',
            -3 => 'آدرس بازگشت (redirect) خالی است.',
            -4 => 'درگاهی با این کلید در بیت‌پی پیدا نشد یا هنوز فعال/تأیید نشده است.',
            -5 => 'خطا در اتصال بیت‌پی به درگاه؛ دوباره تلاش کنید.',
            default => 'پاسخ ناشناخته از بیت‌پی (کد '.$code.').',
        };
    }

    public static function explainVerify(int $code): string
    {
        return match ($code) {
            1 => 'موفق', 11 => 'این تراکنش قبلاً تأیید شده است.',
            -1 => 'کلید API هنگام تأیید اشتباه است.', -2 => 'کد تراکنش (trans_id) درست نیست.', -3 => 'شناسه‌ی پرداخت (id_get) درست نیست.',
            -4 => 'چنین تراکنشی ثبت نشده یا موفق نبوده است.',
            default => 'پاسخ ناشناخته از بیت‌پی (کد '.$code.').',
        };
    }

    /** ایجاد تراکنش و بازگرداندن آدرس پرداخت؛ خطا ← استثنا با پیام فارسی */
    public static function start(Payment $pay, string $callback, ?string $name = null): string
    {
        $list = self::test() ? [self::TEST_API] : self::keys();
        $api = $list[$pay->id % max(1, count($list))];
        $body = trim((string) Http::asForm()->timeout(15)->post(self::base().'/gateway-send', array_filter([
            'api' => $api, 'amount' => $pay->amount * 10, 'redirect' => $callback, 'factorId' => (string) $pay->id,
            'name' => $name, 'description' => 'می‌جا — '.($pay->booking?->code ?? 'شارژ کیف پول'),
        ], fn ($v) => $v !== null && $v !== ''))->body());
        $id = (int) $body;
        if ($id <= 0 || ! ctype_digit(ltrim($body, '-')) && ! is_numeric($body)) {
            throw new \RuntimeException(self::explainSend((int) $body));
        }
        $pay->update(['merchant' => $api, 'track_id' => (string) $id, 'authority' => (string) $id, 'gateway' => 'bitpay']);

        return self::base().'/gateway-'.$id.'-get';
    }

    /** تأیید پس از بازگشت از بانک؛ کد پیگیری یا null (پیام خطا در $err) */
    public static function verify(Payment $pay, string $transId, ?string &$err = null): ?string
    {
        $code = (int) trim((string) Http::asForm()->timeout(15)->post(self::base().'/gateway-result-second', [
            'api' => $pay->merchant, 'id_get' => $pay->track_id, 'trans_id' => $transId,
        ])->body());
        if ($code === 1 || $code === 11) {
            return $transId;
        }
        $err = self::explainVerify($code);

        return null;
    }
}
