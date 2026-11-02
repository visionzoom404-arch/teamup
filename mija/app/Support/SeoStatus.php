<?php

namespace App\Support;

use App\Models\Property;
use App\Models\Visit;

/** وضعیت آمادگی سایت برای گوگل سرچ کنسول (برای نمایش در پنل مدیر) */
class SeoStatus
{
    /** @return array<int, array{0:string,1:string,2:string}> [وضعیت ok|warn|fail، عنوان، توضیح] */
    public static function checks(): array
    {
        $c = [];
        $tag = trim((string) setting('google_verify'));
        $file = trim((string) setting('google_verify_file'));
        $c[] = [$tag !== '' || $file !== '' ? 'ok' : 'warn', 'کد تأیید مالکیت گوگل', $tag !== '' || $file !== ''
            ? 'ثبت شده ('.implode(' و ', array_filter([$tag !== '' ? 'متا تگ' : null, $file !== '' ? 'فایل HTML' : null])).'). بعد از آن در سرچ کنسول «Verify» را بزنید.'
            : 'هنوز کدی وارد نشده است. اگر در سرچ کنسول نوع «دامنه» را با DNS (Cloudflare) تأیید می‌کنید، لازم نیست اینجا چیزی وارد کنید.'];
        try {
            $xml = app(\App\Http\Controllers\SeoController::class)->sitemap()->render();
            $n = preg_match_all('~<loc>~', $xml);
            $c[] = ['ok', 'نقشه‌ی سایت (sitemap.xml)', fa(number_format($n)).' آدرس در نقشه‌ی سایت هست. آدرس برای ثبت در گوگل: '.url('/sitemap.xml')];
        } catch (\Throwable $e) {
            $c[] = ['fail', 'نقشه‌ی سایت (sitemap.xml)', 'تولید نقشه‌ی سایت خطا داد: '.$e->getMessage()];
        }
        $c[] = ['ok', 'robots.txt', 'گوگل اجازه‌ی خزیدن صفحه‌های عمومی را دارد و پنل‌ها (ادمین/میزبان/مهمان) بسته‌اند. آدرس نقشه‌ی سایت داخلش نوشته شده.'];
        $host = request()->getHost();
        $c[] = [str_contains($host, 'localhost') || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host) ? 'warn' : 'ok', 'آدرس اصلی سایت', 'سایت با آدرس '.url('/').' شناخته می‌شود؛ نسخه‌ی www و http به آدرس اصلی هدایت می‌شود (canonical).'];
        $c[] = [Property::approved()->exists() ? 'ok' : 'warn', 'صفحه‌های قابل ایندکس', fa(Property::approved()->count()).' اقامتگاه منتشرشده هر کدام صفحه‌ی جدا با عنوان، توضیح و داده‌ی ساختاریافته دارند.'];
        try {
            $g = Visit::where('is_bot', true)->where('bot_name', 'Googlebot');
            $last = (clone $g)->max('created_at');
            $c[] = [$last ? 'ok' : 'warn', 'بازدید ربات گوگل', $last ? 'Googlebot '.fa((clone $g)->where('day', '>=', today()->subDays(29)->toDateString())->count()).' بار در ۳۰ روز اخیر آمده؛ آخرین بار '.\Illuminate\Support\Carbon::parse($last)->diffForHumans().'.' : 'هنوز ربات گوگل ثبت نشده. بعد از تأیید در سرچ کنسول و ثبت sitemap، معمولاً ظرف چند روز می‌آید.'];
        } catch (\Throwable) {
        }
        $c[] = [setting('ga4_id') ? 'ok' : 'warn', 'گوگل آنالیتیکس (اختیاری)', setting('ga4_id') ? 'شناسه '.setting('ga4_id').' فعال است.' : 'شناسه‌ی G-XXXX وارد نشده است. آمار داخلی سایت (منوی «آمار بازدید») بدون آن هم کار می‌کند.'];

        return $c;
    }

    /** اگر مدیر کل تگ meta را چسباند، فقط مقدار content را بردارد */
    public static function cleanToken(?string $v): string
    {
        $v = trim((string) $v);
        if (preg_match('~content\s*=\s*["\']([^"\']+)["\']~i', $v, $m)) {
            return trim($m[1]);
        }

        return $v;
    }
}
