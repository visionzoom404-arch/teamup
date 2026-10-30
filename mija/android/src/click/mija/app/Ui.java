package click.mija.app;

import android.app.Activity;
import android.content.Context;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.TextView;

/** ابزارهای ساخت رابط بومی (بدون XML) — راست‌به‌چپ، فونت وزیرمتن، رنگ برند. */
final class Ui {
    static final int BRAND = Color.parseColor("#E8860C");
    static final int BRAND_D = Color.parseColor("#C9700A");
    static final int INK = Color.parseColor("#1F2430");
    static final int MUTED = Color.parseColor("#6B7280");
    static final int LINE = Color.parseColor("#EEE7DC");
    static final int TINT = Color.parseColor("#FFF4E5");

    private static Typeface regular, bold;

    static void init(Context c) {
        if (regular != null) return;
        try {
            regular = Typeface.createFromAsset(c.getAssets(), "vazir-regular.ttf");
            bold = Typeface.createFromAsset(c.getAssets(), "vazir-bold.ttf");
        } catch (Exception e) {
            regular = Typeface.DEFAULT;
            bold = Typeface.DEFAULT_BOLD;
        }
    }

    static int dp(Context c, float v) {
        return Math.round(v * c.getResources().getDisplayMetrics().density);
    }

    static GradientDrawable round(int color, float radiusDp, Context c) {
        GradientDrawable g = new GradientDrawable();
        g.setColor(color);
        g.setCornerRadius(dp(c, radiusDp));
        return g;
    }

    static TextView text(Context c, String s, float sp, int color, boolean isBold) {
        init(c);
        TextView t = new TextView(c);
        t.setText(s);
        t.setTextSize(sp);
        t.setTextColor(color);
        t.setTypeface(isBold ? bold : regular);
        t.setTextDirection(View.TEXT_DIRECTION_RTL);
        t.setLineSpacing(0, 1.25f);
        return t;
    }

    /** دکمه‌ی گرد؛ primary = نارنجی، وگرنه کرم روشن. */
    static Button button(Context c, String label, boolean primary) {
        init(c);
        Button b = new Button(c);
        b.setText(label);
        b.setAllCaps(false);
        b.setTypeface(bold);
        b.setTextSize(15);
        b.setTextColor(primary ? Color.WHITE : BRAND_D);
        b.setBackground(round(primary ? BRAND : TINT, 14, c));
        b.setStateListAnimator(null);
        b.setMinHeight(dp(c, 48));
        b.setMinimumHeight(dp(c, 48));
        b.setPadding(dp(c, 18), 0, dp(c, 18), 0);
        return b;
    }

    static LinearLayout.LayoutParams lp(int w, int h) {
        return new LinearLayout.LayoutParams(w, h);
    }

    static LinearLayout.LayoutParams match(Context c, int heightDp, int topMarginDp) {
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, heightDp <= 0 ? ViewGroup.LayoutParams.WRAP_CONTENT : dp(c, heightDp));
        p.topMargin = dp(c, topMarginDp);
        return p;
    }

    /** ارقام فارسی */
    static String fa(String s) {
        StringBuilder b = new StringBuilder();
        for (char ch : s.toCharArray()) b.append(ch >= '0' && ch <= '9' ? (char) ('۰' + (ch - '0')) : ch);
        return b.toString();
    }

    static String fileSize(long bytes) {
        if (bytes <= 0) return "";
        double mb = bytes / 1048576.0;
        return fa(String.format(java.util.Locale.US, "%.1f", mb)) + " مگابایت";
    }

    static void runUi(Activity a, Runnable r) {
        if (a != null) a.runOnUiThread(r);
    }

    static int gravityCenter() { return Gravity.CENTER; }

    private Ui() {}
}
