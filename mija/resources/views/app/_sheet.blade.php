{{-- راهنمای بعد از شروع دانلود --}}
<div class="ap-sheet-back" id="ap-sheet-bk" hidden></div>
<div class="ap-sheet" id="ap-sheet" role="dialog" aria-modal="true" aria-labelledby="ap-sheet-t" hidden>
  <div class="ap-grab"></div>
  <h3 id="ap-sheet-t">دانلود شروع شد</h3>
  <div class="ap-prog"><i></i></div>
  <ol class="ap-mini">
    <li class="on"><span>۱</span> فایل در حال دانلود است؛ کمی صبر کنید.</li>
    <li><span>۲</span> از اعلان دانلود (یا پوشه‌ی Download) روی فایل بزنید.</li>
    <li><span>۳</span> «اجازه‌ی نصب از این منبع» را روشن و «نصب» را بزنید.</li>
  </ol>
  <p class="xs muted">اگر دانلود شروع نشد، <a id="retry" href="{{ route('app.download') }}" rel="nofollow">اینجا بزنید</a>.</p>
  <button class="ap-btn ap-store" type="button" id="ap-sheet-x"><span class="ap-bt"><b>متوجه شدم</b></span></button>
</div>
