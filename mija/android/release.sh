#!/usr/bin/env bash
# ساخت نسخه‌ی جدید اپ و قرار دادن در بسته‌ی سایت:  bash android/release.sh 1.1.0 2 "تغییر اول|تغییر دوم"
# خروجی: storage/app/apk/{mija-direct-X.apk, mija-store-X.apk, release.json}  → سپس  php artisan mija:app-sync
set -euo pipefail
cd "$(dirname "$0")/.."
V="${1:?versionName}"; C="${2:?versionCode}"; NOTES="${3:-}"
bash android/build.sh "$V" "$C" android/out
mkdir -p storage/app/apk
cp android/out/mija-direct-$V.apk android/out/mija-store-$V.apk storage/app/apk/
python3 - "$V" "$C" "$NOTES" <<'PY'
import hashlib, json, os, sys
v, c, notes = sys.argv[1], int(sys.argv[2]), sys.argv[3]
d = 'storage/app/apk'
def info(f):
    p = f'{d}/{f}'
    return {'file': f, 'size': os.path.getsize(p), 'sha256': hashlib.sha256(open(p, 'rb').read()).hexdigest()}
json.dump({'version_name': v, 'version_code': c, 'changelog': [n for n in notes.split('|') if n.strip()],
           'direct': info(f'mija-direct-{v}.apk'), 'store': info(f'mija-store-{v}.apk')},
          open(f'{d}/release.json', 'w'), ensure_ascii=False, indent=1)
PY
echo "آماده شد. حالا: php artisan mija:app-sync"
