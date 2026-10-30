@extends('layouts.app')
@section('title', 'دانلود اپلیکیشن اندروید می‌جا')
@section('bodyclass', 'ap-body')
@push('head')
<meta name="description" content="اپلیکیشن اندروید می‌جا را از سایت، کافه‌بازار یا مایکت دانلود کنید؛ رزرو سریع ویلا و کلبه، تقویم شمسی، پرداخت امن و به‌روزرسانی هوشمند.">
<link rel="stylesheet" href="{{ asset('css/appdl.css') }}?v={{ filemtime(public_path('css/appdl.css')) }}">
@endpush
@section('bare')
@php
  $has = (bool) $latest;
  $ver = $latest?->version_name;
  $size = $latest?->direct_size ? fa(number_format($latest->direct_size / 1048576, 1)).' مگابایت' : '';
  $date = $latest?->created_at ? jdate($latest->created_at, 'j F Y') : '';
  $feat = [
    ['p_bolt', 'سریع‌تر از مرورگر', 'اجرای تمام‌صفحه، بدون نوار آدرس؛ ورود شما همیشه حفظ می‌شود.'],
    ['p_calendar', 'تقویم شمسی و قیمت لحظه‌ای', 'روزهای خالی، پر و تخفیف‌های فوری را یک‌جا ببینید.'],
    ['p_shield', 'پرداخت امن', 'درگاه بانکی، کیف پول و کارت‌به‌کارت با فاکتور رسمی.'],
    ['p_camera', 'آپلود عکس و رسید', 'عکس اقامتگاه، مدارک و رسید را مستقیم از گوشی بفرستید.'],
    ['p_bell', 'به‌روزرسانی هوشمند', 'نسخه‌ی جدید که بیاید، خودتان با یک لمس به‌روز می‌شوید.'],
    ['p_chat', 'پیامک و پیگیری رزرو', 'تأیید میزبان، تایمر پرداخت و یادآوری‌ها را از دست ندهید.'],
  ];
  $steps = [
    ['دانلود', 'دکمه‌ی «دانلود مستقیم» را بزنید؛ فایل چند ثانیه‌ای دانلود می‌شود.'],
    ['باز کردن فایل', 'از اعلان دانلود یا پوشه‌ی Download روی mija.apk بزنید.'],
    ['اجازه‌ی نصب', 'اگر اندروید پرسید، «اجازه‌ی نصب از این منبع» را روشن کنید و «نصب» را بزنید.'],
  ];
  $faq = [
    ['آیا نصب از سایت امن است؟', 'بله. فایل با کلید اختصاصی می‌جا امضا شده و اثر انگشت SHA-256 آن در همین صفحه هست؛ می‌توانید با آن صحت فایل را بسنجید. اگر ترجیح می‌دهید، از کافه‌بازار یا مایکت نصب کنید.'],
    ['چرا اندروید هشدار «منبع ناشناس» می‌دهد؟', 'اندروید برای هر برنامه‌ای که از فروشگاه نصب نمی‌شود این پیام را نشان می‌دهد. فقط یک‌بار برای مرورگر یا مدیر فایل شما لازم است.'],
    ['به‌روزرسانی چطور انجام می‌شود؟', 'اگر از بازار یا مایکت نصب کرده باشید، پیام به‌روزرسانی شما را به همان فروشگاه می‌برد. اگر از سایت نصب کرده باشید، نسخه‌ی جدید داخل خود برنامه دانلود، بررسی و نصب می‌شود. صفحه‌ی «به‌روزرسانی» هم همیشه در دسترس است.'],
    ['اطلاعات و رزروهای من با نصب اپ تغییر می‌کند؟', 'نه. اپ و سایت یک حساب دارند؛ فقط با شماره‌ی خودتان وارد شوید.'],
    ['آیفون چه‌طور؟', 'برای آیفون نسخه‌ی وب نصب‌پذیر دارید: در Safari دکمه‌ی اشتراک‌گذاری ← «Add to Home Screen».'],
  ];
@endphp
<div class="ap {{ $client ? 'in-app' : '' }}" id="ap" data-url="{{ route('app') }}" data-ver="{{ $ver }}" data-code="{{ $latest?->version_code }}" data-client="{{ $client ? $client['code'] : 0 }}">
  <section class="ap-hero container">
    <div class="ap-copy">
      <span class="ap-chip"><x-i3 name="p_phone" class="sm"/> اپلیکیشن رسمی اندروید</span>
      <h1>می‌جا را <em>توی جیبتان</em> ببرید</h1>
      <p class="ap-lead">رزرو ویلا و کلبه در چند لمس؛ با تقویم شمسی، قیمت شفاف، پرداخت امن و اعلان رزروها.</p>

      @if($has)
      <div class="ap-cta only-android only-desktop">
        <a class="ap-btn ap-main" id="dl" href="{{ route('app.download') }}" data-dl rel="nofollow">
          <span class="ap-ic"><x-icon name="download"/></span>
          <span class="ap-bt"><b>دانلود مستقیم</b><small>نسخه‌ی <bdi dir="ltr">{{ fa($ver) }}</bdi> · {{ $size }}</small></span>
        </a>
        <div class="ap-stores">
          @if($bazaar)<a class="ap-btn ap-store" href="{{ $bazaar }}" target="_blank" rel="noopener"><span class="ap-bt"><small>دریافت از</small><b>کافه‌بازار</b></span></a>@else<span class="ap-btn ap-store off"><span class="ap-bt"><small>به‌زودی در</small><b>کافه‌بازار</b></span></span>@endif
          @if($myket)<a class="ap-btn ap-store" href="{{ $myket }}" target="_blank" rel="noopener"><span class="ap-bt"><small>دریافت از</small><b>مایکت</b></span></a>@else<span class="ap-btn ap-store off"><span class="ap-bt"><small>به‌زودی در</small><b>مایکت</b></span></span>@endif
        </div>
      </div>
      <div class="ap-ios only-ios">
        <div class="ap-note"><x-i3 name="p_house" class="sm"/><div><b>نسخه‌ی آیفون</b><p>برای آیفون، سایت را در Safari باز کنید و از دکمه‌ی اشتراک‌گذاری، «Add to Home Screen» را بزنید تا مثل یک اپ نصب شود.</p></div></div>
      </div>
      <div class="ap-inapp only-inapp">
        <div class="ap-note ok"><x-i3 name="p_shield" class="sm"/><div><b>شما از اپلیکیشن می‌جا استفاده می‌کنید</b><p>نسخه‌ی نصب‌شده: <span id="cur-ver"><bdi dir="ltr">{{ $client ? fa($client['name']) : '' }}</bdi></span> @if($latest) · آخرین نسخه: <bdi dir="ltr">{{ fa($ver) }}</bdi> @endif</p></div></div>
        <div class="ap-row"><button class="ap-btn ap-main" type="button" id="chk"><span class="ap-ic"><x-icon name="bolt"/></span><span class="ap-bt"><b>بررسی به‌روزرسانی</b><small>همین حالا</small></span></button><a class="ap-btn ap-store" href="{{ route('app.update') }}"><span class="ap-bt"><b>صفحه‌ی به‌روزرسانی</b></span></a></div>
      </div>
      <ul class="ap-meta">
        <li><x-icon name="check" class="sm"/> بدون تبلیغ</li><li><x-icon name="check" class="sm"/> امضای دیجیتال</li><li><x-icon name="check" class="sm"/> اندروید ۵ به بالا</li>
      </ul>
      @else
      <div class="ap-note"><x-i3 name="p_hourglass" class="sm"/><div><b>به‌زودی</b><p>نسخه‌ی اپلیکیشن هنوز منتشر نشده است. تا آن زمان از سایت استفاده کنید.</p></div></div>
      @endif
    </div>

    <div class="ap-visual" aria-hidden="true">
      <div class="ap-glow"></div>
      <div class="ap-phone"><div class="ap-notch"></div><img src="{{ asset('img/app/s1.jpg') }}" alt="" width="390" height="800" fetchpriority="high"></div>
      <div class="ap-float f1"><x-i3 name="p_calendar" class="sm"/> تقویم شمسی</div>
      <div class="ap-float f2"><x-i3 name="p_bolt" class="sm"/> رزرو فوری</div>
    </div>
  </section>

  @if($has)
  <section class="container ap-sec only-desktop">
    <div class="ap-qr glass">
      <div id="qr" class="ap-qrbox" aria-label="کد QR صفحه‌ی دانلود"></div>
      <div><h3>با گوشی اسکن کنید</h3><p>دوربین گوشی اندرویدتان را روی کد بگیرید تا همین صفحه روی گوشی باز شود و دانلود را شروع کنید.</p></div>
    </div>
  </section>

  <section class="container ap-sec">
    <div class="ap-trust glass">
      <div class="ap-t"><small>نسخه</small><b><bdi dir="ltr">{{ fa($ver) }}</bdi></b></div>
      <div class="ap-t"><small>حجم</small><b>{{ $size }}</b></div>
      <div class="ap-t"><small>تاریخ انتشار</small><b>{{ $date }}</b></div>
      <div class="ap-t wide"><small>اثر انگشت SHA-256 فایل مستقیم</small><code id="sha" dir="ltr">{{ $latest->direct_sha256 }}</code><button class="ap-copy-btn" type="button" data-copy="#sha">کپی</button></div>
    </div>
  </section>
  @endif

  <section class="container ap-sec">
    <h2 class="ap-h">چرا اپلیکیشن؟</h2>
    <div class="ap-feats">
      @foreach($feat as $f)<div class="ap-feat glass"><i><x-i3 name="{{ $f[0] }}"/></i><b>{{ $f[1] }}</b><p>{{ $f[2] }}</p></div>@endforeach
    </div>
  </section>

  <section class="container ap-sec">
    <h2 class="ap-h">نمایی از اپ</h2>
    <div class="ap-shots">
      @foreach(['s1', 's2', 's3', 's4'] as $s)<div class="ap-phone sm"><div class="ap-notch"></div><img src="{{ asset('img/app/'.$s.'.jpg') }}" alt="نمایی از اپلیکیشن می‌جا" width="390" height="800" loading="lazy"></div>@endforeach
    </div>
  </section>

  <section class="container ap-sec">
    <h2 class="ap-h">نصب در سه قدم</h2>
    <ol class="ap-steps">
      @foreach($steps as $i => $s)<li class="glass"><span class="n">{{ fa($i + 1) }}</span><div><b>{{ $s[0] }}</b><p>{{ $s[1] }}</p></div></li>@endforeach
    </ol>
  </section>

  @if($history->count() > 0)
  <section class="container ap-sec">
    <div class="ap-hist-h"><h2 class="ap-h">آخرین تغییرات</h2><a class="ap-link" href="{{ route('app.update') }}">همه‌ی نسخه‌ها و به‌روزرسانی ←</a></div>
    <div class="ap-hist">
      @foreach($history->take(2) as $r)
      <article class="glass"><header><b>نسخه‌ی <bdi dir="ltr">{{ fa($r->version_name) }}</bdi></b><span>{{ jdate($r->created_at, 'j F Y') }}</span></header><ul>@foreach(($r->changelog ?? []) as $c)<li>{{ $c }}</li>@endforeach</ul></article>
      @endforeach
    </div>
  </section>
  @endif

  <section class="container ap-sec">
    <h2 class="ap-h">پرسش‌های رایج</h2>
    <div class="ap-faq">@foreach($faq as $q)<details class="glass"><summary>{{ $q[0] }}</summary><p>{{ $q[1] }}</p></details>@endforeach</div>
  </section>
</div>

@include('app._sheet')
<script src="{{ asset('vendor/qrcode.js') }}"></script>
<script src="{{ asset('js/appdl.js') }}?v={{ filemtime(public_path('js/appdl.js')) }}"></script>
@endsection
