package click.mija.app;

import android.content.Context;
import android.graphics.Color;
import android.view.MotionEvent;
import android.view.View;
import android.view.ViewConfiguration;
import android.view.ViewGroup;
import android.webkit.WebView;
import android.widget.FrameLayout;

/** کشیدن برای تازه‌سازی، مخصوص WebView (بدون androidx). فقط وقتی صفحه بالاست و کشیدن عمودی است فعال می‌شود. */
class PullLayout extends FrameLayout {
    interface Listener { void onRefresh(); boolean canPull(); }

    private final WebView web;
    private final FrameLayout bubble;
    private final Spin spin;
    private final int slop, trigger, size;
    private float downX, downY, lastDy;
    private boolean pulling, refreshing, armed;
    private Listener listener;

    PullLayout(Context c, WebView w) {
        super(c);
        web = w;
        slop = ViewConfiguration.get(c).getScaledTouchSlop();
        trigger = Ui.dp(c, 84);
        size = Ui.dp(c, 40);
        addView(web, new LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT));

        bubble = new FrameLayout(c);
        bubble.setBackground(Ui.round(Color.WHITE, 20, c));
        bubble.setElevation(Ui.dp(c, 6));
        spin = new Spin(c, Ui.BRAND, 3);
        int pad = Ui.dp(c, 9);
        FrameLayout.LayoutParams sp = new FrameLayout.LayoutParams(size, size);
        spin.setPadding(pad, pad, pad, pad);
        bubble.addView(spin, sp);
        LayoutParams bp = new LayoutParams(size, size);
        bp.gravity = android.view.Gravity.TOP | android.view.Gravity.CENTER_HORIZONTAL;
        addView(bubble, bp);
        bubble.setVisibility(INVISIBLE);
        bubble.setTranslationY(-size);
    }

    void setListener(Listener l) { listener = l; }

    void done() {
        if (!refreshing) return;
        refreshing = false;
        spin.stop();
        bubble.animate().translationY(-size).alpha(0f).setDuration(180).withEndAction(new Runnable() {
            @Override public void run() { bubble.setVisibility(INVISIBLE); bubble.setAlpha(1f); }
        }).start();
    }

    private boolean enabled() {
        return !refreshing && listener != null && listener.canPull() && web.getScrollY() == 0 && !web.canScrollVertically(-1);
    }

    @Override public boolean onInterceptTouchEvent(MotionEvent e) {
        switch (e.getActionMasked()) {
            case MotionEvent.ACTION_DOWN:
                downX = e.getX();
                downY = e.getY();
                armed = enabled();
                pulling = false;
                break;
            case MotionEvent.ACTION_MOVE:
                if (armed && !pulling) {
                    float dy = e.getY() - downY, dx = Math.abs(e.getX() - downX);
                    if (dy > slop * 2 && dy > dx * 1.6f && web.getScrollY() == 0) {
                        pulling = true;
                        bubble.animate().cancel();
                        bubble.setAlpha(1f);
                        bubble.setVisibility(VISIBLE);
                        return true;
                    }
                }
                break;
            case MotionEvent.ACTION_CANCEL:
            case MotionEvent.ACTION_UP:
                armed = false;
                break;
        }
        return false;
    }

    @Override public boolean onTouchEvent(MotionEvent e) {
        if (!pulling) return super.onTouchEvent(e);
        switch (e.getActionMasked()) {
            case MotionEvent.ACTION_MOVE: {
                float dy = Math.max(0, e.getY() - downY);
                lastDy = dy;
                float res = dy * 0.5f;
                float f = Math.min(1f, res / trigger);
                bubble.setTranslationY(Math.min(res, trigger * 1.15f) - size * 0.2f);
                spin.setProgress(f);
                return true;
            }
            case MotionEvent.ACTION_UP:
            case MotionEvent.ACTION_CANCEL: {
                pulling = false;
                armed = false;
                boolean go = e.getActionMasked() == MotionEvent.ACTION_UP && lastDy * 0.5f >= trigger;
                if (go) {
                    refreshing = true;
                    bubble.animate().translationY(trigger * 0.55f).setDuration(150).start();
                    spin.start();
                    listener.onRefresh();
                } else {
                    bubble.animate().translationY(-size).alpha(0f).setDuration(160).withEndAction(new Runnable() {
                        @Override public void run() { bubble.setVisibility(INVISIBLE); bubble.setAlpha(1f); }
                    }).start();
                }
                return true;
            }
        }
        return true;
    }
}
