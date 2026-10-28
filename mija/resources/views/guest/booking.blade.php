@extends('layouts.panel')
@section('title', 'رزرو '.$b->code)
@section('content')
@php $p = $b->property; $b->loadMissing('property'); @endphp
<div class="page-h"><div><a href="{{ route('guest.bookings') }}" class="small muted">← سفرهای من</a><h1>رزرو <span class="ltr">{{ $b->code }}</span></h1></div><span class="badge {{ $b->statusColor() }}" style="font-size:14px;padding:6px 16px">{{ $b->statusLabel() }}</span></div>

@if($b->status === 'pending_host')<div class="alert warn"><x-icon name="clock"/>درخواست شما برای میزبان ارسال شده است. مهلت پاسخ تا {{ jdate($b->respond_by, 'j F ساعت H:i') }}.</div>@endif
@if($b->canPay())<div class="alert warn"><x-icon name="clock"/>برای قطعی شدن رزرو تا {{ jdate($b->pay_by, 'H:i') }} پرداخت را انجام دهید.</div>@endif
@if(in_array($b->status, ['rejected','cancelled_host']) && $b->cancel_reason)<div class="alert bad"><x-icon name="info"/>{{ $b->cancel_reason }}</div>@endif

<div class="grid2" style="gap:18px;align-items:start">
  <div class="card">
    <a href="{{ route('property', $p) }}"><img src="{{ $p->cover() }}" alt="" style="width:100%;height:190px;object-fit:cover;border-radius:16px"></a>
    <h3 style="margin-top:12px"><a href="{{ route('property', $p) }}">{{ $p->title }}</a></h3>
    <div class="muted small">{{ $p->province }}، {{ $p->city }}</div>
    <hr class="divider" style="margin:14px 0">
    <div class="qrow"><span>ورود</span><b>{{ jdate($b->check_in, 'l j F Y') }} — {{ fa($p->check_in_time) }}</b></div>
    <div class="qrow"><span>خروج</span><b>{{ jdate($b->check_out, 'l j F Y') }} — {{ fa($p->check_out_time) }}</b></div>
    <div class="qrow"><span>مدت</span><b>{{ fa($b->nights) }} شب</b></div>
    <div class="qrow"><span>مسافران</span><b>{{ fa($b->adults) }} بزرگسال @if($b->children)، {{ fa($b->children) }} کودک @endif</b></div>
    <div class="qrow"><span>میزبان</span><b>{{ $p->host->name }}</b></div>
    @if($b->status === 'confirmed')<div class="qrow"><span>تلفن میزبان</span><b class="ltr">{{ $p->host->phone }}</b></div>@if($p->address)<div class="qrow"><span>آدرس</span><b>{{ $p->address }}</b></div>@endif @endif
    <div class="qrow"><span>سیاست لغو</span><b>{{ $p->policy()['name'] }}</b></div>
  </div>

  <div class="stack">
    <div class="card">
      <h3>صورت‌حساب</h3>
      @php $bd = $b->breakdown ?? []; @endphp
      <div class="qrow"><span>اقامت ({{ fa($b->nights) }} شب)</span><span>{{ toman($bd['base'] ?? 0) }}</span></div>
      @if(($bd['extra'] ?? 0) > 0)<div class="qrow"><span>نفر اضافه</span><span>{{ toman($bd['extra']) }}</span></div>@endif
      @if(($bd['cleaning'] ?? 0) > 0)<div class="qrow"><span>نظافت</span><span>{{ toman($bd['cleaning']) }}</span></div>@endif
      @if(($bd['long_discount'] ?? 0) > 0)<div class="qrow"><span>تخفیف اقامت طولانی</span><span class="off">−{{ toman($bd['long_discount']) }}</span></div>@endif
      @if($b->discount > 0)<div class="qrow"><span>کد تخفیف {{ $b->coupon_code }}</span><span class="off">−{{ toman($b->discount) }}</span></div>@endif
      @if($b->service_fee > 0)<div class="qrow"><span>کارمزد خدمات</span><span>{{ toman($b->service_fee) }}</span></div>@endif
      <div class="qrow total"><span>مبلغ کل</span><span>{{ toman($b->total) }}</span></div>
      @if($b->refund_amount > 0)<div class="alert ok mt" style="margin-bottom:0"><x-icon name="check"/>{{ toman($b->refund_amount) }} به کیف پول شما بازگشت داده شد.</div>@endif

      @if($b->canPay())
        <div class="stack mt">
          <form method="post" action="{{ route('guest.bookings.pay', $b) }}">@csrf<button class="btn btn-lg btn-block"><x-icon name="lock"/> پرداخت آنلاین {{ toman($b->total) }}</button></form>
          @if(auth()->user()->wallet >= $b->total)<form method="post" action="{{ route('guest.bookings.pay', $b) }}">@csrf<input type="hidden" name="method" value="wallet"><button class="btn btn-gold btn-block"><x-icon name="wallet"/> پرداخت از کیف پول ({{ toman(auth()->user()->wallet) }})</button></form>@endif
        </div>
      @endif

      @if(in_array($b->status, ['pending_host','awaiting_payment','confirmed']))
        <form method="post" action="{{ route('guest.bookings.cancel', $b) }}" class="mt" onsubmit="return confirm('{{ $b->status === 'confirmed' ? 'در صورت لغو، '.fa($b->refundPercent()).'٪ مبلغ به کیف پول بازگردانده می‌شود. ادامه می‌دهید؟' : 'رزرو لغو شود؟' }}')">@csrf
          <button class="btn btn-ghost btn-block">لغو رزرو @if($b->status === 'confirmed')(بازگشت {{ fa($b->refundPercent()) }}٪)@endif</button></form>
      @endif
    </div>

    @if($b->canReview())
    <form class="card" method="post" action="{{ route('guest.bookings.review', $b) }}">@csrf
      <h3>نظر شما درباره‌ی اقامت</h3>
      @foreach(['r_clean' => 'پاکیزگی', 'r_accuracy' => 'مطابقت با آگهی', 'r_location' => 'موقعیت', 'r_host' => 'برخورد میزبان', 'r_value' => 'ارزش نسبت به قیمت'] as $k => $l)
        <div class="row between" style="margin-bottom:8px"><span>{{ $l }}</span><select name="{{ $k }}" style="width:auto;padding-block:6px">@foreach([5,4,3,2,1] as $n)<option value="{{ $n }}">{{ fa($n) }} ★</option>@endforeach</select></div>
      @endforeach
      <div class="field"><textarea class="input" name="comment" placeholder="تجربه‌ی خود را بنویسید…"></textarea></div>
      <button class="btn">ثبت نظر</button>
    </form>
    @elseif($b->review)
    <div class="card"><h3>نظر شما</h3><span class="stars"><x-icon name="star"/>{{ fa($b->review->rating) }}</span><p class="small">{{ $b->review->comment }}</p>@if($b->review->host_reply)<div class="alert info"><b>پاسخ میزبان:</b> {{ $b->review->host_reply }}</div>@endif</div>
    @endif
  </div>
</div>

@include('partials.chat', ['b' => $b])
@endsection
