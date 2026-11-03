@php
  $events = \App\Support\SmsEvents::all();
  $done = collect($events)->filter(fn ($e, $k) => \App\Support\SmsEvents::patternCode($k) !== '')->count();
@endphp
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}" id="sms-patterns">@csrf
  <input type="hidden" name="group" value="sms">
  <h3><x-i3 name="p_phone" class="sm"/> پترن‌های پیامک ({{ fa($done) }} از {{ fa(count($events)) }} ثبت شده)</h3>
  <div class="sp-rules"><b>چطور کار می‌کند؟</b>
    <ul class="sp-steps">
      <li><b>اگر برای یک پیامک «کد پترن» تأییدشده وارد کنید</b>، آن پیامک با پترن می‌رود (سریع‌تر و بدون محدودیت متن آزاد).</li>
      <li><b>اگر کد پترن خالی باشد یا ippanel آن را نپذیرد</b>، همان متن به‌صورت عادی ارسال می‌شود؛ پس هیچ پیامکی گم نمی‌شود.</li>
      <li>برای ساخت پترن: ippanel ← پیامک ← الگوها ← ایجاد الگو؛ «کپی متن» را بزنید و بچسبانید، متغیرها (مثل <code dir="ltr">%property%</code>) دقیقاً همین نام‌ها بمانند؛ بعد از تأیید «کد پترن» را همین‌جا بنویسید.</li>
      <li>پترن‌ها <b>لینک ندارند</b> (ippanel رد می‌کند). فقط «کد ورود» اهمیت پترن دارد؛ بقیه اختیاری‌اند.</li>
    </ul>
    <b>رعایت قوانین ippanel:</b> پیامک‌های تبلیغاتی و همگانی در پایان «لغو۱۱» دارند؛ متن‌ها رسمی و کوتاه‌اند؛ پیامک‌های ناموفقِ موقت (قطعی اینترنت/خطای سرور) تا ۳ بار خودکار دوباره ارسال می‌شوند؛ خطای دائمی (توکن/اعتبار) در «گزارش پیامک‌ها» با علت فارسی ثبت می‌شود.
  </div>
  @error("test")<div class="alert bad">{{ $message }}</div>@enderror
  <div class="field"><label>شماره برای ارسال آزمایشی</label><input class="input" dir="ltr" name="test" value="{{ old('test', auth()->user()->phone) }}" inputmode="numeric" placeholder="09123456789"></div>
  @php
    $chkLink = '<input type="hidden" name="keys[]" value="sms_use_link"><input type="hidden" name="sms_use_link" value="0"><label class="check"><input type="checkbox" name="sms_use_link" value="1" '.((int) setting('sms_use_link') ? 'checked' : '').'><div><b>لینک کوتاه‌شده در پایان پیامک‌های «عادی» بیاید</b><div class="xs muted" style="font-weight:500">لینک با کوتاه‌کننده‌ی خود می‌جا (mija.click/s/…) کوتاه می‌شود. پیش‌فرض خاموش است چون ippanel ممکن است پیامک دارای لینک را رد کند. در پترن‌ها هرگز لینک نمی‌آید.</div></div></label>';
    $chkFb = '<input type="hidden" name="keys[]" value="sms_plain_fallback"><input type="hidden" name="sms_plain_fallback" value="0"><label class="check"><input type="checkbox" name="sms_plain_fallback" value="1" '.((int) setting('sms_plain_fallback') ? 'checked' : '').'><div><b>اگر کد پترن خالی بود یا پذیرفته نشد، همان متن را عادی بفرست</b><div class="xs muted" style="font-weight:500">پیشنهاد می‌شود روشن بماند. اگر خط شما ارسال عادی را نمی‌پذیرد، برای پیامک‌های مهم پترن بسازید.</div></div></label>';
  @endphp
  {!! $chkLink !!}
  {!! $chkFb !!}
  <div class="mt">
  @foreach($events as $k => $e)
    @php $code = \App\Support\SmsEvents::patternCode($k); @endphp
    <section class="sp-card {{ $code !== '' ? 'set' : '' }}" id="pat-{{ $k }}">
      <header><div><h4>{{ $e[0] }}</h4><small>گیرنده: {{ $e[1] }} — {{ $e[2] }}</small></div><span class="sp-tag">{{ $code !== '' ? 'کد ثبت شده' : 'کد پترن وارد نشده' }}</span></header>
      <div class="sp-text" id="pt-{{ $k }}">{!! preg_replace('/%(\w+)%/', '<bdi dir="ltr">%$1%</bdi>', e(\App\Support\SmsEvents::template($k))) !!}</div>
      <div class="sp-vars">@foreach(\App\Support\SmsEvents::varDocs($k) as $v => $d)<code title="{{ $d }}">%{{ $v }}%</code><small>{{ $d }}</small>@endforeach</div>
      <div class="sp-row">
        <button type="button" class="btn btn-light btn-sm" data-copy="#pt-{{ $k }}"><x-icon name="share" class="sm"/> کپی متن</button>
        <input class="input" dir="ltr" name="sms_pat_{{ $k }}" value="{{ $code }}" placeholder="کد پترن (مثلاً a1b2c3d4e5)" autocomplete="off"><input type="hidden" name="keys[]" value="sms_pat_{{ $k }}">
        <button class="btn btn-light btn-sm" type="submit" formaction="{{ route('admin.sms.check') }}" name="event" value="{{ $k }}" formnovalidate onclick="this.form.querySelector('[name=test]').insertAdjacentHTML('afterend','<input type=hidden name=send value=1>')">ارسال آزمایشی</button>
      </div>
    </section>
  @endforeach
  </div>
  <button class="btn">ذخیره‌ی کد پترن‌ها</button>
</form>
