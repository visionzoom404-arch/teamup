package click.mija.app;

import android.animation.ValueAnimator;
import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Paint;
import android.graphics.RectF;
import android.view.View;
import android.view.animation.LinearInterpolator;

/** چرخانِ نارنجی ساده (بدون وابستگی). */
class Spin extends View {
    private final Paint p = new Paint(Paint.ANTI_ALIAS_FLAG);
    private final RectF r = new RectF();
    private float angle = 0f;
    private float sweep = 270f;
    private ValueAnimator anim;

    Spin(Context c, int colorInt, float strokeDp) {
        super(c);
        p.setStyle(Paint.Style.STROKE);
        p.setStrokeCap(Paint.Cap.ROUND);
        p.setColor(colorInt);
        p.setStrokeWidth(Ui.dp(c, strokeDp));
    }

    /** حالت کشیدن: زاویه و طول کمان با میزان کشش */
    void setProgress(float f) {
        f = Math.max(0f, Math.min(1f, f));
        sweep = 40f + 230f * f;
        angle = f * 270f;
        invalidate();
    }

    void start() {
        if (anim != null) return;
        sweep = 270f;
        anim = ValueAnimator.ofFloat(0f, 360f);
        anim.setDuration(900);
        anim.setRepeatCount(ValueAnimator.INFINITE);
        anim.setInterpolator(new LinearInterpolator());
        anim.addUpdateListener(new ValueAnimator.AnimatorUpdateListener() {
            @Override public void onAnimationUpdate(ValueAnimator a) {
                angle = (Float) a.getAnimatedValue();
                invalidate();
            }
        });
        anim.start();
    }

    void stop() {
        if (anim != null) {
            anim.cancel();
            anim = null;
        }
    }

    @Override protected void onDetachedFromWindow() {
        stop();
        super.onDetachedFromWindow();
    }

    @Override protected void onDraw(Canvas c) {
        float pad = p.getStrokeWidth();
        r.set(pad, pad, getWidth() - pad, getHeight() - pad);
        c.drawArc(r, angle - 90f, sweep, false, p);
    }
}
