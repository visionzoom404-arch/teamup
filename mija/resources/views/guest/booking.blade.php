@extends('layouts.panel')
@push('vendor')<script src="{{ asset('vendor/confetti.min.js') }}"></script>@endpush
@section('title', 'رزرو '.$b->code)
@section('content')
@php
  $p = $b->property; $host = $p->host; $unlocked = $b->detailsUnlocked();
  [$at, $bad] = $b->stepState('guest');
  $steps = [['ثبت رزرو', 'receipt'], [$p->instant_booking ? 'تأیید خودکار' : 'تأیید میزبان', 'check'], ['پرداخت', 'card'], ['رزرو قطعی', 'shield'], ['اقامت', 'home'], ['ثبت نظر', 'star']];
  $cardPending = $b->payments->firstWhere('status', 'review');
  $bd = $b->breakdown ?? [];
@endphp
@if(session('celebrate'))<div data-confetti hidden></div>@endif
<div class="page-h"><div><a href="{{ route('guest.bookings') }}" class="small muted">← سفرهای من</a><h1>رزرو <span class="ltr">{{ $b->code }}</span></h1></div><span class="badge {{ $b->statusColor() }}" style="font-size:13px;padding:5px 14px">{{ $b->statusLabel() }}</span></div>

@include('partials.stepper', ['steps' => $steps, 'at' => $at, 'bad' => $bad])

{{-- وضعیت جاری و تایمر --}}
@if($b->status === 'pending_host')
  <div class="now-card"><div class="grow"><h3><x-i3 name="p_hourglass" class="sm"/> در انتظار تأیید میزبان</h3><p>درخواست شما به‌صورت پیامک و اعلان برای «{{ $host->name }}» ارسال شد. اگر تأیید کند، برای پرداخت {{ fa(setting('payment_window_minutes')) }} دقیقه فرصت دارید؛ اگر رد کند، ممکن است روز دیگری با تخفیف پیشنهاد دهد.</p></div>@include('partials.countdown', ['until' => $b->respond_by, 'total' => setting('host_response_minutes') * 60, 'label' => 'فرصت پاسخ میزبان'])</div>
@elseif($b->canPay())
  <div class="now-card hot"><div class="grow"><h3><x-i3 name="p_wallet" class="sm"/> {{ $p->instant_booking ? 'رزرو شما ثبت شد' : 'میزبان درخواست شما را تأیید کرد' }}</h3><p>برای قطعی شدن رزرو، پرداخت را انجام دهید؛ این تاریخ‌ها فقط تا پایان مهلت برای شما نگه داشته می‌شود.</p><a class="btn btn-lg" href="{{ route('booking.invoice', $b) }}"><x-icon name="receipt"/> مشاهده فاکتور و پرداخت</a></div>@include('partials.countdown', ['until' => $b->pay_by, 'total' => setting('payment_window_minutes') * 60, 'label' => 'فرصت پرداخت'])</div>
@elseif($b->status === 'payment_review')
  <div class="now-card"><div class="grow"><h3><x-i3 name="p_hourglass" class="sm"/> پرداخت شما در حال بررسی است</h3><p>پرداخت کارت‌به‌کارت شما ثبت شد. پس از تأیید مدیر (معمولاً چند ساعت) رزرو قطعی می‌شود و اطلاعات میزبان برای شما نمایش داده می‌شود.</p></div></div>
@elseif($b->canRespondOffer())
  @php
    $q = $p->quote($b->offer_check_in, $b->offer_check_out, $b->guestsCount());
    $q = $q['ok'] ? \App\Support\Pricing::applyOffer($q, (int) $b->offer_discount) : null;
  @endphp
  <div class="now-card hot offer"><div class="grow"><h3><x-i3 name="p_gift" class="sm"/> پیشنهاد ویژه‌ی میزبان: روز دیگر با {{ fa($b->offer_discount) }}٪ تخفیف</h3>
    <p>میزبان تاریخ درخواستی شما را نتوانست بپذیرد و این بازه را با تخفیف پیشنهاد می‌دهد:</p>
    <div class="offer-box"><div><small>درخواست شما</small><s>{{ jdate($b->check_in, 'j F') }} تا {{ jdate($b->check_out, 'j F') }}</s></div><x-icon name="chev-l"/><div><small>پیشنهاد میزبان</small><b>{{ jdate($b->offer_check_in, 'l j F') }} تا {{ jdate($b->offer_check_out, 'j F') }}</b></div>@if($q)<div><small>مبلغ جدید</small><b class="off">{{ toman($q['total']) }}</b><s>{{ toman($q['total_before']) }}</s></div>@endif</div>
    <div class="row wrap" style="gap:8px"><form method="post" action="{{ route('guest.bookings.offer', $b) }}">@csrf<input type="hidden" name="accept" value="1"><button class="btn btn-lg"><x-icon name="check"/> می‌پذیرم و پرداخت می‌کنم</button></form><form method="post" action="{{ route('guest.bookings.offer', $b) }}">@csrf<input type="hidden" name="accept" value="0"><button class="btn btn-lg btn-light">نمی‌خواهم؛ اقامتگاه دیگر را رزرو می‌کنم</button></form></div></div>@include('partials.countdown', ['until' => $b->offer_expires_at, 'total' => setting('offer_minutes') * 60, 'label' => 'مهلت پاسخ'])</div>
@elseif($b->status === 'rejected')
  <div class="alert bad"><x-icon name="info"/><div>{{ $b->offer_status === 'declined' ? 'شما پیشنهاد روز جایگزین را نپذیرفتید.' : 'میزبان این درخواست را نپذیرفت.' }} @if($b->cancel_reason)<br>{{ $b->cancel_reason }}@endif <a href="{{ route('search') }}" style="text-decoration:underline">اقامتگاه‌های دیگر</a></div></div>
@elseif($b->status === 'expired')
  <div class="alert warn"><x-icon name="clock"/><div>مهلت این رزرو به پایان رسید. <a href="{{ route('property', $p) }}" style="text-decoration:underline">دوباره رزرو کنید</a></div></div>
@endif

<div class="bk-grid">
  <div class="stack">
    {{-- اقامتگاه و جزئیات --}}
    <div class="card bk-prop">
      <a href="{{ route('property', $p) }}" class="bk-img"><img src="{{ $p->cover() }}" alt=""></a>
      <div><h3 style="margin:0 0 2px"><a href="{{ route('property', $p) }}">{{ $p->title }}</a></h3><div class="muted small">{{ $p->province }}، {{ $p->city }}</div></div>
      <div class="bk-facts">
        <div><x-icon name="calendar" class="sm"/><small>ورود</small><b>{{ jdate($b->check_in, 'l j F') }}</b><em>ساعت {{ fa($p->check_in_time) }}</em></div>
        <div><x-icon name="calendar" class="sm"/><small>خروج</small><b>{{ jdate($b->check_out, 'l j F') }}</b><em>ساعت {{ fa($p->check_out_time) }}</em></div>
        <div><x-icon name="clock" class="sm"/><small>مدت</small><b>{{ fa($b->nights) }} شب</b></div>
        <div><x-icon name="users" class="sm"/><small>مسافران</small><b>{{ fa($b->adults) }} بزرگسال@if($b->children) + {{ fa($b->children) }} کودک @endif</b></div>
      </div>
      <div class="row wrap" style="gap:8px"><a class="btn btn-sm btn-light" href="{{ route('booking.invoice', $b) }}"><x-icon name="receipt" class="sm"/> فاکتور</a>@if($unlocked)<a class="btn btn-sm btn-light" href="{{ route('booking.ics', $b) }}"><x-icon name="calendar" class="sm"/> افزودن به تقویم</a>@endif @if($b->status === 'completed')<a class="btn btn-sm" href="{{ route('property', $p) }}"><x-icon name="bolt" class="sm"/> رزرو دوباره</a>@endif</div>
      @if($b->status === 'confirmed')<details class="hint"><summary><x-i3 name="p_suitcase" class="sm"/> چک‌لیست سفر</summary><div class="hint-b"><div class="packlist">@foreach(['مدارک شناسایی همه‌ی مسافران', 'داروها و وسایل شخصی', 'شارژر و پاوربانک', 'لباس مناسب هوای مقصد', 'خوراکی و وسایل پذیرایی', 'برنامه‌ی مسیر و ساعت ورود'] as $i => $t)<label class="check"><input type="checkbox" onchange="try{localStorage.setItem('pk{{ $b->id }}{{ $i }}',this.checked?1:'')}catch(e){}"><span>{{ $t }}</span></label>@endforeach</div></div></details>@endif
    </div>

    {{-- اطلاعات میزبان: قفل تا پرداخت --}}
    @if($unlocked)
    <div class="card unlocked">
      <h3><x-i3 name="p_key" class="sm"/> اطلاعات میزبان و محل اقامت</h3>
      <div class="bk-host"><img class="avatar lg" src="{{ $host->avatarUrl() }}" alt=""><div class="grow"><b>{{ $host->name }}</b><div class="small muted">میزبان شما</div></div><a class="btn btn-sm" href="tel:{{ $host->phone }}"><x-icon name="phone" class="sm"/> <span class="ltr">{{ fa($host->phone) }}</span></a></div>
      <div class="qrow"><span>آدرس دقیق</span><b style="text-align:left">{{ $p->address }}</b></div>
      @if($p->lat && $p->lng)<div class="row wrap" style="gap:8px;margin-top:8px"><a class="btn btn-sm btn-light" target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $p->lat }},{{ $p->lng }}"><x-icon name="pin" class="sm"/> مسیریابی (نقشه)</a><a class="btn btn-sm btn-light" target="_blank" rel="noopener" href="https://nshn.ir/?lat={{ $p->lat }}&lng={{ $p->lng }}">نشان</a></div>@endif
      @if($p->guide)<div class="guide-box"><b>راهنمای ورود</b><p style="white-space:pre-line;margin:4px 0 0">{{ $p->guide }}</p></div>@endif
    </div>
    @else
    <div class="card locked"><div class="lock-ic"><x-icon name="lock"/></div><div><h3 style="margin:0 0 4px">آدرس دقیق و تلفن میزبان</h3><p class="small muted" style="margin:0">برای امنیت میزبان و مهمان، این اطلاعات پس از پرداخت موفق نمایش داده می‌شود و همزمان به شما پیامک می‌شود.</p></div></div>
    @endif

    @if($unlocked)
    {{-- درخواست خدمات --}}
    <div class="card" id="requests">
      <h3><x-i3 name="p_bell" class="sm"/> درخواست خدمات از میزبان</h3>
      @include('partials.help', ['title' => 'چطور کار می‌کند؟', 'text' => 'هر چیزی که در طول اقامت لازم دارید (ناهار، شام، گوشت و مرغ، نوشیدنی، هیزم و ...) را اینجا بنویسید. اگر میزبان بتواند تأمین کند، همین‌جا پاسخ می‌دهد. هزینه‌ی این موارد مستقیماً بین شما و میزبان تسویه می‌شود و به می‌جا ربطی ندارد.'])
      @foreach($b->serviceRequests as $sr)
        <div class="sr-item {{ $sr->status }}"><div class="row between"><b>{{ config('mija.service_categories')[$sr->category] ?? 'سایر' }}</b><span class="badge {{ ['pending' => 'gold', 'accepted' => 'green', 'declined' => 'red', 'done' => 'dark'][$sr->status] }}">{{ ['pending' => 'در انتظار پاسخ', 'accepted' => 'میزبان تأمین می‌کند', 'declined' => 'میزبان نمی‌تواند', 'done' => 'انجام شد'][$sr->status] }}</span></div><p class="small" style="margin:4px 0 0">{{ $sr->text }}</p>@if($sr->host_reply)<div class="small" style="margin-top:6px;color:var(--brand-d)"><b>پاسخ میزبان:</b> {{ $sr->host_reply }}</div>@endif</div>
      @endforeach
      @if(in_array($b->status, ['confirmed']))
      <form method="post" action="{{ route('guest.bookings.request', $b) }}" class="sr-form">@csrf
        <div class="chips">@foreach(config('mija.service_categories') as $k => $l)<label class="qchip"><input type="radio" name="category" value="{{ $k }}" class="sr" @checked($loop->first)><span>{{ $l }}</span></label>@endforeach</div>
        <textarea class="input" name="text" required minlength="3" maxlength="400" placeholder="مثلاً: شب اول شام کباب برای ۶ نفر، ۲ بسته نوشابه…">{{ old('text') }}</textarea>
        @error('text')<div class="msg-e">{{ $message }}</div>@enderror
        <button class="btn btn-sm">ارسال درخواست به میزبان</button>
      </form>
      @endif
    </div>
    @endif

    {{-- نظر --}}
    <div id="review">
    @if($b->canReview())
    <form class="card" method="post" action="{{ route('guest.bookings.review', $b) }}" enctype="multipart/form-data">@csrf
      <h3><x-i3 name="p_star" class="sm"/> نظر شما درباره‌ی اقامت</h3>
      @include('partials.help', ['title' => 'هدیه‌ی نظر', 'text' => 'نظر شما ابتدا توسط میزبان بررسی می‌شود. پس از انتشار، '.fa(setting('cashback_percent')).'٪ مبلغ رزرو به‌صورت اعتبار به کیف پول شما اضافه می‌شود ('.fa(setting('cashback_days')).' روز قابل استفاده است). متن و عکس‌ها باید محترمانه و مطابق قوانین باشد.'])
      @foreach(['r_clean' => 'پاکیزگی', 'r_accuracy' => 'مطابقت با آگهی', 'r_location' => 'موقعیت', 'r_host' => 'برخورد میزبان', 'r_value' => 'ارزش نسبت به قیمت'] as $k => $l)
        <div class="row between" style="margin-bottom:8px"><span>{{ $l }}</span><select name="{{ $k }}" style="width:auto;padding-block:6px">@foreach([5,4,3,2,1] as $n)<option value="{{ $n }}">{{ fa($n) }} از ۵</option>@endforeach</select></div>
      @endforeach
      <div class="field"><textarea class="input" name="comment" required minlength="10" placeholder="تجربه‌ی خود را بنویسید…">{{ old('comment') }}</textarea>@error('comment')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <label class="check"><input type="checkbox" name="recommend" value="1" checked> این اقامتگاه را به دیگران پیشنهاد می‌کنم</label>
      <div class="field mt"><label>عکس‌های شما (اختیاری، حداکثر ۴)</label><input class="input" type="file" name="photos[]" accept="image/*" multiple>@error('photos.*')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <button class="btn">ثبت نظر</button>
    </form>
    @elseif($b->review)
    @php $rv = $b->review; @endphp
    <div class="card"><h3><x-i3 name="p_star" class="sm"/> نظر شما</h3><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span>
      <p class="small">{{ $rv->comment }}</p>
      @if($rv->photos)<div class="rv-ph">@foreach($rv->photoUrls() as $u)<img src="{{ $u }}" alt="">@endforeach</div>@endif
      <span class="badge {{ ['pending_host' => 'gold', 'published' => 'green', 'host_rejected' => 'orange', 'removed' => 'red'][$rv->status] ?? 'gray' }}">{{ ['pending_host' => 'در انتظار بررسی میزبان', 'published' => 'منتشر شد', 'host_rejected' => 'در حال بررسی نهایی توسط مدیر سایت', 'removed' => 'منتشر نشد'][$rv->status] ?? $rv->status }}</span>
      @if($rv->host_reply)<div class="alert info mt" style="margin-bottom:0"><b>پاسخ میزبان:</b> {{ $rv->host_reply }}</div>@endif
      @if($rv->status === 'removed' && $rv->admin_note)<div class="alert bad mt" style="margin-bottom:0">{{ $rv->admin_note }}</div>@endif
    </div>
    @endif
    </div>
  </div>

  <div class="stack">
    <div class="card inv-mini">
      <h3>صورت‌حساب</h3>
      <div class="qrow"><span>اقامت ({{ fa($b->nights) }} شب)</span><span>{{ toman($bd['base'] ?? 0) }}</span></div>
      @if(($bd['extra'] ?? 0) > 0)<div class="qrow"><span>نفر اضافه</span><span>{{ toman($bd['extra']) }}</span></div>@endif
      @if(($bd['cleaning'] ?? 0) > 0)<div class="qrow"><span>نظافت</span><span>{{ toman($bd['cleaning']) }}</span></div>@endif
      @if(($bd['long_discount'] ?? 0) > 0)<div class="qrow"><span>تخفیف اقامت طولانی</span><span class="off">−{{ toman($bd['long_discount']) }}</span></div>@endif
      @if(($bd['offer_pct'] ?? 0) > 0)<div class="qrow"><span>تخفیف پیشنهادی میزبان ({{ fa($bd['offer_pct']) }}٪)</span><span class="off">−{{ toman(($bd['total_before'] ?? $b->total) - $b->total) }}</span></div>@endif
      @if($b->discount > 0)<div class="qrow"><span>کد تخفیف {{ $b->coupon_code }}</span><span class="off">−{{ toman($b->discount) }}</span></div>@endif
      <div class="qrow total"><span>مبلغ کل</span><span>{{ toman($b->total) }}</span></div>
      @if($b->refund_amount > 0)<div class="alert ok mt" style="margin-bottom:0"><x-icon name="check"/>{{ toman($b->refund_amount) }} به کیف پول شما بازگشت داده شد.</div>@endif
      @if($b->canPay())<a class="btn btn-lg btn-block mt" href="{{ route('booking.invoice', $b) }}"><x-icon name="lock"/> پرداخت {{ toman($b->total) }}</a>@endif
      @if($cardPending)<div class="alert warn mt" style="margin-bottom:0"><x-icon name="clock"/>پرداخت کارت‌به‌کارت شما ({{ toman($cardPending->amount) }}) در انتظار تأیید است.</div>@endif
    </div>

    @if($b->is_installment && $b->installments->count())
    <div class="card"><h3><x-i3 name="p_calendar" class="sm"/> برنامه‌ی اقساط</h3>
      @foreach($b->installments as $in)
        <div class="inst {{ $in->status }}"><span class="n">{{ fa($in->seq) }}</span><div class="grow"><b>{{ toman($in->amount) }}</b><div class="xs muted">سررسید {{ jdate($in->due_on, 'j F Y') }}</div></div>
          @if($in->status === 'paid')<span class="badge green">پرداخت شد</span>
          @elseif($b->status === 'confirmed' && $in->seq > 1)<form method="post" action="{{ route('guest.installments.pay', $in) }}" class="row" style="gap:6px">@csrf<select name="method" style="width:auto;padding-block:5px;font-size:12px"><option value="online">آنلاین</option><option value="wallet">کیف پول</option>@if(\App\Support\Payments::cardEnabled())<option value="card" disabled>کارت‌به‌کارت (از فاکتور)</option>@endif</select><button class="btn btn-sm">پرداخت</button></form>
          @else<span class="badge gold">در انتظار</span>@endif</div>
      @endforeach
      <p class="xs muted" style="margin:8px 0 0">در صورت پرداخت‌نشدن اقساط تا سررسید، رزرو لغو و مبلغ پرداختی به کیف پول شما بازمی‌گردد.</p>
    </div>
    @endif

    @if(in_array($b->status, ['pending_host','awaiting_payment','payment_review','offered','confirmed']))
    <form method="post" action="{{ route('guest.bookings.cancel', $b) }}" onsubmit="return confirm('{{ $b->status === 'confirmed' ? 'در صورت لغو، '.fa($b->refundPercent()).'٪ مبلغ به کیف پول بازگردانده می‌شود. ادامه می‌دهید؟' : 'رزرو لغو شود؟' }}')">@csrf
      <button class="btn btn-ghost btn-block">لغو رزرو @if($b->status === 'confirmed')(بازگشت {{ fa($b->refundPercent()) }}٪ — سیاست {{ $p->policy()['name'] }})@endif</button></form>
    @endif
  </div>
</div>

@include('partials.chat', ['b' => $b])
@endsection
