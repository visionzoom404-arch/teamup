<?php

namespace App\Support;

/**
 * خطاهای فنیِ سرور را به زبان ساده‌ی فارسی توضیح می‌دهد: چیست، علتش چیست، چطور رفع می‌شود
 * و (در صورت امکان) دکمه‌ای که مدیر را مستقیم به محل رفع مشکل می‌برد یا رفع خودکار می‌کند.
 */
class ErrorExplainer
{
    /** رفع‌های خودکار مجاز (فقط کارهای بی‌خطر) */
    public const FIXES = [
        'migrate' => ['به‌روزرسانی دیتابیس', 'migrate'],
        'clear' => ['پاک‌کردن حافظه‌ی موقت سایت', 'optimize:clear'],
        'storage_link' => ['اتصال پوشه‌ی عکس‌ها', 'storage:link'],
    ];

    /** @return array{level:string,title:string,what:string,why:string,fix:string,route:?array,action:?string,cmd:?string} */
    public static function explain(string $msg): array
    {
        $m = $msg;
        $l = mb_strtolower($msg);
        $has = fn (string ...$n) => collect($n)->contains(fn ($x) => str_contains($l, mb_strtolower($x)));
        $r = fn (string $level, string $title, string $what, string $why, string $fix, ?array $route = null, ?string $action = null, ?string $cmd = null) => compact('level', 'title', 'what', 'why', 'fix', 'route', 'action', 'cmd');

        if (preg_match('/no such table:?\s*(\w+)|table [\'"`]?[\w.]*?(\w+)[\'"`]? doesn\'t exist|base table or view not found.*?table [\'"`]?[\w.]*?(\w+)/i', $m, $t)) {
            $tb = $t[1] ?: ($t[2] ?: $t[3]);

            return $r('critical', 'جدول «'.$tb.'» در دیتابیس ساخته نشده', 'بخشی از سایت برای ذخیره‌ی اطلاعات به جدولی نیاز دارد که هنوز ساخته نشده است.', 'معمولاً بعد از آپلود نسخه‌ی جدید، به‌روزرسانی دیتابیس انجام نشده است.', 'دکمه‌ی «رفع خودکار» را بزنید؛ دیتابیس به‌روز می‌شود و اطلاعات قبلی حفظ می‌شود.', null, 'migrate');
        }
        if ($has('no such column', 'unknown column', 'has no column named', 'undefined column')) {
            return $r('critical', 'ستونی از دیتابیس وجود ندارد', 'کد جدید سایت اطلاعاتی را می‌خواهد که در دیتابیس قدیمی نیست.', 'نسخه‌ی جدید آپلود شده ولی دیتابیس به‌روز نشده است.', 'دکمه‌ی «رفع خودکار» را بزنید تا دیتابیس به‌روز شود.', null, 'migrate');
        }
        if ($has('database is locked', 'deadlock', 'lock wait timeout')) {
            return $r('warn', 'دیتابیس موقتاً درگیر بود', 'دو عملیات هم‌زمان خواستند روی یک اطلاعات بنویسند.', 'ترافیک هم‌زمان زیاد یا یک عملیات سنگین.', 'معمولاً خودش رفع می‌شود. اگر زیاد تکرار شد، با پشتیبان فنی درباره‌ی انتقال به MySQL مشورت کنید.');
        }
        if ($has('rest.ippanel.com', 'ippanel', 'پنل پیامک')) {
            return $r('warn', 'خطا در ارتباط با پنل پیامک (ippanel)', 'ارسال پیامک انجام نشد.', 'توکن اشتباه، اعتبار تمام‌شده، خط نامعتبر یا قطعی ارتباط سرور با ippanel.', 'به تنظیمات پیامک بروید و «بررسی اتصال» را بزنید؛ مرحله‌ی اشتباه را مشخص می‌کند.', ['admin.settings', ['tab' => 'sms'], 'تنظیمات پیامک']);
        }
        if ($has('bitpay')) {
            return $r('warn', 'خطا در ارتباط با درگاه بیت‌پی', 'پرداخت آنلاین بیت‌پی شروع یا تأیید نشد.', 'کلید API اشتباه، درگاه غیرفعال در پنل بیت‌پی، یا قطعی ارتباط.', 'کلید API بیت‌پی را در تنظیمات درگاه بررسی کنید؛ تا حل نشود می‌توانید این روش را خاموش کنید.', ['admin.settings', ['tab' => 'gateway'], 'تنظیمات درگاه پرداخت']);
        }
        if ($has('zibal')) {
            return $r('warn', 'خطا در ارتباط با درگاه زیبال', 'پرداخت آنلاین زیبال شروع یا تأیید نشد.', 'مرچنت‌کد اشتباه/غیرفعال، یا قطعی ارتباط با زیبال.', 'مرچنت‌کد را در تنظیمات درگاه بررسی کنید؛ تا حل نشود می‌توانید این روش را خاموش کنید.', ['admin.settings', ['tab' => 'gateway'], 'تنظیمات درگاه پرداخت']);
        }
        if ($has('curl error 28', 'operation timed out', 'timed out', 'could not resolve host', 'connection refused', 'curl error 6', 'curl error 7', 'ssl connect', 'connection timed out')) {
            return $r('warn', 'اتصال سرور به یک سرویس بیرونی برقرار نشد', 'سایت نتوانست با سرویسی خارج از سرور (درگاه پرداخت، پنل پیامک، نقشه و…) صحبت کند.', 'قطعی اینترنت سرور، فیلتر/مسدودی آدرس، یا خرابی خودِ آن سرویس.', 'چند دقیقه بعد دوباره امتحان کنید. اگر ادامه داشت، از شرکت سرور بخواهید دسترسی خروجی (outbound) را بررسی کند.');
        }
        if ($has('permission denied', 'could not be opened in append mode', 'failed to open stream: permission', 'unable to create a directory', 'is not writable')) {
            return $r('critical', 'سایت اجازه‌ی نوشتن در پوشه‌ی storage را ندارد', 'سایت نمی‌تواند فایل، عکس یا حافظه‌ی موقت ذخیره کند.', 'مالکیت/دسترسی پوشه‌ها بعد از آپلود دستی تغییر کرده است.', 'این دو دستور را روی سرور اجرا کنید (از گام نصب هم همین کار انجام می‌شود).', null, null, "chown -R www-data:www-data /var/www/mija/storage /var/www/mija/bootstrap/cache\nchmod -R ug+rwX /var/www/mija/storage /var/www/mija/bootstrap/cache");
        }
        if ($has('allowed memory size')) {
            return $r('warn', 'حافظه‌ی PHP برای این کار کافی نبود', 'صفحه یا عملیات سنگین از سقف حافظه‌ی مجاز گذشت.', 'معمولاً آپلود/پردازش عکس‌های بزرگ.', 'عکس‌ها را کوچک‌تر آپلود کنید یا memory_limit را در php.ini به ۲۵۶M برسانید.');
        }
        if ($has('maximum execution time')) {
            return $r('warn', 'اجرای یک عملیات بیش از حد طول کشید', 'درخواست قبل از اتمام، قطع شد.', 'عملیات سنگین یا کندی سرور.', 'دوباره امتحان کنید؛ اگر تکرار شد، max_execution_time را بالاتر ببرید.');
        }
        if ($has('postsizeexceeded', 'post_max_size', 'content too large', 'payload too large')) {
            return $r('info', 'حجم فایل ارسالی بیش از حد مجاز بود', 'کاربر فایل/عکس خیلی بزرگ فرستاد.', 'سقف upload_max_filesize یا post_max_size سرور پایین است.', 'از کاربر بخواهید عکس را کوچک‌تر بفرستد یا این دو مقدار را در php.ini بالا ببرید (مثلاً ۲۰M).');
        }
        if ($has('page expired', 'token mismatch', 'csrf')) {
            return $r('info', 'نشست کاربر منقضی شده بود', 'کاربر مدت زیادی صفحه را باز گذاشت و بعد دکمه‌ای زد.', 'طبیعی است؛ خطای سایت نیست.', 'نیاز به کاری نیست.');
        }
        if ($has('too many attempts', 'throttle')) {
            return $r('info', 'کاربر بیش از حد درخواست فرستاد', 'محافظ سایت چند درخواست پشت‌سرهم را مسدود کرد.', 'تلاش مکرر ورود/ارسال یا ربات.', 'نیاز به کاری نیست؛ این یک محافظت است.');
        }
        if ($has('no application encryption key', 'unsupported cipher', 'app_key')) {
            return $r('critical', 'کلید امنیتی سایت (APP_KEY) تنظیم نشده', 'ورود و نشست کاربران درست کار نمی‌کند.', 'فایل .env ناقص است.', 'این دستور را روی سرور بزنید و بعد «پاک‌کردن حافظه‌ی موقت» را اجرا کنید.', null, 'clear', 'cd /var/www/mija && sudo -u www-data php artisan key:generate --force');
        }
        if ($has('view [', 'route [', 'target class', 'class "', 'class \'', 'does not exist', 'not found')) {
            if ($has('view [') || $has('route [') || $has('target class')) {
                return $r('warn', 'سایت از حافظه‌ی موقتِ قدیمی استفاده می‌کند', 'بخشی از صفحه‌ها یا مسیرها پیدا نمی‌شود.', 'بعد از آپلود نسخه‌ی جدید، حافظه‌ی موقت پاک نشده است.', 'دکمه‌ی «رفع خودکار» را بزنید.', null, 'clear');
            }
            if ($has('class "')) {
                return $r('critical', 'بخشی از فایل‌های سایت وجود ندارد', 'یک کلاس/فایل برنامه پیدا نشد.', 'آپلود ناقص یا قدیمی بودن فایل‌های vendor.', 'بسته‌ی نصب کامل را دوباره آپلود و نصب کنید (اطلاعات حفظ می‌شود).', null, 'clear');
            }
        }
        if ($has('storage/app/public', 'symlink', 'public/storage')) {
            return $r('warn', 'عکس‌ها به سایت متصل نیستند', 'عکس‌های آپلودشده ممکن است نمایش داده نشوند.', 'لینک پوشه‌ی storage ساخته نشده است.', 'دکمه‌ی «رفع خودکار» را بزنید.', null, 'storage_link');
        }
        if ($has('undefined variable', 'undefined array key', 'undefined property', 'call to a member function', 'attempt to read property', 'trying to access array offset', 'must be of type', 'cannot access offset', 'division by zero')) {
            return $r('bug', 'اشکال در برنامه یا داده‌ی ناقص', 'یک صفحه به‌خاطر داده‌ی غیرمنتظره، خطای ۵۰۰ داد.', 'یا اطلاعات ناقصی در دیتابیس هست (مثلاً اقامتگاه بدون عکس)، یا اشکال برنامه‌نویسی است.', 'جزئیات فنی همین کارت را کپی کنید و برای پشتیبان فنی بفرستید تا همان‌جا اصلاح کند.');
        }

        return $r('bug', 'خطای ناشناخته‌ی سرور', 'یک عملیات با خطا روبه‌رو شد.', 'علت دقیق در متن فنی زیر است.', 'متن فنی همین کارت را کپی کنید و برای پشتیبان فنی بفرستید.');
    }

    public const LEVELS = ['critical' => ['بحرانی', 'red'], 'warn' => ['هشدار', 'gold'], 'info' => ['اطلاع', 'blue'], 'bug' => ['فنی', 'gray']];
}
