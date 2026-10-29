#!/usr/bin/env bash
# نصب/به‌روزرسانی «می‌جا» روی سرور Ubuntu/Debian خالی (nginx + PHP 8.3 + SQLite) پشت Cloudflare
# استفاده (روی سرور، با root، از داخل پوشه‌ی کلون‌شده‌ی مخزن):
#   MIJA_ADMIN_PHONE=09xxxxxxxxx MIJA_ADMIN_PASSWORD='...' bash mija/deploy/setup-server.sh amirxui.site
set -euo pipefail

DOMAIN="${1:?دامنه را وارد کنید. مثال: bash setup-server.sh amirxui.site}"
APP=/var/www/mija
SRC="$(cd "$(dirname "$0")/.." && pwd)"
[ "$(id -u)" = 0 ] || { echo "با root اجرا کنید (sudo)"; exit 1; }
[ -f "$SRC/artisan" ] || { echo "artisan در $SRC پیدا نشد"; exit 1; }

export DEBIAN_FRONTEND=noninteractive COMPOSER_ALLOW_SUPERUSER=1
echo "==> نصب بسته‌ها"
apt-get update -y
apt-get install -y software-properties-common curl unzip git rsync nginx sqlite3 openssl ca-certificates composer
if ! apt-cache show php8.3-fpm >/dev/null 2>&1; then
  . /etc/os-release
  if [ "$ID" = "ubuntu" ]; then add-apt-repository -y ppa:ondrej/php; else
    curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/trusted.gpg.d/sury.gpg
    echo "deb https://packages.sury.org/php/ $VERSION_CODENAME main" > /etc/apt/sources.list.d/sury.list
  fi
  apt-get update -y
fi
apt-get install -y php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

echo "==> کپی کد به $APP"
mkdir -p "$APP"
rsync -a --delete \
  --exclude '/vendor' --exclude '/.env' --exclude '/database/database.sqlite' \
  --exclude '/storage' --exclude '/node_modules' --exclude '/deploy' \
  "$SRC"/ "$APP"/
mkdir -p "$APP"/storage/{app/public,framework/{cache/data,sessions,views},logs} "$APP"/bootstrap/cache
cd "$APP"

echo "==> نصب وابستگی‌ها"
composer install --no-dev --optimize-autoloader --no-interaction

FIRST=0
if [ ! -f .env ]; then
  FIRST=1
  cp .env.example .env
  sed -i "s|^APP_ENV=.*|APP_ENV=production|; s|^APP_DEBUG=.*|APP_DEBUG=false|; s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
  sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|; s|^# *DB_DATABASE=.*|DB_DATABASE=${APP}/database/database.sqlite|" .env
  grep -q '^DB_DATABASE=' .env || echo "DB_DATABASE=${APP}/database/database.sqlite" >> .env
  touch database/database.sqlite
  php artisan key:generate --force
fi

echo "==> مهاجرت دیتابیس"
php artisan migrate --force
if [ "$FIRST" = 1 ]; then
  php artisan db:seed --force      # در production فقط تنظیمات و امکانات
fi
# حساب مدیر: از متغیرهای MIJA_ADMIN_PHONE و MIJA_ADMIN_PASSWORD، وگرنه رمز تصادفی
if [ -n "${MIJA_ADMIN_PHONE:-}" ] && [ -n "${MIJA_ADMIN_PASSWORD:-}" ]; then
  php artisan mija:admin "$MIJA_ADMIN_PHONE" "$MIJA_ADMIN_PASSWORD"
elif [ "$FIRST" = 1 ]; then
  GEN="$(openssl rand -base64 12 | tr -dc 'A-Za-z0-9' | head -c 12)"
  php artisan mija:admin 09120000000 "$GEN"
  umask 077; printf 'موبایل مدیر: 09120000000\nرمز: %s\n' "$GEN" > /root/mija-admin.txt
fi
rm -f public/storage; php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache database
chmod -R ug+rwX storage bootstrap/cache database

echo "==> cron (تسویه و انقضای رزروها)"
echo "* * * * * www-data cd $APP && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/mija

echo "==> TLS (خودامضا برای حالت Full در Cloudflare) و nginx"
mkdir -p /etc/ssl/mija
[ -f /etc/ssl/mija/origin.crt ] || openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
  -subj "/CN=${DOMAIN}" -keyout /etc/ssl/mija/origin.key -out /etc/ssl/mija/origin.crt 2>/dev/null
cat > /etc/nginx/sites-available/mija <<NGINX
server {
    listen 80 default_server;
    listen 443 ssl default_server;
    server_name ${DOMAIN} www.${DOMAIN} _;
    ssl_certificate     /etc/ssl/mija/origin.crt;
    ssl_certificate_key /etc/ssl/mija/origin.key;
    root ${APP}/public;
    index index.php;
    client_max_body_size 30m;
    charset utf-8;
    gzip on; gzip_types text/css application/javascript application/json image/svg+xml;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~* \.(?:css|js|woff2|svg|png|jpg|jpeg|webp|ico)\$ { expires 7d; access_log off; try_files \$uri /index.php?\$query_string; }
    location = /sw.js { add_header Cache-Control "no-cache"; }
    location ~ /\.(?!well-known) { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/mija /etc/nginx/sites-enabled/mija
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now php8.3-fpm nginx
systemctl reload nginx

echo
echo "✅ نصب شد: https://${DOMAIN}"
[ -f /root/mija-admin.txt ] && echo "   اطلاعات مدیر: cat /root/mija-admin.txt (پس از ورود رمز را عوض کنید)"
echo "   در Cloudflare: SSL/TLS ← حالت Full"
