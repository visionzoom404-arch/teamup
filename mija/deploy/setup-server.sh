#!/usr/bin/env bash
# نصب/به‌روزرسانی «می‌جا» روی سرور Ubuntu/Debian خالی (nginx + PHP 8.3 + SQLite) پشت Cloudflare
# استفاده (روی سرور، با root، از داخل پوشه‌ی کلون‌شده‌ی مخزن):
#   MIJA_ADMIN_PHONE=09xxxxxxxxx MIJA_ADMIN_PASSWORD='...' [MIJA_DEMO=1] bash mija/deploy/setup-server.sh mija.click
#   MIJA_DEMO=1  → اقامتگاه‌های نمونه هم ساخته شود (برای دیدن سایت پر)
set -euo pipefail

DOMAIN="${1:-mija.click}"
APP=/var/www/mija
SRC="$(cd "$(dirname "$0")/.." && pwd)"
[ "$(id -u)" = 0 ] || { echo "با root اجرا کنید (sudo)"; exit 1; }
[ -f "$SRC/artisan" ] || { echo "artisan در $SRC پیدا نشد"; exit 1; }

export DEBIAN_FRONTEND=noninteractive COMPOSER_ALLOW_SUPERUSER=1
echo "==> نصب بسته‌ها"
# اگر در تلاش قبلی منبع PPA ناسازگار اضافه شده، پاکش کن
rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-*.list /etc/apt/sources.list.d/ondrej-ubuntu-php-*.sources /etc/apt/sources.list.d/ondrej-*.list /etc/apt/sources.list.d/ondrej-*.sources 2>/dev/null || true
apt-get update -y
HAS_VENDOR=0; [ -f "$SRC/vendor/autoload.php" ] && HAS_VENDOR=1
apt-get install -y software-properties-common curl unzip git rsync nginx sqlite3 openssl ca-certificates
[ "$HAS_VENDOR" = 0 ] && apt-get install -y composer

# پیدا کردن نسخه‌ی PHP (لازم: 8.3 یا بالاتر) — اول از مخزن خود سیستم
find_php() { for v in 8.4 8.3 8.5; do apt-cache show "php$v-fpm" >/dev/null 2>&1 && { echo "$v"; return; }; done; }
PHPV="$(find_php || true)"
if [ -z "$PHPV" ]; then
  . /etc/os-release
  echo "PHP در مخزن پیش‌فرض نبود؛ تلاش با مخزن خارجی…"
  if [ "$ID" = "ubuntu" ]; then add-apt-repository -y ppa:ondrej/php || true; else
    curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/trusted.gpg.d/sury.gpg
    echo "deb https://packages.sury.org/php/ $VERSION_CODENAME main" > /etc/apt/sources.list.d/sury.list
  fi
  apt-get update -y || true
  PHPV="$(find_php || true)"
fi
[ -n "$PHPV" ] || { echo "❌ نسخه‌ی مناسب PHP پیدا نشد. خروجی همین پیام را برای پشتیبانی بفرستید."; exit 1; }
echo "نسخه‌ی PHP: $PHPV"
apt-get install -y "php$PHPV-fpm" "php$PHPV-cli" "php$PHPV-sqlite3" "php$PHPV-mbstring" "php$PHPV-xml" "php$PHPV-curl" "php$PHPV-zip" "php$PHPV-gd" "php$PHPV-intl" "php$PHPV-bcmath"
PHP="$(command -v php$PHPV)"

echo "==> کپی کد به $APP"
mkdir -p "$APP"
EXC=(--exclude '/.env' --exclude '/database/database.sqlite' --exclude '/storage' --exclude '/node_modules' --exclude '/deploy' --exclude '/photos' --exclude '/INSTALL-FA.txt')
[ "$HAS_VENDOR" = 0 ] && EXC+=(--exclude '/vendor')
rsync -a --delete "${EXC[@]}" "$SRC"/ "$APP"/
mkdir -p "$APP"/storage/{app/public,framework/{cache/data,sessions,views},logs} "$APP"/bootstrap/cache
cd "$APP"

if [ "$HAS_VENDOR" = 0 ]; then
  echo "==> نصب وابستگی‌ها"
  composer install --no-dev --optimize-autoloader --no-interaction
fi

FIRST=0
if [ ! -f .env ]; then
  FIRST=1
  cp .env.example .env
  sed -i "s|^APP_ENV=.*|APP_ENV=production|; s|^APP_DEBUG=.*|APP_DEBUG=false|; s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
  sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|; s|^# *DB_DATABASE=.*|DB_DATABASE=${APP}/database/database.sqlite|" .env
  grep -q '^DB_DATABASE=' .env || echo "DB_DATABASE=${APP}/database/database.sqlite" >> .env
  touch database/database.sqlite
  $PHP artisan key:generate --force
fi

echo "==> مهاجرت دیتابیس"
$PHP artisan migrate --force
if [ "$FIRST" = 1 ]; then
  $PHP artisan db:seed --force      # در production فقط تنظیمات و امکانات
fi
# حساب مدیر: از متغیرهای MIJA_ADMIN_PHONE و MIJA_ADMIN_PASSWORD، وگرنه رمز تصادفی
if [ -n "${MIJA_ADMIN_PHONE:-}" ] && [ -n "${MIJA_ADMIN_PASSWORD:-}" ]; then
  $PHP artisan mija:admin "$MIJA_ADMIN_PHONE" "$MIJA_ADMIN_PASSWORD"
elif [ "$FIRST" = 1 ]; then
  GEN="$(openssl rand -base64 12 | tr -dc 'A-Za-z0-9' | head -c 12)"
  $PHP artisan mija:admin 09120000000 "$GEN"
  umask 077; printf 'موبایل مدیر: 09120000000\nرمز: %s\n' "$GEN" > /root/mija-admin.txt
fi
rm -f public/storage; $PHP artisan storage:link
# عکس‌های واقعی: اگر پوشه‌ی photos کنار نصب‌کننده تصویر داشته باشد، وارد سایت می‌شود
if [ -d "$SRC/photos" ] && find "$SRC/photos" -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' -o -iname '*.webp' \) | grep -q .; then
  echo "==> وارد کردن عکس‌ها"
  $PHP artisan mija:photos --dir="$SRC/photos" --per="${MIJA_PHOTOS_PER:-5}"
fi
$PHP artisan config:cache && $PHP artisan route:cache && $PHP artisan view:cache

chown -R www-data:www-data storage bootstrap/cache database
chmod -R ug+rwX storage bootstrap/cache database

echo "==> cron (تسویه و انقضای رزروها)"
echo "* * * * * www-data cd $APP && $PHP artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/mija

echo "==> TLS (خودامضا برای حالت Full در Cloudflare) و nginx"
mkdir -p /etc/ssl/mija
[ -f /etc/ssl/mija/origin.crt ] || openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
  -subj "/CN=${DOMAIN}" -keyout /etc/ssl/mija/origin.key -out /etc/ssl/mija/origin.crt 2>/dev/null
cat > /etc/nginx/sites-available/mija <<NGINX
# پورت ۸۰: فقط تأیید دامنه‌ی Let's Encrypt و ریدایرکت به https
server {
    listen 80 default_server;
    server_name ${DOMAIN} www.${DOMAIN} _;
    location ^~ /.well-known/acme-challenge/ { root /var/www/letsencrypt; default_type text/plain; }
    location / { return 301 https://${DOMAIN}\$request_uri; }
}
# پورت ۴۴۳: خود سایت
server {
    listen 443 ssl default_server;
    server_name ${DOMAIN} www.${DOMAIN} _;
    ssl_certificate     /etc/ssl/mija/origin.crt;
    ssl_certificate_key /etc/ssl/mija/origin.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    add_header Strict-Transport-Security "max-age=31536000" always;
    root ${APP}/public;
    index index.php;
    client_max_body_size 30m;
    charset utf-8;
    gzip on; gzip_types text/css application/javascript application/json image/svg+xml;

    location ^~ /.well-known/acme-challenge/ { root /var/www/letsencrypt; default_type text/plain; }
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHPV}-fpm.sock;
    }
    location ~* \.(?:css|js|woff2|svg|png|jpg|jpeg|webp|ico)\$ { expires 7d; access_log off; try_files \$uri /index.php?\$query_string; }
    location = /sw.js { add_header Cache-Control "no-cache"; }
    location ~ /\.(?!well-known) { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/mija /etc/nginx/sites-enabled/mija
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now php${PHPV}-fpm nginx
systemctl reload nginx

echo "==> گواهی SSL معتبر (Let's Encrypt)"
mkdir -p /var/www/letsencrypt
bash "$SRC/deploy/enable-ssl.sh" "$DOMAIN" "${MIJA_EMAIL:-}" || true

echo
echo "✅ نصب شد: https://${DOMAIN}"
[ -f /root/mija-admin.txt ] && echo "   اطلاعات مدیر: cat /root/mija-admin.txt (پس از ورود رمز را عوض کنید)"
echo "   در Cloudflare: SSL/TLS ← حالت Full (یا Full strict اگر گواهی معتبر صادر شد)"
