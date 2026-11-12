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

LIVE="/etc/letsencrypt/live/${DOMAIN}"
CONF=/etc/nginx/sites-available/mija

apply_cert() {
  sed -i "s#ssl_certificate .*#ssl_certificate     ${LIVE}/fullchain.pem;#; s#ssl_certificate_key .*#ssl_certificate_key ${LIVE}/privkey.pem;#" "$CONF"
  mkdir -p /etc/letsencrypt/renewal-hooks/deploy
  printf '#!/bin/sh\nsystemctl reload nginx\n' > /etc/letsencrypt/renewal-hooks/deploy/mija-reload.sh
  chmod +x /etc/letsencrypt/renewal-hooks/deploy/mija-reload.sh
  nginx -t && systemctl reload nginx
}
ok_msg() {
  echo
  echo "✅ SSL معتبر فعال شد (تمدید خودکار هر ۶۰ روز)."
  echo "   حالا در Cloudflare: SSL/TLS ← حالت «Full (strict)» را بزنید."
}

# گواهی معتبرِ موجود را بدون تماس با certbot فوراً به nginx بده (سایت هیچ‌وقت با گواهی خودامضا نماند)
if [ -s "${LIVE}/fullchain.pem" ] && openssl x509 -checkend 86400 -noout -in "${LIVE}/fullchain.pem" >/dev/null 2>&1; then
  echo "==> گواهی معتبر از قبل وجود دارد؛ فعال‌سازی روی nginx"
  apply_cert && { ok_msg; exit 0; }
fi

echo "==> درخواست گواهی برای ${DOMAIN} و www.${DOMAIN}"
MAIL_ARG="--register-unsafely-without-email"
[ -n "$EMAIL" ] && MAIL_ARG="-m $EMAIL"
for try in 1 2 3 4; do
  # اگر certbot دیگری (مثلاً تمدید خودکار) در حال اجراست، تا ۲ دقیقه صبر کن
  for w in $(seq 1 24); do pgrep -x certbot >/dev/null 2>&1 || break; echo "   منتظر پایان certbot دیگر… ($w)"; sleep 5; done
  if certbot certonly --webroot -w /var/www/letsencrypt -d "$DOMAIN" -d "www.$DOMAIN" \
       --non-interactive --agree-tos $MAIL_ARG --keep-until-expiring; then
    apply_cert && { ok_msg; exit 0; }
  fi
  # اگر گواهی در همین حین صادر شده بود، همان را فعال کن
  if [ -s "${LIVE}/fullchain.pem" ] && openssl x509 -checkend 86400 -noout -in "${LIVE}/fullchain.pem" >/dev/null 2>&1; then
    apply_cert && { ok_msg; exit 0; }
  fi
  echo "   تلاش ${try} ناموفق بود؛ ۲۰ ثانیه بعد دوباره…"; sleep 20
done
echo
echo "⚠️ گواهی صادر نشد؛ سایت با گواهی خودامضا (برای حالت Full در Cloudflare) کار می‌کند."
echo "   معمولاً علت: رکورد DNS دامنه هنوز به این سرور نرسیده یا پورت ۸۰ بسته است."
echo "   بعد از درست شدن DNS دوباره اجرا کنید:  bash deploy/enable-ssl.sh ${DOMAIN}"
exit 2
