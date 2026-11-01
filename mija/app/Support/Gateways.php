<?php

namespace App\Support;

/** روش‌های پرداخت و روشن/خاموش کردن هر کدام توسط مدیر */
class Gateways
{
    /** کلید ← [عنوان، توضیح، کلید تنظیم روشن/خاموش] */
    public const METHODS = [
        'zibal' => ['زیبال', 'درگاه بانکی زیبال (نیازمند مرچنت‌کد)', 'pay_zibal_on'],
        'bitpay' => ['بیت‌پی', 'درگاه بانکی بیت‌پی (نیازمند کلید API)', 'pay_bitpay_on'],
        'card' => ['کارت‌به‌کارت', 'واریز دستی؛ پس از تأیید مدیر رزرو قطعی می‌شود', 'card_to_card_enabled'],
        'wallet' => ['کیف پول', 'پرداخت از موجودی کیف پول مهمان', 'pay_wallet_on'],
        'sandbox' => ['درگاه آزمایشی', 'فقط برای تست — هر پرداختی موفق می‌شود (در سایت واقعی خاموش باشد)', 'pay_sandbox_on'],
    ];

    /** روشن و کامل تنظیم‌شده بودن */
    public static function on(string $k): bool
    {
        return match ($k) {
            'zibal' => Zibal::enabled(),
            'bitpay' => BitPay::enabled(),
            'card' => Payments::cardEnabled(),
            'wallet' => (bool) (int) setting('pay_wallet_on'),
            'sandbox' => (bool) (int) setting('pay_sandbox_on'),
            default => false,
        };
    }

    /** درگاه‌های آنلاین فعال به ترتیب نمایش */
    public static function online(): array
    {
        return array_values(array_filter(['zibal', 'bitpay', 'sandbox'], fn ($k) => self::on($k)));
    }

    /** وضعیت کامل برای پنل مدیر: [روشن (کلید)، آماده (کامل)، دلیل ناقص‌بودن] */
    public static function status(string $k): array
    {
        $flag = (bool) (int) setting(self::METHODS[$k][2]);
        [$ready, $why] = match ($k) {
            'zibal' => [Zibal::merchants() !== [], 'مرچنت‌کد وارد نشده'],
            'bitpay' => [BitPay::test() || BitPay::keys() !== [], 'کلید API وارد نشده'],
            'card' => [Payments::cards() !== [], 'شماره کارتی وارد نشده'],
            default => [true, ''],
        };

        return ['flag' => $flag, 'ready' => $ready, 'why' => $ready ? '' : $why, 'live' => $flag && $ready];
    }

    public static function label(string $k): string
    {
        return self::METHODS[$k][0] ?? $k;
    }
}
