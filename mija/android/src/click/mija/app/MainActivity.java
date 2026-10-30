package click.mija.app;

import android.app.Activity;
import android.app.DownloadManager;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.graphics.Bitmap;
import android.graphics.Color;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkRequest;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.os.Handler;
import android.os.Looper;
import android.os.Message;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.JavascriptInterface;
import android.webkit.URLUtil;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONObject;

/** پوسته‌ی بومی می‌جا: اسپلش، نوار پیشرفت، کشیدن برای تازه‌سازی، حالت آفلاین، آپلود فایل، دیپ‌لینک و به‌روزرسانی هوشمند. */
public class MainActivity extends Activity {
    private WebView web;
    private PullLayout pull;
    private View bar;
    private LinearLayout offline;
    private FrameLayout splash;
    private Spin splashSpin;
    private Updater updater;
    private ValueCallback<Uri[]> fileCb;
    private String failedUrl;
    private volatile String curHost = Cfg.HOST;
    private long lastBack;
    private boolean splashGone;
    private ConnectivityManager cm;
    private ConnectivityManager.NetworkCallback netCb;
    private final Handler h = new Handler(Looper.getMainLooper());
    private static final int REQ_FILE = 71;

    @Override protected void onCreate(Bundle b) {
        super.onCreate(b);
        Ui.init(this);
        styleBars();

        FrameLayout root = new FrameLayout(this);
        root.setBackgroundColor(Color.WHITE);

        web = new WebView(this);
        setupWebView();
        pull = new PullLayout(this, web);
        pull.setListener(new PullLayout.Listener() {
            @Override public void onRefresh() { web.reload(); h.postDelayed(new Runnable() { @Override public void run() { pull.done(); } }, 6000); }
            @Override public boolean canPull() { return pullAllowed(); }
        });
        root.addView(pull, new FrameLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT));

        bar = new View(this);
        bar.setBackgroundColor(Ui.BRAND);
        FrameLayout.LayoutParams bp = new FrameLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, Ui.dp(this, 3));
        root.addView(bar, bp);
        bar.setPivotX(getResources().getDisplayMetrics().widthPixels);
        bar.setScaleX(0f);

        buildOffline(root);
        buildSplash(root);
        setContentView(root);

        updater = new Updater(this);
        watchNetwork();

        if (b != null) web.restoreState(b);
        else load(startUrl(getIntent()));
        h.postDelayed(new Runnable() { @Override public void run() { hideSplash(); } }, 9000);
    }

    // ───────────── ظاهر ─────────────

    private void styleBars() {
        try {
            getWindow().setStatusBarColor(Color.WHITE);
            getWindow().setNavigationBarColor(Color.WHITE);
            int f = getWindow().getDecorView().getSystemUiVisibility();
            if (Build.VERSION.SDK_INT >= 23) f |= View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR;
            if (Build.VERSION.SDK_INT >= 26) f |= 0x00000010 /* LIGHT_NAVIGATION_BAR */;
            getWindow().getDecorView().setSystemUiVisibility(f);
        } catch (Exception ignored) { }
    }

    private void buildSplash(FrameLayout root) {
        splash = new FrameLayout(this);
        splash.setBackgroundColor(Color.WHITE);
        ImageView logo = new ImageView(this);
        logo.setImageResource(R.drawable.logo_full);
        logo.setAdjustViewBounds(true);
        FrameLayout.LayoutParams lp = new FrameLayout.LayoutParams(Ui.dp(this, 220), ViewGroup.LayoutParams.WRAP_CONTENT, Gravity.CENTER);
        splash.addView(logo, lp);
        splashSpin = new Spin(this, Ui.BRAND, 3);
        FrameLayout.LayoutParams sp = new FrameLayout.LayoutParams(Ui.dp(this, 30), Ui.dp(this, 30), Gravity.CENTER_HORIZONTAL | Gravity.BOTTOM);
        sp.bottomMargin = Ui.dp(this, 84);
        splash.addView(splashSpin, sp);
        splashSpin.start();
        root.addView(splash, new FrameLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT));
    }

    private void buildOffline(FrameLayout root) {
        offline = new LinearLayout(this);
        offline.setOrientation(LinearLayout.VERTICAL);
        offline.setGravity(Gravity.CENTER);
        offline.setBackgroundColor(Color.WHITE);
        offline.setLayoutDirection(View.LAYOUT_DIRECTION_RTL);
        int pad = Ui.dp(this, 32);
        offline.setPadding(pad, pad, pad, pad);
        ImageView mark = new ImageView(this);
        mark.setImageResource(R.drawable.logo_mark);
        mark.setAlpha(0.9f);
        offline.addView(mark, Ui.lp(Ui.dp(this, 84), Ui.dp(this, 84)));
        TextView t = Ui.text(this, "اتصال به اینترنت برقرار نیست", 18, Ui.INK, true);
        t.setGravity(Gravity.CENTER);
        offline.addView(t, Ui.match(this, 0, 22));
        TextView s = Ui.text(this, "اینترنت خود را بررسی کنید؛ به‌محض وصل شدن، صفحه خودکار بارگذاری می‌شود.", 13.5f, Ui.MUTED, false);
        s.setGravity(Gravity.CENTER);
        offline.addView(s, Ui.match(this, 0, 8));
        android.widget.Button retry = Ui.button(this, "تلاش دوباره", true);
        retry.setOnClickListener(new View.OnClickListener() { @Override public void onClick(View v) { retry(); } });
        LinearLayout.LayoutParams rp = Ui.lp(Ui.dp(this, 200), Ui.dp(this, 50));
        rp.topMargin = Ui.dp(this, 24);
        offline.addView(retry, rp);
        offline.setVisibility(View.GONE);
        root.addView(offline, new FrameLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT));
    }

    private void hideSplash() {
        if (splashGone || splash == null) return;
        splashGone = true;
        splash.animate().alpha(0f).setDuration(260).withEndAction(new Runnable() {
            @Override public void run() { splash.setVisibility(View.GONE); splashSpin.stop(); }
        }).start();
        // بررسی به‌روزرسانی کمی بعد از ورود
        h.postDelayed(new Runnable() { @Override public void run() { if (updater != null) updater.check(false); } }, 2500);
    }

    // ───────────── وب‌ویو ─────────────

    private void setupWebView() {
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setUseWideViewPort(true);
        s.setLoadWithOverviewMode(false);
        s.setTextZoom(100);
        s.setBuiltInZoomControls(false);
        s.setSupportZoom(false);
        s.setAllowFileAccess(false);
        s.setAllowContentAccess(false);
        s.setMediaPlaybackRequiresUserGesture(true);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        s.setSupportMultipleWindows(true);
        s.setJavaScriptCanOpenWindowsAutomatically(true);
        s.setUserAgentString(s.getUserAgentString() + " MijaApp/" + Cfg.versionName(this) + " (code " + Cfg.versionCode(this) + "; flavor " + Cfg.flavor() + ")");
        web.setBackgroundColor(Color.WHITE);
        web.setOverScrollMode(View.OVER_SCROLL_NEVER);
        CookieManager cmg = CookieManager.getInstance();
        cmg.setAcceptCookie(true);
        cmg.setAcceptThirdPartyCookies(web, true);
        web.addJavascriptInterface(new Bridge(), "MijaNative");

        web.setWebViewClient(new WebViewClient() {
            @Override public boolean shouldOverrideUrlLoading(WebView v, String url) { return route(url); }

            @Override public void onPageStarted(WebView v, String url, Bitmap fav) {
                curHost = hostOf(url);
                bar.animate().cancel();
                bar.setAlpha(1f);
                bar.setScaleX(0.08f);
                if (failedUrl != null && !url.equals(failedUrl)) failedUrl = null;
            }

            @Override public void onPageFinished(WebView v, String url) {
                curHost = hostOf(url);
                bar.animate().scaleX(1f).setDuration(120).withEndAction(new Runnable() {
                    @Override public void run() { bar.animate().alpha(0f).setDuration(220).start(); }
                }).start();
                pull.done();
                CookieManager.getInstance().flush();
                if (failedUrl == null) {
                    offline.setVisibility(View.GONE);
                    hideSplash();
                }
            }

            @SuppressWarnings("deprecation")
            @Override public void onReceivedError(WebView v, int code, String desc, String failing) {
                // این نسخه از متد فقط برای صفحه‌ی اصلی (main frame) روی اندروید ۶+ صدا زده می‌شود
                if (code == ERROR_UNSUPPORTED_SCHEME) return;
                failedUrl = failing;
                offline.setVisibility(View.VISIBLE);
                pull.done();
                hideSplash();
            }
        });

        web.setWebChromeClient(new WebChromeClient() {
            @Override public void onProgressChanged(WebView v, int p) {
                if (p < 100) bar.setScaleX(Math.max(0.08f, p / 100f));
            }

            @Override public boolean onShowFileChooser(WebView v, ValueCallback<Uri[]> cb, FileChooserParams params) {
                if (fileCb != null) fileCb.onReceiveValue(null);
                fileCb = cb;
                try {
                    Intent i = new Intent(Intent.ACTION_GET_CONTENT);
                    i.addCategory(Intent.CATEGORY_OPENABLE);
                    String[] types = params.getAcceptTypes();
                    String type = types != null && types.length > 0 && types[0] != null && !types[0].isEmpty() ? types[0] : "*/*";
                    i.setType(type);
                    if (params.getMode() == FileChooserParams.MODE_OPEN_MULTIPLE) i.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
                    startActivityForResult(Intent.createChooser(i, "انتخاب فایل"), REQ_FILE);
                    return true;
                } catch (Exception e) {
                    fileCb = null;
                    cb.onReceiveValue(null);
                    return false;
                }
            }

            @Override public boolean onCreateWindow(WebView v, boolean dialog, boolean gesture, Message msg) {
                // لینک‌های target=_blank و window.open را در همین وب‌ویو باز می‌کنیم
                WebView tmp = new WebView(MainActivity.this);
                tmp.setWebViewClient(new WebViewClient() {
                    @Override public boolean shouldOverrideUrlLoading(WebView t, String url) { openFromPopup(url); destroyLater(t); return true; }
                    @Override public void onPageStarted(WebView t, String url, Bitmap f) { if (url != null && !url.equals("about:blank")) { openFromPopup(url); t.stopLoading(); destroyLater(t); } }
                });
                WebView.WebViewTransport tr = (WebView.WebViewTransport) msg.obj;
                tr.setWebView(tmp);
                msg.sendToTarget();
                return true;
            }
        });

        web.setDownloadListener(new DownloadListener() {
            @Override public void onDownloadStart(String url, String ua, String cd, String mime, long len) { download(url, ua, cd, mime); }
        });
    }

    private void destroyLater(final WebView t) {
        h.post(new Runnable() { @Override public void run() { try { t.destroy(); } catch (Exception ignored) { } } });
    }

    private void openFromPopup(final String url) {
        h.post(new Runnable() { @Override public void run() { if (!route(url)) web.loadUrl(url); } });
    }

    private static String hostOf(String url) {
        try { String hh = Uri.parse(url).getHost(); return hh == null ? "" : hh; } catch (Exception e) { return ""; }
    }

    private static boolean isOwn(String host) {
        return host != null && (host.equals(Cfg.HOST) || host.endsWith("." + Cfg.HOST));
    }

    /** true یعنی خودمان مدیریتش کردیم (وب‌ویو لود نکند). */
    private boolean route(String url) {
        if (url == null) return true;
        Uri u = Uri.parse(url);
        String sc = u.getScheme() == null ? "" : u.getScheme().toLowerCase();
        if (sc.equals("http") || sc.equals("https")) {
            String host = u.getHost() == null ? "" : u.getHost().toLowerCase();
            if (isOwn(host)) {
                String p = u.getPath() == null ? "" : u.getPath();
                // فایل APK خارج از اپ دانلود شود (نسخه‌ی فروشگاهی هرگز APK نصب نمی‌کند)
                if (p.endsWith(".apk")) { if (Cfg.STORE) return true; download(url, null, null, "application/vnd.android.package-archive"); return true; }
                return false;
            }
            if (host.equals("wa.me") || host.endsWith("whatsapp.com") || host.equals("t.me") || host.endsWith("instagram.com")
                    || host.endsWith("google.com") && (u.getPath() != null && u.getPath().startsWith("/maps")) || host.endsWith("neshan.org") || host.endsWith("balad.ir")
                    || host.equals("cafebazaar.ir") || host.equals("myket.ir")) {
                external(new Intent(Intent.ACTION_VIEW, u));
                return true;
            }
            return false; // درگاه‌های پرداخت و سایر صفحه‌ها داخل اپ می‌مانند
        }
        if (sc.equals("intent")) {
            try {
                Intent i = Intent.parseUri(url, Intent.URI_INTENT_SCHEME);
                i.setComponent(null);
                i.setSelector(null);
                i.addCategory(Intent.CATEGORY_BROWSABLE);
                external(i);
            } catch (Exception ignored) { }
            return true;
        }
        if (sc.equals("tel") || sc.equals("sms") || sc.equals("smsto") || sc.equals("mailto") || sc.equals("geo") || sc.equals("bazaar") || sc.equals("myket") || sc.equals("market")) {
            external(new Intent(Intent.ACTION_VIEW, u));
            return true;
        }
        return true; // طرح‌های ناشناخته را نادیده بگیر
    }

    private void external(Intent i) {
        try { startActivity(i); } catch (ActivityNotFoundException e) { Toast.makeText(this, "برنامه‌ای برای باز کردن این لینک پیدا نشد.", Toast.LENGTH_SHORT).show(); }
    }

    private void download(String url, String ua, String cd, String mime) {
        if (Cfg.STORE && url.endsWith(".apk")) return;
        try {
            DownloadManager.Request r = new DownloadManager.Request(Uri.parse(url));
            String name = URLUtil.guessFileName(url, cd, mime);
            r.setMimeType(mime);
            String ck = CookieManager.getInstance().getCookie(url);
            if (ck != null) r.addRequestHeader("Cookie", ck);
            r.addRequestHeader("User-Agent", ua != null ? ua : web.getSettings().getUserAgentString());
            r.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
            r.setTitle(name);
            r.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, name);
            ((DownloadManager) getSystemService(DOWNLOAD_SERVICE)).enqueue(r);
            Toast.makeText(this, "دانلود شروع شد…", Toast.LENGTH_SHORT).show();
        } catch (Exception e) {
            external(new Intent(Intent.ACTION_VIEW, Uri.parse(url)));
        }
    }

    // ───────────── ناوبری ─────────────

    private String startUrl(Intent i) {
        if (i != null && i.getData() != null && isOwn(i.getData().getHost())) return i.getData().toString();
        return Cfg.BASE + "/";
    }

    private void load(String url) {
        failedUrl = null;
        offline.setVisibility(View.GONE);
        web.loadUrl(url);
    }

    private void retry() {
        offline.setVisibility(View.GONE);
        String u = failedUrl != null ? failedUrl : Cfg.BASE + "/";
        failedUrl = null;
        web.loadUrl(u);
    }

    private boolean pullAllowed() {
        String u = web.getUrl();
        if (u == null) return false;
        String p = Uri.parse(u).getPath();
        if (p == null) return true;
        String[] block = {"/create", "/edit", "/pay", "/checkout", "/login", "/register", "/onboard", "/book", "/gateway", "/invoice"};
        for (String x : block) if (p.contains(x)) return false;
        return true;
    }

    private void watchNetwork() {
        try {
            cm = (ConnectivityManager) getSystemService(CONNECTIVITY_SERVICE);
            netCb = new ConnectivityManager.NetworkCallback() {
                @Override public void onAvailable(Network n) {
                    h.post(new Runnable() { @Override public void run() { if (offline.getVisibility() == View.VISIBLE) retry(); } });
                }
            };
            cm.registerNetworkCallback(new NetworkRequest.Builder().build(), netCb);
        } catch (Exception ignored) { }
    }

    @Override protected void onNewIntent(Intent i) {
        super.onNewIntent(i);
        setIntent(i);
        if (i != null && i.getData() != null && isOwn(i.getData().getHost())) load(i.getData().toString());
    }

    @Override public void onBackPressed() {
        if (offline.getVisibility() == View.VISIBLE && web.canGoBack()) { offline.setVisibility(View.GONE); web.goBack(); return; }
        if (web.canGoBack()) { web.goBack(); return; }
        long now = System.currentTimeMillis();
        if (now - lastBack < 2000) { super.onBackPressed(); return; }
        lastBack = now;
        Toast.makeText(this, "برای خروج، یک بار دیگر بزنید", Toast.LENGTH_SHORT).show();
    }

    @Override protected void onActivityResult(int req, int res, Intent data) {
        if (req == REQ_FILE) {
            if (fileCb != null) {
                Uri[] r = res == RESULT_OK ? WebChromeClient.FileChooserParams.parseResult(res, data) : null;
                fileCb.onReceiveValue(r);
                fileCb = null;
            }
            return;
        }
        super.onActivityResult(req, res, data);
    }

    @Override protected void onSaveInstanceState(Bundle o) { super.onSaveInstanceState(o); web.saveState(o); }
    @Override protected void onResume() { super.onResume(); web.onResume(); }
    @Override protected void onPause() { web.onPause(); CookieManager.getInstance().flush(); super.onPause(); }

    @Override protected void onDestroy() {
        try { if (cm != null && netCb != null) cm.unregisterNetworkCallback(netCb); } catch (Exception ignored) { }
        h.removeCallbacksAndMessages(null);
        if (web != null) { web.stopLoading(); web.destroy(); }
        super.onDestroy();
    }

    // ───────────── پل جاوااسکریپت (فقط کارهای بی‌خطر؛ فقط برای دامنه‌ی خودمان) ─────────────

    private class Bridge {
        private boolean ok() { return isOwn(curHost); }

        @JavascriptInterface public String info() {
            try {
                JSONObject o = new JSONObject();
                o.put("versionName", Cfg.versionName(MainActivity.this));
                o.put("versionCode", Cfg.versionCode(MainActivity.this));
                o.put("flavor", Cfg.flavor());
                o.put("installer", Cfg.installer(MainActivity.this));
                o.put("sdk", Build.VERSION.SDK_INT);
                return o.toString();
            } catch (Exception e) { return "{}"; }
        }

        @JavascriptInterface public void checkUpdate() {
            if (!ok()) return;
            runOnUiThread(new Runnable() { @Override public void run() { updater.check(true); } });
        }

        @JavascriptInterface public void startUpdate() {
            if (!ok()) return;
            runOnUiThread(new Runnable() { @Override public void run() { updater.startFromWeb(); } });
        }

        @JavascriptInterface public void share(final String text) {
            if (!ok() || text == null) return;
            runOnUiThread(new Runnable() {
                @Override public void run() {
                    Intent i = new Intent(Intent.ACTION_SEND);
                    i.setType("text/plain");
                    i.putExtra(Intent.EXTRA_TEXT, text.length() > 2000 ? text.substring(0, 2000) : text);
                    startActivity(Intent.createChooser(i, "اشتراک‌گذاری"));
                }
            });
        }
    }
}
