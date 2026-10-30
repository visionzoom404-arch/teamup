package click.mija.app;

import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.job.JobInfo;
import android.app.job.JobParameters;
import android.app.job.JobScheduler;
import android.app.job.JobService;
import android.content.ComponentName;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Build;
import android.webkit.CookieManager;
import android.net.Uri;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;

/**
 * اعلان «نسخه‌ی جدید رسید»: هر چند ساعت یک بار (حتی وقتی اپ بسته است) آخرین نسخه را از سایت می‌پرسد؛
 * اگر جدیدتر بود، یک اعلان نشان می‌دهد که با لمس آن، پنجره‌ی به‌روزرسانی باز می‌شود.
 * (بدون سرویس‌های گوگل؛ مناسب بازار و مایکت.)
 */
public class UpdateJob extends JobService {
    static final int JOB_ID = 4101;
    static final String CHANNEL = "updates";
    static final String EXTRA_UPDATE = "click.mija.app.UPDATE";
    static final String EXTRA_URL = "click.mija.app.URL";

    static void schedule(Context c) {
        try {
            JobScheduler js = (JobScheduler) c.getSystemService(Context.JOB_SCHEDULER_SERVICE);
            JobInfo job = new JobInfo.Builder(JOB_ID, new ComponentName(c, UpdateJob.class))
                    .setPeriodic(15L * 60 * 1000)
                    .setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY)
                    .setPersisted(true)
                    .build();
            js.schedule(job);
        } catch (Exception ignored) { }
    }

    @Override public boolean onStartJob(final JobParameters p) {
        // کوکی ورود کاربر (همان نشست وب‌ویو) را در نخ اصلی می‌خوانیم
        String ck = "";
        try { ck = CookieManager.getInstance().getCookie(Cfg.BASE); } catch (Exception ignored) { }
        final String cookie = ck == null ? "" : ck;
        new Thread(new Runnable() {
            @Override public void run() {
                try { pollNotices(UpdateJob.this, cookie); } catch (Exception ignored) { }
                try {
                    SharedPreferences sp = getSharedPreferences("mija", 0);
                    if (System.currentTimeMillis() - sp.getLong("jobUpdateCheck", 0) > 6L * 3600 * 1000) {
                        sp.edit().putLong("jobUpdateCheck", System.currentTimeMillis()).apply();
                        check(UpdateJob.this);
                    }
                } catch (Exception ignored) { }
                jobFinished(p, false);
            }
        }).start();
        return true;
    }

    /** اعلان‌های تازه‌ی حساب کاربر را از سایت می‌گیرد و به‌صورت اعلان گوشی نشان می‌دهد (بدون نیاز به سرویس گوگل) */
    static void pollNotices(Context c, String cookie) throws Exception {
        if (cookie.isEmpty()) return; // کاربر وارد نشده
        SharedPreferences sp = c.getSharedPreferences("mija", 0);
        long last = sp.getLong("noticeLast", -1);
        long uidSaved = sp.getLong("noticeUid", 0);
        HttpURLConnection h = (HttpURLConnection) new URL(Cfg.BASE + "/api/notices?after=" + (last < 0 ? 2147483647L : last)).openConnection();
        h.setConnectTimeout(8000);
        h.setReadTimeout(8000);
        h.setInstanceFollowRedirects(false);
        h.setRequestProperty("Cookie", cookie);
        h.setRequestProperty("Accept", "application/json");
        h.setRequestProperty("User-Agent", "MijaApp/" + Cfg.versionName(c));
        try {
            if (h.getResponseCode() != 200) return; // ۴۰۱/ریدایرکت = وارد نشده
            InputStream in = h.getInputStream();
            ByteArrayOutputStream bo = new ByteArrayOutputStream();
            byte[] b = new byte[4096];
            int n;
            while ((n = in.read(b)) > 0) bo.write(b, 0, n);
            in.close();
            JSONObject j = new JSONObject(bo.toString("UTF-8"));
            if (!j.optBoolean("ok")) return;
            long uid = j.optLong("uid");
            long lastId = j.optLong("last_id");
            // اولین بار یا تغییر کاربر: فقط نقطه‌ی شروع را ثبت کن تا اعلان‌های قدیمی یکباره نیایند
            if (last < 0 || uid != uidSaved) {
                sp.edit().putLong("noticeLast", lastId).putLong("noticeUid", uid).apply();
                if (last >= 0 && uid != uidSaved) return;
                return;
            }
            JSONArray items = j.optJSONArray("items");
            if (items == null) return;
            long maxId = last;
            int shown = 0;
            for (int i = 0; i < items.length(); i++) {
                JSONObject it = items.getJSONObject(i);
                long id = it.optLong("id");
                maxId = Math.max(maxId, id);
                if (shown++ < 5) notifyNotice(c, (int) (id % 1000000) + 100, it.optString("title"), it.optString("body"), it.optString("url"));
            }
            if (items.length() > 5) notifyNotice(c, 99, "اعلان‌های تازه در می‌جا", (items.length() - 5) + " اعلان دیگر هم دارید", Cfg.BASE + "/notices");
            if (maxId != last) sp.edit().putLong("noticeLast", maxId).apply();
        } finally {
            h.disconnect();
        }
    }

    @SuppressWarnings("deprecation")
    static void notifyNotice(Context c, int nid, String title, String body, String url) {
        try {
            NotificationManager nm = (NotificationManager) c.getSystemService(Context.NOTIFICATION_SERVICE);
            if (Build.VERSION.SDK_INT >= 26) {
                Class<?> cc = Class.forName("android.app.NotificationChannel");
                Object ch = cc.getConstructor(String.class, CharSequence.class, int.class).newInstance("notices", "اعلان‌های می‌جا", 4);
                NotificationManager.class.getMethod("createNotificationChannel", cc).invoke(nm, ch);
            }
            Intent it = new Intent(c, MainActivity.class).putExtra(EXTRA_URL, url).setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
            PendingIntent pi = PendingIntent.getActivity(c, nid, it, PendingIntent.FLAG_UPDATE_CURRENT | 0x04000000);
            Notification.Builder b = new Notification.Builder(c)
                    .setSmallIcon(R.drawable.ic_stat)
                    .setContentTitle(title)
                    .setContentText(body)
                    .setStyle(new Notification.BigTextStyle().bigText(body))
                    .setContentIntent(pi)
                    .setAutoCancel(true)
                    .setPriority(Notification.PRIORITY_HIGH)
                    .setDefaults(Notification.DEFAULT_ALL)
                    .setColor(Ui.BRAND);
            if (Build.VERSION.SDK_INT >= 26) Notification.Builder.class.getMethod("setChannelId", String.class).invoke(b, "notices");
            nm.notify(nid, b.build());
        } catch (Exception ignored) { }
    }

    @Override public boolean onStopJob(JobParameters p) { return false; }

    static void check(Context c) {
        Updater.Info i = Updater.fetchInfo(c);
        if (i == null || !i.available) return;
        SharedPreferences sp = c.getSharedPreferences("mija", 0);
        if (sp.getInt("notifiedCode", 0) >= i.code) return; // برای هر نسخه فقط یک اعلان
        notifyUpdate(c, i);
        sp.edit().putInt("notifiedCode", i.code).apply();
    }

    @SuppressWarnings("deprecation")
    static void notifyUpdate(Context c, Updater.Info i) {
        try {
            NotificationManager nm = (NotificationManager) c.getSystemService(Context.NOTIFICATION_SERVICE);
            if (Build.VERSION.SDK_INT >= 26) {
                Class<?> cc = Class.forName("android.app.NotificationChannel");
                Object ch = cc.getConstructor(String.class, CharSequence.class, int.class).newInstance(CHANNEL, "به‌روزرسانی می‌جا", 3);
                NotificationManager.class.getMethod("createNotificationChannel", cc).invoke(nm, ch);
            }
            Intent it = new Intent(c, MainActivity.class).putExtra(EXTRA_UPDATE, true).setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
            PendingIntent pi = PendingIntent.getActivity(c, 7, it, PendingIntent.FLAG_UPDATE_CURRENT | 0x04000000); // FLAG_IMMUTABLE
            Notification.Builder b = new Notification.Builder(c)
                    .setSmallIcon(R.drawable.ic_stat)
                    .setContentTitle("نسخه‌ی جدید می‌جا رسید")
                    .setContentText("نسخه‌ی " + Ui.fa(i.name) + " آماده است؛ برای به‌روزرسانی بزنید.")
                    .setContentIntent(pi)
                    .setAutoCancel(true)
                    .setColor(Ui.BRAND);
            if (Build.VERSION.SDK_INT >= 26) Notification.Builder.class.getMethod("setChannelId", String.class).invoke(b, CHANNEL);
            nm.notify(7, b.build());
        } catch (Exception ignored) { }
    }
}
