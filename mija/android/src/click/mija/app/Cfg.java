package click.mija.app;

import android.content.Context;
import android.content.pm.PackageInfo;

/** تنظیمات ثابت اپ. مقدار STORE هنگام ساخت (build.sh) تعیین می‌شود: store = نسخه‌ی بازار/مایکت، direct = نسخه‌ی دانلود از سایت. */
final class Cfg {
    static final String BASE = "https://mija.click";
    static final String HOST = "mija.click";
    static final boolean STORE = @STORE@;

    static final String BAZAAR_PKG = "com.farsitel.bazaar";
    static final String MYKET_PKG = "ir.mservices.market";

    private static int code = -1;
    private static String name = "";

    private static void load(Context c) {
        if (code >= 0) return;
        try {
            PackageInfo p = c.getPackageManager().getPackageInfo(c.getPackageName(), 0);
            code = p.versionCode;
            name = p.versionName == null ? "" : p.versionName;
        } catch (Exception e) {
            code = 0;
            name = "";
        }
    }

    static int versionCode(Context c) { load(c); return code; }
    static String versionName(Context c) { load(c); return name; }
    static String flavor() { return STORE ? "store" : "direct"; }

    static String installer(Context c) {
        try {
            String i = c.getPackageManager().getInstallerPackageName(c.getPackageName());
            return i == null ? "" : i;
        } catch (Exception e) {
            return "";
        }
    }

    static boolean isInstalled(Context c, String pkg) {
        try {
            c.getPackageManager().getPackageInfo(pkg, 0);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    private Cfg() {}
}
