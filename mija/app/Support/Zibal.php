<?php

namespace App\Support;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

/** درگاه پرداخت زیبال (gateway.zibal.ir). مرچنت‌کدها از تنظیمات مدیر خوانده می‌شوند؛ کد «zibal» حالت آزمایشی است. */
class Zibal
{
    private const BASE = 'https://gateway.zibal.ir';

    /** @return array<int,string> */
    public static function merchants(): array
    {
        return array_values(array_filter(array_map(fn ($l) => trim(explode('|', $l)[0]), preg_split('/\R/u', (string) setting('zibal_merchants')))));
    }

    public static function enabled(): bool
    {
        return (bool) (int) setting('pay_zibal_on') && self::merchants() !== [];
    }

    /** ایجاد تراکنش و بازگرداندن آدرس پرداخت؛ خطا ← استثنا */
    public static function start(Payment $pay, string $callback, ?string $mobile = null): string
    {
        $list = self::merchants();
        $merchant = $list[$pay->id % count($list)];
        $res = Http::timeout(12)->acceptJson()->post(self::BASE.'/v1/request', array_filter([
            'merchant' => $merchant, 'amount' => $pay->amount * 10, 'callbackUrl' => $callback,
            'description' => 'می‌جا — '.($pay->booking?->code ?? 'شارژ کیف پول'), 'orderId' => (string) $pay->id, 'mobile' => $mobile,
        ]))->json();
        if (($res['result'] ?? 0) != 100 || empty($res['trackId'])) {
            throw new \RuntimeException('خطا در اتصال به درگاه: '.($res['message'] ?? 'نامشخص'));
        }
        $pay->update(['merchant' => $merchant, 'track_id' => (string) $res['trackId'], 'authority' => (string) $res['trackId']]);

        return self::BASE.'/start/'.$res['trackId'];
    }

    /** تأیید پرداخت پس از بازگشت از بانک؛ کد مرجع یا null */
    public static function verify(Payment $pay): ?string
    {
        $res = Http::timeout(12)->acceptJson()->post(self::BASE.'/v1/verify', ['merchant' => $pay->merchant, 'trackId' => $pay->track_id])->json();
        if (in_array($res['result'] ?? 0, [100, 201], true) && (int) ($res['amount'] ?? 0) === $pay->amount * 10) {
            return (string) ($res['refNumber'] ?? $pay->track_id);
        }

        return null;
    }
}
