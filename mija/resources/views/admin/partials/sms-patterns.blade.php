@php
  $events = \App\Support\SmsEvents::all();
  $done = collect($events)->filter(fn ($e, $k) => \App\Support\SmsEvents::patternCode($k) !== '')->count();
@endphp
<form class="card mb" method="post" action="{{ route('admin.settings.update') }}" id="sms-patterns">@csrf
  <input type="hidden" name="group" value="sms">
  <h3><x-i3 name="p_phone" class="sm"/> پترن‌های پیامک ({{ fa($done) }} از {{ fa(count($events)) }} ثبت شده)</h3>
  <div class="sp-rules"><b>چطور کار می‌کند؟</b>
    <ol class="sp-steps">
      <li>در پنل ippanel وارد «پیامک ← الگوها (پترن‌ها) ← ایجاد الگو» شوید.</li>
      <li>متن آماده‌ی هر پیامک را از همین صفحه با دکمه‌ی «کپی متن» کپی و در ippanel بچسبانید. متغیرها به شکل <code dir="ltr">%نام%</code> هستند و باید <b>دقیقاً همین نام‌ها</b> بمانند.</li>
      <li>پس از تأیید ippanel، «کد پترن» را بردارید و در کادر همان پیامک بنویسید، بعد «ذخیره‌ی کد پترن‌ها» را بزنید.</li>
      <li>با «ارسال آزمایشی» همان پیامک را روی شماره‌ی خودتان تست کنید.</li>
    </ol>
    <b>رعایت قوانین ippanel:</b> متن الگو را کوتاه و رسمی نگه دارید، تبلیغ و کلمات ممنوع ننویسید، لینک را فقط به شکل متغیر <code dir="ltr">%link%</code> بگذارید (سایت خودش لینک کوتاه <code dir="ltr">mija.click/s/…</code> می‌سازد تا در محدودیت طول متغیر بگنجد) و تا تأیید الگو منتظر بمانید. پیامک بدون کد پترن <b>ارسال نمی‌شود</b> و در «گزارش پیامک‌ها» با علت ثبت می‌شود.
  </div>
  @error("test")<div class="alert bad">{{ $message }}</div>@enderror
  <div class="field"><label>شماره برای ارسال آزمایشی</label><input class="input" dir="ltr" name="test" value="{{ old('test', auth()->user()->phone) }}" inputmode="numeric" placeholder="09123456789"></div>
  @php
    $chkFb = '<input type="hidden" name="keys[]" value="sms_plain_fallback"><input type="hidden" name="sms_plain_fallback" value="0"><label class="check"><input type="checkbox" name="sms_plain_fallback" value="1" '.((int) setting('sms_plain_fallback') ? 'checked' : '').'><div><b>اگر کد پترن خالی بود، همان متن را عادی بفرست</b><div class="xs muted" style="font-weight:500">پیشنهاد نمی‌شود: ippanel ارسال عادی را روی بعضی خطوط نمی‌پذیرد و ممکن است رد شود. بهتر است برای همه‌ی پیامک‌ها پترن بسازید.</div></div></label>';
  @endphp
  {!! $chkFb !!}
  <div class="mt">
  @foreach($events as $k => $e)
    @php $code = \App\Support\SmsEvents::patternCode($k); @endphp
    <section class="sp-card {{ $code !== '' ? 'set' : '' }}" id="pat-{{ $k }}">
      <header><div><h4>{{ $e[0] }}</h4><small>گیرنده: {{ $e[1] }} — {{ $e[2] }}</small></div><span class="sp-tag">{{ $code !== '' ? 'کد ثبت شده' : 'کد پترن وارد نشده' }}</span></header>
      <div class="sp-text" id="pt-{{ $k }}">{!! preg_replace('/%(\w+)%/', '<bdi dir="ltr">%$1%</bdi>', e($e[4])) !!}</div>
      <div class="sp-vars">@foreach($e[3] as $v => $d)<code title="{{ $d }}">%{{ $v }}%</code><small>{{ $d }}</small>@endforeach</div>
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
