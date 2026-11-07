<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * لینک ورود شخصی برای کاربری که مدیر ساخته است: پیامک می‌رود، کاربر روی لینک می‌زند،
 * بدون وارد کردن رمز وارد پنل می‌شود و ادامه‌ی ثبت‌نام (برای میزبان: تکمیل هویت و ثبت اقامتگاه) را انجام می‌دهد.
 * خودِ توکن در دیتابیس نیست، فقط هش آن؛ لینک ۷ روز اعتبار دارد. برای مدیر سایت لینک ساخته نمی‌شود.
 */
class UserInvite
{
    public const DAYS = 7;

    public static function allowed(User $u): bool
    {
        return $u->role !== 'admin' && $u->is_active !== false;
    }

    /** توکن تازه می‌سازد (قبلی باطل می‌شود) و لینک کامل را برمی‌گرداند: mija.click/j/… */
    public static function issue(User $u): string
    {
        $raw = Str::lower(Str::random(14));
        $u->forceFill(['invite_hash' => hash('sha256', $raw), 'invite_expires_at' => now()->addDays(self::DAYS), 'invite_used_at' => null])->save();

        return url('/j/'.$raw);
    }

    public static function find(string $raw): ?User
    {
        if (! preg_match('/^[a-z0-9]{14}$/', $raw)) {
            return null;
        }
        $u = User::where('invite_hash', hash('sha256', $raw))->where('invite_expires_at', '>', now())->first();

        return $u && self::allowed($u) ? $u : null;
    }

    public static function message(User $u, string $link): string
    {
        $who = ['host' => 'میزبانان', 'guest' => 'مهمانان'][$u->role] ?? 'کاربران';
        $last = $u->role === 'host' ? 'لطفا مراحل ثبت اقامتگاهتان را در پنل جلو ببرید.' : 'لطفا مراحل تکمیل حساب خود را در پنل ادامه دهید.';

        return "سلام جناب/آقای {$u->name}\n"
            ."از پشتیبانی {$who} پلتفرم می‌جا خدمتتون پیام دادم.\n"
            ."وارد لینک زیر شوید؛ شماره موبایل شما ({$u->phone}) به‌عنوان نام کاربری و گذرواژه‌ی شما ثبت گردید.\n"
            ."{$last}\n"
            ."لینک ورود: {$link}";
    }

    /** @return array{0:bool,1:string,2:?string} [ارسال موفق؟، لینک، پیام خطا] */
    public static function send(User $u): array
    {
        if (! self::allowed($u)) {
            return [false, '', 'برای مدیر سایت لینک ورود ساخته نمی‌شود.'];
        }
        $link = self::issue($u);
        $ok = Sms::raw($u->phone, self::message($u, $link), 0, 'invite');
        if ($ok) {
            $u->forceFill(['invite_sent_at' => now()])->save();
        }

        return [$ok, $link, $ok ? null : (Sms::$lastError ?: 'پیامک ارسال نشد')];
    }
}
