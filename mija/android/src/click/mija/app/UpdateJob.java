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

/**
 * اعلان «نسخه‌ی جدید رسید»: هر چند ساعت یک بار (حتی وقتی اپ بسته است) آخرین نسخه را از سایت می‌پرسد؛
 * اگر جدیدتر بود، یک اعلان نشان می‌دهد که با لمس آن، پنجره‌ی به‌روزرسانی باز می‌شود.
 * (بدون سرویس‌های گوگل؛ مناسب بازار و مایکت.)
 */
public class UpdateJob extends JobService {
    static final int JOB_ID = 4101;
    static final String CHANNEL = "updates";
    static final String EXTRA_UPDATE = "click.mija.app.UPDATE";

    static void schedule(Context c) {
        try {
            JobScheduler js = (JobScheduler) c.getSystemService(Context.JOB_SCHEDULER_SERVICE);
            JobInfo job = new JobInfo.Builder(JOB_ID, new ComponentName(c, UpdateJob.class))
                    .setPeriodic(6L * 3600 * 1000)
                    .setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY)
                    .setPersisted(true)
                    .build();
            js.schedule(job);
        } catch (Exception ignored) { }
    }

    @Override public boolean onStartJob(final JobParameters p) {
        new Thread(new Runnable() {
            @Override public void run() {
                try { check(UpdateJob.this); } catch (Exception ignored) { }
                jobFinished(p, false);
            }
        }).start();
        return true;
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
