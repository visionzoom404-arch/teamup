<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** ورود با کد پیامکی (OTP): ۵ رقم، اعتبار ۵ دقیقه، حداکثر ۵ بار تلاش، محدودیت ارسال */
class LoginCode
{
    public const TTL = 5;
    public const GAP = 60;

    private static function hash(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone.'|'.$code, (string) config('app.key'));
    }

    /** @return array{0:bool,1:string} [موفق؟، پیام فارسی] */
    public static function request(string $phone): array
    {
        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return [false, 'حسابی با این شماره پیدا نشد. اول ثبت‌نام کنید.'];
        }
        if (! $user->is_active) {
            return [false, 'حساب کاربری شما مسدود شده است.'];
        }
        $last = DB::table('login_codes')->where('phone', $phone)->latest('id')->first();
        if ($last && now()->diffInSeconds($last->created_at, true) < self::GAP) {
            $wait = self::GAP - (int) abs(now()->diffInSeconds($last->created_at, true));

            return [false, 'برای دریافت کد جدید '.fa(max(1, $wait)).' ثانیه صبر کنید.'];
        }
        if (DB::table('login_codes')->where('phone', $phone)->where('created_at', '>', now()->subHour())->count() >= 6) {
            return [false, 'تعداد درخواست کد زیاد بود؛ یک ساعت بعد دوباره تلاش کنید یا با رمز عبور وارد شوید.'];
        }
        $code = (string) random_int(10000, 99999);
        $id = DB::table('login_codes')->insertGetId(['phone' => $phone, 'code_hash' => self::hash($phone, $code), 'expires_at' => now()->addMinutes(self::TTL), 'created_at' => now(), 'updated_at' => now()]);
        $ok = Sms::event('login_code', $phone, ['code' => $code], route('login'));
        if (! $ok) {
            DB::table('login_codes')->where('id', $id)->delete();
            // مدیر باید بداند ورود پیامکی کار نمی‌کند (حداکثر هر ۳۰ دقیقه یک بار)
            if (Cache::add('mija.otp.fail.notice', 1, 1800)) {
                User::notifyAdmins('ارسال کد ورود پیامکی ناموفق بود', Sms::$lastError ?: 'تنظیمات پیامک را بررسی کنید', route('admin.settings', ['tab' => 'sms']));
            }

            return [false, 'ارسال پیامک در حال حاضر ممکن نیست. با رمز عبور وارد شوید یا کمی بعد تلاش کنید.'];
        }

        return [true, 'کد ۵ رقمی به '.fa($phone).' پیامک شد.'];
    }

    public static function verify(string $phone, string $code): ?User
    {
        $row = DB::table('login_codes')->where('phone', $phone)->where('expires_at', '>', now())->latest('id')->first();
        if (! $row || $row->attempts >= 5) {
            return null;
        }
        DB::table('login_codes')->where('id', $row->id)->increment('attempts');
        if (! hash_equals($row->code_hash, self::hash($phone, $code))) {
            return null;
        }
        DB::table('login_codes')->where('phone', $phone)->delete();

        return User::where('phone', $phone)->where('is_active', true)->first();
    }
}
