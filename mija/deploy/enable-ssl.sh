#!/usr/bin/env bash
# گواهی SSL رایگان Let's Encrypt برای می‌جا (تمدید خودکار)
# استفاده:  sudo bash enable-ssl.sh mija.click [ایمیل-اختیاری]
set -uo pipefail
DOMAIN="${1:-mija.click}"
EMAIL="${2:-${MIJA_EMAIL:-}}"
[ "$(id -u)" = "0" ] || { echo "با دسترسی root اجرا کنید (sudo)"; exit 1; }

echo "==> نصب certbot"
export DEBIAN_FRONTEND=noninteractive
apt-get install -y certbot >/dev/null 2>&1 || { apt-get update -y >/dev/null 2>&1; apt-get install -y certbot >/dev/null 2>&1; }
command -v certbot >/dev/null || { echo "❌ نصب certbot انجام نشد"; exit 1; }

mkdir -p /var/www/letsencrypt/.well-known/acme-challenge
chmod -R 755 /var/www/letsencrypt
nginx -t >/dev/null 2>&1 && systemctl reload nginx

echo "==> درخواست گواهی برای ${DOMAIN} و www.${DOMAIN}"
MAIL_ARG="--register-unsafely-without-email"
[ -n "$EMAIL" ] && MAIL_ARG="-m $EMAIL"
if certbot certonly --webroot -w /var/www/letsencrypt -d "$DOMAIN" -d "www.$DOMAIN" \
     --non-interactive --agree-tos $MAIL_ARG --keep-until-expiring; then
  LIVE="/etc/letsencrypt/live/${DOMAIN}"
  CONF=/etc/nginx/sites-available/mija
  sed -i "s#ssl_certificate .*#ssl_certificate     ${LIVE}/fullchain.pem;#; s#ssl_certificate_key .*#ssl_certificate_key ${LIVE}/privkey.pem;#" "$CONF"
  mkdir -p /etc/letsencrypt/renewal-hooks/deploy
  printf '#!/bin/sh\nsystemctl reload nginx\n' > /etc/letsencrypt/renewal-hooks/deploy/mija-reload.sh
  chmod +x /etc/letsencrypt/renewal-hooks/deploy/mija-reload.sh
  nginx -t && systemctl reload nginx
  echo
  echo "✅ SSL معتبر فعال شد (تمدید خودکار هر ۶۰ روز)."
  echo "   حالا در Cloudflare: SSL/TLS ← حالت «Full (strict)» را بزنید."
else
  echo
  echo "⚠️ گواهی صادر نشد؛ سایت با گواهی خودامضا (برای حالت Full در Cloudflare) کار می‌کند."
  echo "   معمولاً علت: رکورد DNS دامنه هنوز به این سرور نرسیده یا پورت ۸۰ بسته است."
  echo "   بعد از درست شدن DNS دوباره اجرا کنید:  bash enable-ssl.sh ${DOMAIN}"
  exit 2
fi
