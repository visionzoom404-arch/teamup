اپ اندروید می‌جا (جاوا، بدون AndroidX، بدون PWA/TWA)
================================================
ساخت (روی اوبونتو):
    sudo apt install aapt apksigner zipalign dalvik-exchange android-sdk-platform-23 default-jdk-headless
    bash android/release.sh 1.1.0 2 "تغییر اول|تغییر دوم"      # خروجی: storage/app/apk/
    # سایت با نصب/به‌روزرسانی (و هر دقیقه) نسخه را خودکار ثبت می‌کند؛ دستی:  php artisan mija:app-sync
    # اختیاری: FORCE=1 (اجباری)، BAZAAR_URL=...، MYKET_URL=... قبل از دستور بالا

دو فایل ساخته می‌شود (با یک کلید امضا و یک نام بسته: click.mija.app):
  • mija-direct-X.apk  ← دانلود از سایت؛ مجوز REQUEST_INSTALL_PACKAGES دارد و خودش را به‌روز می‌کند.
  • mija-store-X.apk   ← برای کافه‌بازار و مایکت؛ بدون آن مجوز (قوانین فروشگاه‌ها)؛ به‌روزرسانی از طریق صفحه‌ی خود فروشگاه.

نکته‌ها:
  • android/keystore/ (mija.jks و pass.txt) هرگز در گیت نرود و حتماً پشتیبان بگیرید. گم شدنش یعنی ناتوانی در به‌روزرسانی اپ.
  • versionCode همیشه باید بزرگ‌تر شود. نام بسته (click.mija.app) بعد از انتشار قابل تغییر نیست.
  • آدرس سایت در android/src/click/mija/app/Cfg.java (BASE/HOST) است.
