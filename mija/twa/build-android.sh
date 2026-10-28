#!/usr/bin/env bash
# ساخت APK/AAB اندروید (TWA) برای انتشار در کافه‌بازار، مایکت، گوگل‌پلی و ...
# استفاده:  ./twa/build-android.sh your-domain.ir
set -euo pipefail
DOMAIN="${1:?دامنه را وارد کنید، مثال: ./build-android.sh mija.ir}"
cd "$(dirname "$0")"
sed -i "s/REPLACE_WITH_YOUR_DOMAIN/${DOMAIN}/g" twa-manifest.json
npx --yes @bubblewrap/cli init --manifest="https://${DOMAIN}/manifest.webmanifest" || true
npx --yes @bubblewrap/cli build        # خروجی: app-release-signed.apk و app-release-bundle.aab
echo "SHA-256 کلید امضا را در public/.well-known/assetlinks.json قرار دهید:"
echo "  keytool -list -v -keystore mija-release.keystore -alias mija | grep SHA256"
