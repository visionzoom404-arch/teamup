# می‌جا (Mija) — پلتفرم رزرو ویلا و کلبه

پروژه‌ای **کاملاً مستقل** از `teamup` (کد، دیتابیس، برند). این پوشه را می‌توان بدون هیچ وابستگی به بقیه‌ی مخزن به سرور جدید منتقل کرد.

## پشته
Laravel · Blade · CSS اختصاصی (بدون build) · JavaScript خام · SQLite (پیش‌فرض؛ قابل تغییر به MySQL) · PWA + TWA

## اجرا
```bash
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve
```
حساب‌های نمونه (فقط محیط غیر production):

| نقش | موبایل | رمز |
|---|---|---|
| مدیر سایت | 09120000000 | admin1234 |
| میزبان | 09121111111 | host1234 |
| مهمان | 09123333333 | guest1234 |

## سه پنل
- **مهمان** `/panel` — سفرها، پرداخت، لغو با بازگشت وجه، نظر، علاقه‌مندی، کیف پول
- **میزبان** `/host` — اقامتگاه‌ها، تقویم و قیمت روزانه، رزروها (تأیید/رد)، درآمد و برداشت، کد تخفیف، نظرات
- **مدیر** `/admin` — تأیید اقامتگاه‌ها، کاربران، رزروها، پرداخت‌ها، برداشت‌ها، نظرات، امکانات، کمیسیون، ایام پیک

## قوانین رزرو (الگوی جاباما)
- رزرو فوری ← مهلت پرداخت (پیش‌فرض ۶۰ دقیقه)؛ رزرو درخواستی ← میزبان تا ۱۲ ساعت پاسخ می‌دهد، سپس مهمان پرداخت می‌کند
- قیمت: میان‌هفته / آخر هفته (پنجشنبه و جمعه) / ایام پیک / قیمت دلخواه هر روز؛ نفر اضافه، نظافت، تخفیف هفتگی و ماهانه، کد تخفیف
- سیاست لغو: منعطف / متوسط / سخت‌گیرانه؛ لغو توسط میزبان = بازگشت کامل
- وجه نزد پلتفرم می‌ماند و روز ورود، پس از کسر کمیسیون، به کیف پول میزبان می‌رود
- زمان‌بندی: `php artisan schedule:run` را هر دقیقه با cron اجرا کنید (دستور `mija:tick` هر ۵ دقیقه انقضا/تسویه را انجام می‌دهد)

## پرداخت
درگاه آزمایشی (`MIJA_GATEWAY=sandbox`) فعال است. برای انتشار واقعی، `GatewayController` را به زرین‌پال/… وصل کنید و `MIJA_GATEWAY=zarinpal` بگذارید.

## PWA و TWA (کافه‌بازار، مایکت، گوگل‌پلی)
- `public/manifest.webmanifest` و `public/sw.js` — PWA قابل نصب با صفحه‌ی آفلاین
- `twa/` — پیکربندی Bubblewrap؛ پس از خرید دامنه: `./twa/build-android.sh your-domain.ir`
- SHA-256 کلید امضا را در `public/.well-known/assetlinks.json` بگذارید
- هیچ آدرس ثابتی در کد نیست؛ تغییر دامنه فقط `APP_URL` و فایل‌های بالا را عوض می‌کند.

## انتقال به سرور جدید
۱. پوشه‌ی `mija/` را منتقل کنید ۲. `composer install --no-dev` ۳. `.env` (با `APP_ENV=production`، `APP_URL`، دیتابیس) ۴. `php artisan migrate --force` ۵. `storage:link` و cron ۶. دیتابیس و `storage/app/public` را از سرور قدیم کپی کنید.

## استقرار روی سرور (nginx + PHP 8.3 + SQLite، پشت Cloudflare)
روی سرور خالی Ubuntu/Debian با root:
```bash
git clone -b claude/blissful-bardeen-1rjd75 https://github.com/visionzoom404-arch/teamup.git /root/teamup
sudo bash /root/teamup/mija/deploy/setup-server.sh amirxui.site
```
- همین دستور برای به‌روزرسانی هم قابل اجراست (`git pull` و اجرای دوباره؛ دیتابیس و عکس‌ها حفظ می‌شوند).
- اطلاعات ورود مدیر در `/root/mija-admin.txt` ساخته می‌شود.
- در Cloudflare حالت SSL/TLS را روی **Full** بگذارید.
- ⚠️ تا زمانی که درگاه واقعی وصل نشده، `MIJA_GATEWAY=sandbox` هر پرداختی را «موفق» می‌پذیرد؛ سایت را به کاربران واقعی معرفی نکنید.
