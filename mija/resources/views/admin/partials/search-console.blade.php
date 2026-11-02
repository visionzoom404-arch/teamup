@php $checks = \App\Support\SeoStatus::checks(); $ic = ['ok' => 'check', 'warn' => 'info', 'fail' => 'x']; $lb = ['ok' => 'آماده', 'warn' => 'بررسی کنید', 'fail' => 'مشکل']; @endphp
<div class="card mb sd">
  <div class="sd-h"><div><h3 style="margin:0"><x-i3 name="p_compass" class="sm"/> آمادگی برای گوگل سرچ کنسول</h3><div class="muted small">وضعیت سایت از نگاه گوگل</div></div>
    <a class="btn btn-light btn-sm" target="_blank" rel="noopener" href="https://search.google.com/search-console">باز کردن سرچ کنسول</a></div>
  <ol class="sd-steps">
    @foreach($checks as $i => $st)
    <li class="sd-step {{ $st[0] }}" style="animation-delay:{{ $i * 70 }}ms"><span class="sd-dot"><x-icon name="{{ $ic[$st[0]] }}"/></span><div><b>{{ $st[1] }}</b><span class="sd-tag">{{ $lb[$st[0]] }}</span><p>{{ $st[2] }}</p></div></li>
    @endforeach
  </ol>
</div>

<div class="card mb">
  <h3><x-i3 name="compass" class="sm"/> راه‌اندازی قدم‌به‌قدم</h3>
  <ol class="sp-steps">
    <li>به <b dir="ltr">search.google.com/search-console</b> بروید و با حساب گوگل خودتان وارد شوید؛ «Add property» را بزنید.</li>
    <li><b>روش پیشنهادی:</b> نوع «Domain» و وارد کردن <b dir="ltr">mija.click</b>. گوگل یک رکورد TXT می‌دهد؛ آن را در Cloudflare ← DNS ← Add record (نوع TXT، نام @) بگذارید و Verify بزنید. در این روش نیازی به وارد کردن کد در همین صفحه نیست.</li>
    <li><b>روش دوم:</b> نوع «URL prefix» با آدرس <b dir="ltr">{{ url('/') }}</b> ← روش «HTML tag». کل تگ را همان‌جا کپی و در کادر «کد متا تگ» پایین بچسبانید (خودش فقط کد را برمی‌دارد)، ذخیره کنید، بعد در گوگل Verify بزنید. یا روش «HTML file» و نام فایل را در کادر «نام فایل تأیید» بنویسید.</li>
    <li>بعد از تأیید: منوی Sitemaps ← بنویسید <b dir="ltr">sitemap.xml</b> ← Submit.</li>
    <li>منوی URL Inspection ← آدرس صفحه‌ی اول را بزنید ← «Request indexing». چند روز بعد در همین پنل (آمار بازدید ← ربات‌ها) می‌بینید Googlebot آمده است.</li>
  </ol>
  <div class="sp-rules" style="margin-bottom:0">آمار جستجوها (چند بار در گوگل دیده شدید، چه عبارت‌هایی) فقط داخل خود سرچ کنسول نمایش داده می‌شود؛ گوگل آن را به سایت‌ها نمی‌دهد مگر با اتصال حساب گوگل (API). آمار بازدید واقعی کاربران در منوی «آمار بازدید» همین پنل هست.</div>
</div>

<form class="card mb" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="seo"><h3>کدهای تأیید و آنالیتیکس</h3>
  <input type="hidden" name="keys[]" value="google_verify"><div class="field"><label>کد متا تگ تأیید گوگل (google-site-verification)</label><input class="input" dir="ltr" name="google_verify" value="{{ setting('google_verify') }}" placeholder='کل تگ یا فقط مقدار content را بچسبانید'>
    <div class="help">در سرچ کنسول: Ownership verification ← HTML tag. این کد در بخش head همه‌ی صفحه‌ها گذاشته می‌شود.</div></div>
  <input type="hidden" name="keys[]" value="google_verify_file"><div class="field"><label>نام فایل تأیید گوگل (اختیاری)</label><input class="input" dir="ltr" name="google_verify_file" value="{{ setting('google_verify_file') }}" placeholder="google1234abcd5678efgh.html">
    <div class="help">اگر روش «HTML file» را انتخاب کردید فقط نام فایل را بنویسید؛ سایت خودش آدرس <b dir="ltr">{{ url('/') }}/نام-فایل</b> را با محتوای درست نشان می‌دهد و نیازی به آپلود نیست.</div></div>
  <div class="grid2">@php $txt('bing_verify', 'کد تأیید بینگ (msvalidate.01)') @endphp @php $txt('ga4_id', 'شناسه‌ی گوگل آنالیتیکس (G-XXXX) — اختیاری') @endphp</div>
  <button class="btn mt">ذخیره</button> <a class="btn btn-light mt" target="_blank" href="{{ url('/sitemap.xml') }}">sitemap.xml</a> <a class="btn btn-light mt" target="_blank" href="{{ url('/robots.txt') }}">robots.txt</a> <a class="btn btn-light mt" href="{{ route('admin.analytics') }}">آمار بازدید</a>
</form>
