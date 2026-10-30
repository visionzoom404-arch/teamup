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
            Toast.makeText(ctx, "نصب به‌روزرسانی انجام نشد. دوباره تلاش کنید یا از صفحه‌ی دانلود سایت نصب کنید.", Toast.LENGTH_LONG).show();
        }
    }
}
