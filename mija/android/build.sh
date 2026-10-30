#!/usr/bin/env bash
# ساخت APK اپ اندروید می‌جا (بدون Gradle/Android Studio).
# استفاده:  bash android/build.sh <versionName> <versionCode> [خروجی=android/out]
# نیازمندی (اوبونتو):  apt install aapt apksigner zipalign dalvik-exchange android-sdk-platform-23 default-jdk-headless
# کلید امضا:  android/keystore/mija.jks  +  android/keystore/pass.txt  (این دو هرگز در گیت نرود و حتماً پشتیبان بگیرید)
set -euo pipefail
unset JAVA_TOOL_OPTIONS
cd "$(dirname "$0")"
VNAME="${1:?versionName}"; VCODE="${2:?versionCode}"; OUT="${3:-out}"
SDK=/usr/lib/android-sdk; AJAR=$SDK/platforms/android-23/android.jar
KS=keystore/mija.jks; PASS=$(cat keystore/pass.txt)
W=$(mktemp -d); mkdir -p "$OUT"
for FLAVOR in direct store; do
  D="$W/$FLAVOR"; mkdir -p "$D/gen" "$D/classes" "$D/src" "$D/flat"
  cp -r src/. "$D/src/"
  [ "$FLAVOR" = store ] && ST=true || ST=false
  sed -i "s/@STORE@/$ST/" "$D/src/click/mija/app/Cfg.java"
  # مانیفست: نسخه‌ی فروشگاهی مجوز نصب بسته ندارد (قوانین بازار/مایکت) + queries برای تشخیص بازار/مایکت
  if [ "$FLAVOR" = store ]; then sed -e '/DIRECT_ONLY/d' AndroidManifest.xml > "$D/AndroidManifest.xml"; else cp AndroidManifest.xml "$D/AndroidManifest.xml"; fi
  sed -i 's|<application|<queries><package android:name="com.farsitel.bazaar"/><package android:name="ir.mservices.market"/></queries>\n    <application|' "$D/AndroidManifest.xml"
  aapt2 compile --dir res -o "$D/flat"
  aapt2 link -I "$AJAR" --manifest "$D/AndroidManifest.xml" -A assets --min-sdk-version 21 --target-sdk-version 34 \
    --version-code "$VCODE" --version-name "$VNAME" --java "$D/gen" -o "$D/base.apk" "$D"/flat/*.flat
  javac -nowarn -Xlint:-options -source 8 -target 8 -bootclasspath "$AJAR" -d "$D/classes" $(find "$D/src" "$D/gen" -name '*.java')
  dalvik-exchange --dex --min-sdk-version=21 --output="$D/classes.dex" "$D/classes"
  (cd "$D" && zip -q -j base.apk classes.dex)
  zipalign -f -p 4 "$D/base.apk" "$D/aligned.apk"
  APK="$OUT/mija-$FLAVOR-$VNAME.apk"
  apksigner sign --ks "$KS" --ks-pass "pass:$PASS" --ks-key-alias mija --min-sdk-version 21 --out "$APK" "$D/aligned.apk"
  apksigner verify "$APK"
  echo "ساخته شد: $APK ($(du -h "$APK" | cut -f1))  sha256=$(sha256sum "$APK" | cut -d' ' -f1)"
done
rm -rf "$W"
