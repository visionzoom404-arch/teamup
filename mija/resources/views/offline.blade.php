<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#E8860C">
<meta name="color-scheme" content="light dark">
<meta name="robots" content="noindex">
<title>اتصال برقرار نیست | می‌جا</title>
<script>(function(){try{var t=localStorage.getItem('mija-theme');if(t!=='dark'&&t!=='light')t=(window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light';document.documentElement.setAttribute('data-theme',t)}catch(e){}})()</script>
<style>
/* صفحه‌ی آفلاین مستقل است (بدون وابستگی به CSS سایت) تا حتی بدون اینترنت درست و وسط‌چین دیده شود */
@font-face{font-family:Vazirmatn;src:url('/fonts/Vazirmatn-Regular.woff2') format('woff2');font-weight:400;font-display:swap}
@font-face{font-family:Vazirmatn;src:url('/fonts/Vazirmatn-Bold.woff2') format('woff2');font-weight:700;font-display:swap}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%}
body{min-height:100vh;min-height:100dvh;display:grid;place-items:center;padding:24px 16px calc(24px + env(safe-area-inset-bottom,0px));
  font-family:Vazirmatn,Tahoma,system-ui,sans-serif;color:#2a1a02;
  background:radial-gradient(700px 480px at 90% -8%,rgba(250,176,62,.35),transparent 62%),radial-gradient(640px 460px at -6% 100%,rgba(232,134,12,.2),transparent 62%),linear-gradient(180deg,#FFF9EF,#FDEFD6)}
html[data-theme="dark"] body{color:#F4EDE3;background:radial-gradient(700px 480px at 90% -8%,rgba(214,128,26,.22),transparent 62%),linear-gradient(180deg,#17120D,#100C09)}
.card{width:100%;max-width:440px;text-align:center;padding:34px 24px 28px;border-radius:26px;
  background:linear-gradient(180deg,rgba(255,255,255,.82),rgba(255,248,236,.62));border:1px solid rgba(255,255,255,.9);
  box-shadow:0 30px 60px -28px rgba(120,60,0,.45),inset 0 1px 0 #fff;-webkit-backdrop-filter:blur(18px);backdrop-filter:blur(18px)}
html[data-theme="dark"] .card{background:linear-gradient(180deg,rgba(46,36,27,.85),rgba(28,21,15,.78));border-color:rgba(255,255,255,.12);box-shadow:0 30px 60px -28px rgba(0,0,0,.85),inset 0 1px 0 rgba(255,255,255,.08)}
.logo{height:40px;width:auto;margin:0 auto 16px;display:block}
.ic{width:56px;height:56px;margin:0 auto 12px;display:grid;place-items:center;border-radius:50%;background:rgba(232,134,12,.14);color:#C4650C}
html[data-theme="dark"] .ic{background:rgba(240,162,72,.16);color:#F8C98A}
.ic svg{width:28px;height:28px}
h1{margin:0 0 8px;font-size:20px;line-height:1.6}
p{margin:0 0 20px;font-size:14px;line-height:2;color:#6b5a44}
html[data-theme="dark"] p{color:#B5AB9B}
.row{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 22px;border-radius:14px;border:1px solid rgba(255,255,255,.7);font:inherit;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;
  background:linear-gradient(135deg,#F9C468,#E8860C);color:#2a1a02;box-shadow:0 12px 22px -12px rgba(232,134,12,.9),inset 0 1px 0 rgba(255,255,255,.6)}
.btn.light{background:rgba(255,255,255,.7);border-color:rgba(168,95,5,.25);box-shadow:none}
html[data-theme="dark"] .btn.light{background:rgba(255,255,255,.08);color:#F4EDE3;border-color:rgba(255,255,255,.2)}
.btn:active{transform:scale(.97)}
.tip{margin-top:18px;font-size:12px;color:#8a7a66;line-height:1.9}
html[data-theme="dark"] .tip{color:#8B8274}
@media(max-width:380px){.card{padding:28px 18px 22px;border-radius:22px}h1{font-size:18px}}
</style>
</head>
<body>
<main class="card" role="alert">
  <img class="logo" src="/img/mija-logo-h.png" alt="می‌جا" height="40">
  <div class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8.8a15 15 0 0 1 20 0"/><path d="M5 12.5a10.5 10.5 0 0 1 14 0"/><path d="M8.5 16a5.5 5.5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor"/></svg></div>
  <h1>اتصال برقرار نیست</h1>
  <p>اینترنت شما قطع است یا سایت موقتاً در دسترس نیست. اتصال را بررسی کنید و دوباره تلاش کنید.</p>
  <div class="row">
    <button class="btn" type="button" onclick="location.reload()">تلاش دوباره</button>
    <a class="btn light" href="/">صفحه‌ی اول</a>
  </div>
  <div class="tip">اگر اینترنت‌تان وصل است و این پیام می‌ماند، چند دقیقه بعد دوباره امتحان کنید.</div>
</main>
<script>addEventListener('online',function(){location.reload()})</script>
</body>
</html>
