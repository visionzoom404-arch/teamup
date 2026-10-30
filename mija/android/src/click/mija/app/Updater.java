package click.mija.app;

import android.app.Dialog;
import android.content.ActivityNotFoundException;
import android.content.Context;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.content.pm.Signature;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageInstaller;
import android.graphics.Color;
import android.graphics.drawable.ColorDrawable;
import android.net.Uri;
import android.os.Build;
import android.provider.Settings;
import android.view.Gravity;
import android.view.KeyEvent;
import android.view.View;
import android.view.Window;
import android.view.WindowManager;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;
import android.app.PendingIntent;
import android.content.res.ColorStateList;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.security.MessageDigest;
import java.util.ArrayList;
import java.util.List;

/**
 * به‌روزرسانی هوشمند:
 *  - منبع نصب را تشخیص می‌دهد: بازار → صفحه‌ی اپ در بازار، مایکت → صفحه‌ی اپ در مایکت،
 *  - نسخه‌ی مستقیم (از سایت) → دانلود درون‌برنامه‌ای + بررسی SHA-256 + نصب با PackageInstaller.
 */
final class Updater {
    private static final long CHECK_EVERY = 6L * 3600 * 1000;
    private static final long SKIP_FOR = 24L * 3600 * 1000;
    static final String ACTION = "click.mija.app.INSTALL_RESULT";

    private static Updater current;

    static void installFailed() {
        final Updater u = current;
        if (u != null) {
            u.act.runOnUiThread(new Runnable() {
                @Override public void run() { u.showOffer(); }
            });
        }
    }

    static class Info {
        int code;
        String name = "";
        boolean force, available;
        List<String> notes = new ArrayList<String>();
        String apkUrl = "";
        long apkSize;
        String sha = "";
        String bazaar = "", myket = "";
    }

    private final MainActivity act;
    private final SharedPreferences prefs;
    private Info info;
    private Dialog dlg;
    private volatile boolean downloading;

    Updater(MainActivity a) {
        act = a;
        prefs = a.getSharedPreferences("mija", 0);
        current = this;
    }

    /** بررسی نسخه‌ی جدید؛ manual=true یعنی کاربر خودش خواسته (پیام «آخرین نسخه» هم نشان داده شود). */
    void check(final boolean manual) {
        final long now = System.currentTimeMillis();
        if (!manual && now - prefs.getLong("lastCheck", 0) < CHECK_EVERY && prefs.getInt("pendingCode", 0) <= Cfg.versionCode(act)) return;
        new Thread(new Runnable() {
            @Override public void run() {
                final Info i = fetch();
                Ui.runUi(act, new Runnable() {
                    @Override public void run() {
                        if (i == null) {
                            if (manual) Toast.makeText(act, "بررسی نسخه ناموفق بود؛ اتصال اینترنت را بررسی کنید.", Toast.LENGTH_SHORT).show();
                            return;
                        }
                        prefs.edit().putLong("lastCheck", now).putInt("pendingCode", i.available ? i.code : 0).apply();
                        info = i;
                        if (!i.available) {
                            if (manual) Toast.makeText(act, "شما آخرین نسخه‌ی می‌جا را دارید ✓", Toast.LENGTH_SHORT).show();
                            return;
                        }
                        if (!manual && !i.force && prefs.getInt("skipCode", 0) == i.code && now - prefs.getLong("skipAt", 0) < SKIP_FOR) return;
                        showOffer();
                    }
                });
            }
        }).start();
    }

    /** برای دکمه‌ی «به‌روزرسانی» در صفحه‌ی وب. */
    void startFromWeb() {
        if (info != null && info.available) { onUpdateClicked(); return; }
        check(true);
    }

    private Info fetch() { return fetchInfo(act); }

    /** دریافت اطلاعات آخرین نسخه از سایت (برای خود اپ و کار پس‌زمینه‌ی اعلان) */
    static Info fetchInfo(Context ctx) {
        HttpURLConnection c = null;
        try {
            String u = Cfg.BASE + "/api/app/version?code=" + Cfg.versionCode(ctx) + "&flavor=" + Cfg.flavor()
                    + "&installer=" + Uri.encode(Cfg.installer(ctx)) + "&sdk=" + Build.VERSION.SDK_INT;
            c = (HttpURLConnection) new URL(u).openConnection();
            c.setConnectTimeout(8000);
            c.setReadTimeout(8000);
            c.setRequestProperty("Accept", "application/json");
            c.setRequestProperty("User-Agent", "MijaApp/" + Cfg.versionName(ctx));
            if (c.getResponseCode() != 200) return null;
            InputStream in = c.getInputStream();
            java.io.ByteArrayOutputStream bo = new java.io.ByteArrayOutputStream();
            byte[] b = new byte[4096];
            int n;
            while ((n = in.read(b)) > 0) bo.write(b, 0, n);
            in.close();
            JSONObject j = new JSONObject(bo.toString("UTF-8"));
            if (!j.optBoolean("ok")) return null;
            JSONObject l = j.getJSONObject("latest");
            Info i = new Info();
            i.code = l.optInt("code");
            i.name = l.optString("name");
            i.force = j.optBoolean("force");
            i.available = j.optBoolean("update_available") && i.code > Cfg.versionCode(ctx);
            JSONArray ch = l.optJSONArray("changelog");
            if (ch != null) for (int k = 0; k < ch.length(); k++) i.notes.add(ch.optString(k));
            JSONObject apk = j.optJSONObject("apk");
            if (apk != null) {
                i.apkUrl = apk.optString("url").replaceFirst("^http://", "https://");
                i.apkSize = apk.optLong("size");
                i.sha = apk.optString("sha256");
            }
            JSONObject st = j.optJSONObject("stores");
            if (st != null) {
                i.bazaar = st.optString("bazaar");
                i.myket = st.optString("myket");
            }
            return i;
        } catch (Exception e) {
            return null;
        } finally {
            if (c != null) c.disconnect();
        }
    }

    // ───────────── رابط ─────────────

    private Dialog newDialog(LinearLayout body, boolean cancelable) {
        if (dlg != null) { try { dlg.dismiss(); } catch (Exception ignored) { } }
        final Dialog d = new Dialog(act);
        d.requestWindowFeature(Window.FEATURE_NO_TITLE);
        d.setContentView(body);
        d.setCancelable(cancelable);
        if (!cancelable) {
            d.setOnKeyListener(new Dialog.OnKeyListener() {
                @Override public boolean onKey(android.content.DialogInterface di, int k, KeyEvent e) {
                    if (k == KeyEvent.KEYCODE_BACK && e.getAction() == KeyEvent.ACTION_UP) act.finish();
                    return true;
                }
            });
        }
        Window w = d.getWindow();
        if (w != null) {
            w.setBackgroundDrawable(new ColorDrawable(Color.TRANSPARENT));
            w.setDimAmount(0.45f);
            int width = Math.min(Ui.dp(act, 380), (int) (act.getResources().getDisplayMetrics().widthPixels * 0.9f));
            w.setLayout(width, WindowManager.LayoutParams.WRAP_CONTENT);
        }
        dlg = d;
        return d;
    }

    private LinearLayout card() {
        LinearLayout l = new LinearLayout(act);
        l.setOrientation(LinearLayout.VERTICAL);
        l.setLayoutDirection(View.LAYOUT_DIRECTION_RTL);
        l.setBackground(Ui.round(Color.WHITE, 26, act));
        int p = Ui.dp(act, 22);
        l.setPadding(p, p, p, p);
        return l;
    }

    private LinearLayout header(String title, String sub) {
        LinearLayout row = new LinearLayout(act);
        row.setOrientation(LinearLayout.HORIZONTAL);
        row.setGravity(Gravity.CENTER_VERTICAL);
        ImageView logo = new ImageView(act);
        logo.setImageResource(R.drawable.logo_mark);
        logo.setScaleType(ImageView.ScaleType.FIT_CENTER);
        row.addView(logo, Ui.lp(Ui.dp(act, 44), Ui.dp(act, 44)));
        LinearLayout col = new LinearLayout(act);
        col.setOrientation(LinearLayout.VERTICAL);
        LinearLayout.LayoutParams cp = new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f);
        cp.setMarginStart(Ui.dp(act, 12));
        col.addView(Ui.text(act, title, 17, Ui.INK, true));
        if (sub != null) col.addView(Ui.text(act, sub, 12.5f, Ui.MUTED, false));
        row.addView(col, cp);
        return row;
    }

    private void showOffer() {
        if (info == null) return;
        final Info i = info;
        LinearLayout c = card();
        c.addView(header(i.force ? "به‌روزرسانی ضروری" : "نسخه‌ی جدید می‌جا آماده است",
                "نسخه‌ی " + Ui.fa(i.name) + (i.apkSize > 0 && !Cfg.STORE ? " · " + Ui.fileSize(i.apkSize) : "")));
        if (!i.notes.isEmpty()) {
            LinearLayout box = new LinearLayout(act);
            box.setOrientation(LinearLayout.VERTICAL);
            box.setBackground(Ui.round(Ui.TINT, 16, act));
            int p = Ui.dp(act, 14);
            box.setPadding(p, Ui.dp(act, 10), p, Ui.dp(act, 10));
            int n = 0;
            for (String s : i.notes) {
                if (n++ >= 6) break;
                TextView t = Ui.text(act, "•  " + s, 13.5f, Ui.INK, false);
                t.setPadding(0, Ui.dp(act, 3), 0, Ui.dp(act, 3));
                box.addView(t);
            }
            c.addView(box, Ui.match(act, 0, 14));
        }
        if (i.force) c.addView(Ui.text(act, "برای ادامه‌ی استفاده، لطفاً برنامه را به‌روز کنید.", 12.5f, Ui.MUTED, false), Ui.match(act, 0, 10));
        Button go = Ui.button(act, "به‌روزرسانی", true);
        go.setOnClickListener(new View.OnClickListener() {
            @Override public void onClick(View v) { onUpdateClicked(); }
        });
        c.addView(go, Ui.match(act, 50, 16));
        if (!i.force) {
            Button later = Ui.button(act, "بعداً", false);
            later.setOnClickListener(new View.OnClickListener() {
                @Override public void onClick(View v) {
                    prefs.edit().putInt("skipCode", i.code).putLong("skipAt", System.currentTimeMillis()).apply();
                    if (dlg != null) dlg.dismiss();
                }
            });
            c.addView(later, Ui.match(act, 46, 8));
        }
        newDialog(c, !i.force).show();
    }

    private void showProgress(String title) {
        LinearLayout c = card();
        c.addView(header(title, "لطفاً برنامه را نبندید"));
        final ProgressBar pb = new ProgressBar(act, null, android.R.attr.progressBarStyleHorizontal);
        pb.setMax(100);
        pb.setProgressTintList(ColorStateList.valueOf(Ui.BRAND));
        pb.setProgressBackgroundTintList(ColorStateList.valueOf(Ui.LINE));
        pb.setIndeterminate(false);
        c.addView(pb, Ui.match(act, 10, 18));
        final TextView pct = Ui.text(act, "۰٪", 13, Ui.MUTED, true);
        pct.setGravity(Gravity.CENTER);
        c.addView(pct, Ui.match(act, 0, 8));
        progressBar = pb;
        progressText = pct;
        newDialog(c, false).show();
    }

    private ProgressBar progressBar;
    private TextView progressText;

    private void setProgress(final int pc, final String label) {
        Ui.runUi(act, new Runnable() {
            @Override public void run() {
                if (progressBar != null) progressBar.setProgress(pc);
                if (progressText != null) progressText.setText(label);
            }
        });
    }

    // ───────────── اقدام ─────────────

    private void onUpdateClicked() {
        String inst = Cfg.installer(act);
        if (Cfg.STORE || Cfg.BAZAAR_PKG.equals(inst) || Cfg.MYKET_PKG.equals(inst)) {
            openStore(inst);
        } else {
            downloadAndInstall();
        }
    }

    private void openStore(String inst) {
        String target = null;
        if (Cfg.BAZAAR_PKG.equals(inst)) target = "bazaar";
        else if (Cfg.MYKET_PKG.equals(inst)) target = "myket";
        else if (Cfg.isInstalled(act, Cfg.BAZAAR_PKG)) target = "bazaar";
        else if (Cfg.isInstalled(act, Cfg.MYKET_PKG)) target = "myket";
        String pkg = act.getPackageName();
        try {
            if ("bazaar".equals(target)) {
                Intent it = new Intent(Intent.ACTION_VIEW, Uri.parse("bazaar://details?id=" + pkg));
                it.setPackage(Cfg.BAZAAR_PKG);
                act.startActivity(it);
                return;
            }
            if ("myket".equals(target)) {
                Intent it = new Intent(Intent.ACTION_VIEW, Uri.parse("myket://details?id=" + pkg));
                it.setPackage(Cfg.MYKET_PKG);
                act.startActivity(it);
                return;
            }
        } catch (ActivityNotFoundException ignored) { }
        String web = info != null && !info.bazaar.isEmpty() ? info.bazaar : (info != null && !info.myket.isEmpty() ? info.myket : Cfg.BASE + "/app/update?src=" + Cfg.flavor());
        try {
            act.startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(web)));
        } catch (Exception e) {
            Toast.makeText(act, "برای به‌روزرسانی، می‌جا را در بازار یا مایکت باز کنید.", Toast.LENGTH_LONG).show();
        }
    }

    private void downloadAndInstall() {
        if (info == null || info.apkUrl.isEmpty()) { openWebFallback(); return; }
        if (Build.VERSION.SDK_INT >= 26 && !canInstall()) {
            askUnknownSources();
            return;
        }
        if (downloading) return;
        downloading = true;
        showProgress("در حال دریافت نسخه‌ی " + Ui.fa(info.name));
        final Info i = info;
        new Thread(new Runnable() {
            @Override public void run() {
                File f = new File(act.getCacheDir(), "mija-update.apk");
                HttpURLConnection c = null;
                try {
                    c = (HttpURLConnection) new URL(i.apkUrl).openConnection();
                    c.setConnectTimeout(15000);
                    c.setReadTimeout(20000);
                    c.setRequestProperty("User-Agent", "MijaApp/" + Cfg.versionName(act));
                    if (c.getResponseCode() != 200) throw new Exception("http " + c.getResponseCode());
                    long total = c.getContentLength() > 0 ? c.getContentLength() : i.apkSize;
                    MessageDigest md = MessageDigest.getInstance("SHA-256");
                    InputStream in = c.getInputStream();
                    OutputStream out = new FileOutputStream(f);
                    byte[] buf = new byte[32768];
                    long got = 0, lastUi = 0;
                    int n;
                    while ((n = in.read(buf)) > 0) {
                        out.write(buf, 0, n);
                        md.update(buf, 0, n);
                        got += n;
                        long now = System.currentTimeMillis();
                        if (total > 0 && now - lastUi > 120) {
                            lastUi = now;
                            int pc = (int) (got * 100 / total);
                            setProgress(pc, Ui.fa(String.valueOf(pc)) + "٪  ·  " + Ui.fileSize(got) + " از " + Ui.fileSize(total));
                        }
                    }
                    out.close();
                    in.close();
                    if (!i.sha.isEmpty()) {
                        StringBuilder hex = new StringBuilder();
                        for (byte b : md.digest()) hex.append(String.format("%02x", b));
                        if (!hex.toString().equalsIgnoreCase(i.sha)) throw new Exception("checksum");
                    }
                    if (!sameSigner(f, i.code)) throw new Exception("signer");
                    setProgress(100, "در حال آماده‌سازی نصب…");
                    install(f);
                } catch (Exception e) {
                    downloading = false;
                    Ui.runUi(act, new Runnable() {
                        @Override public void run() {
                            Toast.makeText(act, "دریافت به‌روزرسانی ناموفق بود. دوباره تلاش کنید.", Toast.LENGTH_LONG).show();
                            showOffer();
                        }
                    });
                } finally {
                    if (c != null) c.disconnect();
                }
            }
        }).start();
    }

    /** امنیت: فقط فایلی نصب می‌شود که بسته‌ی همین اپ باشد، با همان کلید امضا و نسخه‌ی بالاتر. */
    @SuppressWarnings("deprecation")
    private boolean sameSigner(File f, int expectCode) {
        try {
            PackageManager pm = act.getPackageManager();
            PackageInfo arc = pm.getPackageArchiveInfo(f.getAbsolutePath(), PackageManager.GET_SIGNATURES);
            PackageInfo me = pm.getPackageInfo(act.getPackageName(), PackageManager.GET_SIGNATURES);
            if (arc == null || me == null || !act.getPackageName().equals(arc.packageName)) return false;
            if (arc.versionCode <= me.versionCode) return false;
            if (arc.signatures == null || me.signatures == null || arc.signatures.length == 0) return false;
            for (Signature a : arc.signatures) {
                boolean ok = false;
                for (Signature m : me.signatures) if (a.equals(m)) ok = true;
                if (!ok) return false;
            }
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    private void install(File f) throws Exception {
        PackageInstaller pi = act.getPackageManager().getPackageInstaller();
        PackageInstaller.SessionParams sp = new PackageInstaller.SessionParams(PackageInstaller.SessionParams.MODE_FULL_INSTALL);
        sp.setAppPackageName(act.getPackageName());
        int id = pi.createSession(sp);
        PackageInstaller.Session s = pi.openSession(id);
        try {
            InputStream in = new FileInputStream(f);
            OutputStream out = s.openWrite("mija", 0, f.length());
            byte[] buf = new byte[65536];
            int n;
            while ((n = in.read(buf)) > 0) out.write(buf, 0, n);
            s.fsync(out);
            out.close();
            in.close();
            Intent it = new Intent(act, InstallReceiver.class).setAction(ACTION);
            int flags = PendingIntent.FLAG_UPDATE_CURRENT | (Build.VERSION.SDK_INT >= 31 ? 0x02000000 : 0); // FLAG_MUTABLE
            PendingIntent pe = PendingIntent.getBroadcast(act, id, it, flags);
            s.commit(pe.getIntentSender());
            downloading = false;
        } catch (Exception e) {
            s.abandon();
            throw e;
        } finally {
            s.close();
        }
    }

    private boolean canInstall() {
        try {
            return (Boolean) android.content.pm.PackageManager.class.getMethod("canRequestPackageInstalls").invoke(act.getPackageManager());
        } catch (Exception e) {
            return true;
        }
    }

    private void askUnknownSources() {
        LinearLayout c = card();
        c.addView(header("یک اجازه‌ی کوچک لازم است", null));
        c.addView(Ui.text(act, "برای نصب نسخه‌ی جدید، در صفحه‌ای که باز می‌شود گزینه‌ی «اجازه‌ی نصب از این منبع» را روشن کنید و برگردید؛ سپس دوباره «به‌روزرسانی» را بزنید.", 13.5f, Ui.INK, false), Ui.match(act, 0, 12));
        Button ok = Ui.button(act, "باز کردن تنظیمات", true);
        ok.setOnClickListener(new View.OnClickListener() {
            @Override public void onClick(View v) {
                try {
                    act.startActivity(new Intent("android.settings.MANAGE_UNKNOWN_APP_SOURCES", Uri.parse("package:" + act.getPackageName())));
                } catch (Exception e) {
                    act.startActivity(new Intent(Settings.ACTION_SECURITY_SETTINGS));
                }
                if (dlg != null) dlg.dismiss();
            }
        });
        c.addView(ok, Ui.match(act, 50, 16));
        Button no = Ui.button(act, "انصراف", false);
        no.setOnClickListener(new View.OnClickListener() {
            @Override public void onClick(View v) { if (dlg != null) dlg.dismiss(); if (info != null && info.force) showOffer(); }
        });
        c.addView(no, Ui.match(act, 46, 8));
        newDialog(c, false).show();
    }

    private void openWebFallback() {
        try {
            act.startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(Cfg.BASE + "/app/update?src=" + Cfg.flavor())));
        } catch (Exception ignored) { }
    }
}
