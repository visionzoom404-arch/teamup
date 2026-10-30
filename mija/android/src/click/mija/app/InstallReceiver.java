package click.mija.app;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageInstaller;
import android.widget.Toast;

/** نتیجه‌ی نصب بسته‌ی به‌روزرسانی (فقط نسخه‌ی direct استفاده می‌کند). */
public class InstallReceiver extends BroadcastReceiver {
    @Override public void onReceive(Context ctx, Intent i) {
        int st = i.getIntExtra(PackageInstaller.EXTRA_STATUS, -1);
        if (st == PackageInstaller.STATUS_PENDING_USER_ACTION) {
            Intent confirm = i.getParcelableExtra(Intent.EXTRA_INTENT);
            if (confirm != null) {
                confirm.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                try { ctx.startActivity(confirm); } catch (Exception ignored) { }
            }
        } else if (st != PackageInstaller.STATUS_SUCCESS) {
            Updater.installFailed();
            if (st == PackageInstaller.STATUS_FAILURE_ABORTED) return; // کاربر خودش لغو کرد
            String m;
            if (st == PackageInstaller.STATUS_FAILURE_STORAGE) m = "فضای گوشی کافی نیست؛ مقداری فضا آزاد کنید و دوباره تلاش کنید.";
            else if (st == PackageInstaller.STATUS_FAILURE_INCOMPATIBLE) m = "این نسخه با گوشی شما سازگار نیست.";
            else if (st == PackageInstaller.STATUS_FAILURE_CONFLICT) m = "نسخه‌ی نصب‌شده با فایل جدید تداخل دارد؛ اپ را از همان محلِ قبلی (بازار/مایکت/سایت) به‌روز کنید.";
            else if (st == PackageInstaller.STATUS_FAILURE_BLOCKED) m = "نصب توسط گوشی مسدود شد؛ اجازه‌ی «نصب از این منبع» یا محافظ گوشی را بررسی کنید.";
            else m = "نصب به‌روزرسانی انجام نشد. دوباره تلاش کنید یا از صفحه‌ی دانلود سایت نصب کنید.";
            Toast.makeText(ctx, m, Toast.LENGTH_LONG).show();
        }
    }
}
