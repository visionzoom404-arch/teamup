@php
  $G = \App\Support\Gateways::class;
  $tg = function ($k, $title) { $st = \App\Support\Gateways::status($k); $flag = \App\Support\Gateways::METHODS[$k][2];
    return compact('st', 'flag', 'title'); };
@endphp
<form class="card mb gw" method="post" action="{{ route('admin.settings.update') }}">@csrf<input type="hidden" name="group" value="gateway">
  <div class="gw-h"><h3 style="margin:0"><x-i3 name="p_wallet" class="sm"/> روش‌های پرداخت</h3><div class="muted small">هر روش را جداگانه روشن یا خاموش کنید؛ فقط روش‌های «روشن و کامل» به مهمان نشان داده می‌شوند.</div></div>
  @if(\App\Support\Gateways::on('sandbox') && app()->environment('production'))<div class="alert bad"><x-icon name="info"/><div><b>هشدار:</b> «درگاه آزمایشی» روشن است؛ هر پرداختی بدون کسر پول موفق می‌شود. در سایت واقعی آن را خاموش کنید.</div></div>@endif
  @if(empty(\App\Support\Gateways::online()) && ! \App\Support\Gateways::on('card') && ! \App\Support\Gateways::on('wallet'))<div class="alert warn"><x-icon name="info"/>الان هیچ روش پرداختی فعال نیست؛ مهمان‌ها نمی‌توانند پرداخت کنند.</div>@endif

  {{-- زیبال --}}
  <section class="gw-card {{ \App\Support\Gateways::status('zibal')['live'] ? 'live' : '' }}">
    <header><div class="gw-t"><b>زیبال</b><span>درگاه بانکی (gateway.zibal.ir)</span></div>@include('admin.partials.gw-switch', ['k' => 'zibal'])</header>
    <input type="hidden" name="keys[]" value="zibal_merchants"><div class="field"><label>مرچنت‌کدهای زیبال (هر خط یک کد؛ برای چند کد به‌نوبت استفاده می‌شود)</label><textarea class="input" dir="ltr" name="zibal_merchants" rows="2" placeholder="zibal">{{ setting('zibal_merchants') }}</textarea><div class="help">مرچنت‌کد را از پنل زیبال کپی و اینجا بچسبانید. کد «zibal» حالت آزمایشی خود زیبال است.</div></div>
  </section>

  {{-- بیت‌پی --}}
  <section class="gw-card {{ \App\Support\Gateways::status('bitpay')['live'] ? 'live' : '' }}">
    <header><div class="gw-t"><b>بیت‌پی</b><span>درگاه بانکی (bitpay.ir)</span></div>@include('admin.partials.gw-switch', ['k' => 'bitpay'])</header>
    <input type="hidden" name="keys[]" value="bitpay_api"><div class="field"><label>کلید API بیت‌پی (مرچنت‌کد؛ هر خط یک کد)</label><textarea class="input" dir="ltr" name="bitpay_api" rows="2" placeholder="xxxxx-xxxxx-xxxxx-xxxxx">{{ setting('bitpay_api') }}</textarea><div class="help">کلید API را از پنل بیت‌پی ← درگاه‌های من بردارید و دستی اینجا بگذارید. آدرس بازگشت را خود سایت می‌فرستد: <b class="ltr">{{ route('gateway.bitpay') }}</b></div></div>
    @php $chk('bitpay_test', 'حالت آزمایشی بیت‌پی (فقط برای تست؛ پولی کم نمی‌شود)') @endphp
  </section>

  {{-- کارت‌به‌کارت --}}
  <section class="gw-card {{ \App\Support\Gateways::status('card')['live'] ? 'live' : '' }}">
    <header><div class="gw-t"><b>کارت‌به‌کارت</b><span>مهمان واریز می‌کند، شما در «پرداخت‌ها» تأیید می‌کنید</span></div>@include('admin.partials.gw-switch', ['k' => 'card'])</header>
    <input type="hidden" name="keys[]" value="cards"><div class="field"><label>کارت‌های دریافت (هر خط: شماره کارت | نام صاحب کارت | بانک)</label><textarea class="input" dir="ltr" name="cards" rows="3" placeholder="6037997000001234 | علی رضایی | ملی">{{ setting('cards') }}</textarea></div>
  </section>

  {{-- کیف پول و آزمایشی --}}
  <div class="grid2">
    <section class="gw-card {{ \App\Support\Gateways::status('wallet')['live'] ? 'live' : '' }}"><header><div class="gw-t"><b>کیف پول</b><span>پرداخت از موجودی مهمان</span></div>@include('admin.partials.gw-switch', ['k' => 'wallet'])</header></section>
    <section class="gw-card sandbox {{ \App\Support\Gateways::status('sandbox')['live'] ? 'live' : '' }}"><header><div class="gw-t"><b>درگاه آزمایشی</b><span>فقط برای تست؛ در سایت واقعی خاموش</span></div>@include('admin.partials.gw-switch', ['k' => 'sandbox'])</header></section>
  </div>
  <button class="btn btn-lg mt">ذخیره‌ی روش‌های پرداخت</button>
</form>
