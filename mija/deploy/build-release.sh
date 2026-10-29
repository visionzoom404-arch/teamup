#!/usr/bin/env bash
# ساخت بسته‌ی نصبی (tar.gz) شامل vendor آماده — اجرا از داخل پوشه‌ی mija:  bash deploy/build-release.sh
set -euo pipefail
cd "$(dirname "$0")/.."
OUT="${1:-../mija-installer.tar.gz}"
B="$(mktemp -d)/mija-installer"
mkdir -p "$B"
tar -c --exclude='./vendor' --exclude='./.env' --exclude='./node_modules' --exclude='./tests' --exclude='./database/*.sqlite' \
  --exclude='./storage/logs/*' --exclude='./storage/framework/cache/data/*' --exclude='./storage/framework/sessions/*' --exclude='./storage/framework/views/*' \
  --exclude='./storage/app/public/*' --exclude='./public/storage' --exclude='./.phpunit.result.cache' --exclude='./.git' --exclude='./bootstrap/cache/*.php' --exclude='./CLAUDE.md' --exclude='./AGENTS.md' --exclude='./CHANGELOG.md' --exclude='./package.json' --exclude='./vite.config.js' --exclude='./phpunit.xml' . | tar -x -C "$B"
mkdir -p "$B"/storage/app/public "$B"/photos/{villa,cottage,garden_villa,suite,eco,apartment,farm,tiny,all,site}
touch "$B"/storage/app/public/.gitignore
(cd "$B" && composer install --no-dev --no-interaction --no-scripts --prefer-dist 2>&1 | tail -3)
# حذف تاریخچه‌ی گیت و فایل‌های غیرضروری از vendor تا حجم کم شود
find "$B/vendor" -maxdepth 3 \( -name .git -o -name .github -o -name tests -o -name Tests -o -name docs \) -prune -exec rm -rf {} + 2>/dev/null || true
(cd "$B" && composer dump-autoload --no-dev --no-scripts 2>&1 | tail -1)
cp deploy/installer-root.sh "$B"/install.sh && chmod +x "$B"/install.sh
cp deploy/INSTALL-FA.txt "$B"/INSTALL-FA.txt
cp deploy/photos-README.txt "$B"/photos/README.txt
tar -czf "$OUT" -C "$(dirname "$B")" mija-installer
echo "ساخته شد: $OUT ($(du -h "$OUT" | cut -f1))"
