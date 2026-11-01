@extends('layouts.app')
@section('title', 'فاکتور '.$b->code)
@section('nofooter', '1')
@section('bare')
@php
  $p = $b->property; $host = $p->host; $unlocked = $b->detailsUnlocked(); $bd = $b->breakdown ?? [];
  $paid = $b->isPaid();
  $first = $b->installments->firstWhere('seq', 1);
  $canPay = $isGuest && $b->canPay();
  $inst = $canPay && auth()->user()->canInstallment() && now()->diffInDays($b->check_in, false) >= (int) setting('installment_min_days');
  $partsN = max(2, (int) setting('installment_parts'));
  $firstAmt = intdiv($b->total * (int) setting('installment_first_pct'), 100);
  $wallet = auth()->user()->wallet;
@endphp
<div class="container inv-wrap">
  @include('partials.flash')
  @if($isGuest)@include('partials.stepper', ['steps' => [['ثبت رزرو', 'receipt'], ['تأیید میزبان', 'check'], ['پرداخت', 'card'], ['رزرو قطعی', 'shield'], ['اقامت', 'home'], ['ثبت نظر', 'star']], 'at' => $b->stepState('guest')[0], 'bad' => $b->stepState('guest')[1]])@endif

  <div class="inv-grid {{ $canPay || ($isGuest && ! $paid && $b->status === 'payment_review') ? '' : 'solo' }}">
    <article class="inv" id="invoice">
      <header class="inv-h">
        <img src="{{ asset('img/mija-logo-h.png') }}" alt="می‌جا" height="38">
        <div class="inv-no"><small>شماره‌ی فاکتور</small><b class="ltr">{{ $b->code }}</b><small>{{ jdate($b->created_at, 'j F Y — H:i') }}</small></div>
      </header>
      @if($paid)<div class="stamp ok"><x-icon name="check"/>پرداخت‌شده</div>@elseif(in_array($b->status, ['awaiting_payment', 'payment_review', 'pending_host']))<div class="stamp wait">{{ $b->status === 'payment_review' ? 'در حال بررسی' : 'پرداخت‌نشده' }}</div>@else<div class="stamp bad">{{ $b->statusLabel() }}</div>@endif

      <div class="inv-parties">
        <div><small>مهمان</small><b>{{ $b->guest->name }}</b>@if($unlocked)<span class="ltr">{{ fa($b->guest->phone) }}</span>@else<span class="muted xs">شماره پس از پرداخت</span>@endif</div>
        <div><small>میزبان</small><b>{{ $host->name }}</b>@if($unlocked)<span class="ltr">{{ fa($host->phone) }}</span>@else<span class="muted xs">شماره پس از پرداخت</span>@endif</div>
      </div>

      <div class="inv-prop"><img src="{{ $p->cover() }}" alt=""><div><b>{{ $p->title }}</b><div class="small muted">{{ $p->province }}، {{ $p->city }} · {{ $p->typeLabel() }}</div>@if($unlocked)<div class="small" style="margin-top:4px"><x-icon name="pin" class="sm"/> {{ $p->address }}</div>@endif</div></div>

      <div class="inv-dates">
        <div><small>ورود</small><b>{{ jdate($b->check_in, 'l j F Y') }}</b><span>{{ fa($p->check_in_time) }}</span></div>
        <div><small>خروج</small><b>{{ jdate($b->check_out, 'l j F Y') }}</b><span>{{ fa($p->check_out_time) }}</span></div>
        <div><small>مدت</small><b>{{ fa($b->nights) }} شب</b><span>{{ fa($b->adults) }} بزرگسال@if($b->children) · {{ fa($b->children) }} کودک @endif</span></div>
      </div>

      <table class="inv-t">
        <thead><tr><th>شرح</th><th>مبلغ</th></tr></thead>
        <tbody>
          @foreach(($bd['lines'] ?? []) as $ln)<tr class="ln"><td>{{ jdate($ln['date'], 'l j F') }} @if(($ln['flash'] ?? 0) > 0)<span class="badge red" style="padding:0 8px">{{ fa($ln['flash']) }}٪ تخفیف فوری</span>@endif</td><td>{{ toman($ln['price']) }}</td></tr>@endforeach
          <tr><td>جمع اقامت ({{ fa($b->nights) }} شب)</td><td>{{ toman($bd['base'] ?? 0) }}</td></tr>
          @if(($bd['extra'] ?? 0) > 0)<tr><td>هزینه‌ی نفر اضافه</td><td>{{ toman($bd['extra']) }}</td></tr>@endif
          @if(($bd['cleaning'] ?? 0) > 0)<tr><td>نظافت</td><td>{{ toman($bd['cleaning']) }}</td></tr>@endif
          @if(($bd['long_discount'] ?? 0) > 0)<tr class="off"><td>تخفیف اقامت طولانی ({{ fa($bd['long_pct']) }}٪)</td><td>−{{ toman($bd['long_discount']) }}</td></tr>@endif
          @if(($bd['offer_pct'] ?? 0) > 0)<tr class="off"><td>تخفیف پیشنهادی میزبان ({{ fa($bd['offer_pct']) }}٪)</td><td>−{{ toman(($bd['total_before'] ?? $b->total) - $b->total) }}</td></tr>@endif
          @if($b->discount > 0)<tr class="off"><td>کد تخفیف {{ $b->coupon_code }}</td><td>−{{ toman($b->discount) }}</td></tr>@endif
        </tbody>
        <tfoot><tr class="tot"><td>مبلغ قابل پرداخت</td><td>{{ toman($b->total) }}</td></tr></tfoot>
      </table>
      @if($b->is_installment && $b->installments->count())
        <div class="inv-inst">@foreach($b->installments as $in)<div class="{{ $in->status }}"><b>قسط {{ fa($in->seq) }}</b><span>{{ toman($in->amount) }}</span><small>{{ $in->status === 'paid' ? 'پرداخت شد' : 'سررسید '.jdate($in->due_on, 'j F') }}</small></div>@endforeach</div>
      @endif
      <p class="inv-note">سیاست لغو: {{ $p->policy()['name'] }} — {{ $p->policy()['desc'] }}</p>
      <div class="inv-actions noprint"><button class="btn btn-light btn-sm" onclick="print()"><x-icon name="download" class="sm"/> چاپ / ذخیره PDF</button><a class="btn btn-light btn-sm" href="{{ $isGuest ? route('guest.bookings.show', $b) : (auth()->user()->isHost() ? route('host.bookings.show', $b) : route('admin.bookings.show', $b)) }}">بازگشت به رزرو</a></div>
    </article>

    {{-- پنل پرداخت --}}
    @if($canPay)
    <aside class="pay noprint">
      <div class="pay-card">
        @include('partials.countdown', ['until' => $b->pay_by, 'total' => setting('payment_window_minutes') * 60, 'label' => 'فرصت پرداخت'])
        <h3>پرداخت رزرو</h3>
        <form method="post" action="{{ route('guest.bookings.pay', $b) }}" enctype="multipart/form-data" id="pay-form">@csrf
          @if($inst)
          <div class="pm-title">طرح پرداخت</div>
          <div class="plan">
            <label class="pm"><input type="radio" name="plan" value="full" checked><div class="pm-b"><b>پرداخت کامل</b><small>{{ toman($b->total) }}</small></div></label>
            <label class="pm"><input type="radio" name="plan" value="installment"><div class="pm-b"><b>پرداخت اقساطی <span class="badge gold" style="padding:0 8px"><x-i3 name="p_crown" class="sm"/>ویژه شما</span></b><small>الان {{ toman($firstAmt) }} · بقیه در {{ fa($partsN - 1) }} قسط تا ۳ روز مانده به ورود</small></div></label>
          </div>
          <p class="xs muted" style="margin:6px 0 10px">این گزینه به‌دلیل امتیاز بالای شما در می‌جا برایتان فعال شده است.</p>
          @endif
          <div class="pm-title">روش پرداخت</div>
          @foreach($onlineList as $gi => $gk)
          <label class="pm"><input type="radio" name="method" value="{{ $gk }}" @checked($gi === 0)><div class="pm-b"><b><x-icon name="card" class="sm"/> {{ $gk === 'sandbox' ? 'درگاه آزمایشی (تست)' : 'پرداخت آنلاین با '.\App\Support\Gateways::label($gk) }}</b><small>{{ $gk === 'sandbox' ? 'فقط برای آزمایش — پولی کم نمی‌شود' : 'با تمام کارت‌های عضو شتاب' }}</small></div></label>
          @endforeach
          @if($walletOn)<label class="pm @if($wallet < $b->total && ! $inst) dis @endif"><input type="radio" name="method" value="wallet" @checked(empty($onlineList))><div class="pm-b"><b><x-icon name="wallet" class="sm"/> کیف پول</b><small>موجودی: {{ toman($wallet) }}</small></div></label>@endif
          @if(empty($onlineList) && ! $walletOn && ! $cardOn)<div class="alert warn"><x-icon name="info"/>فعلاً هیچ روش پرداختی فعال نیست؛ لطفاً با پشتیبانی تماس بگیرید.</div>@endif
          @if($cardOn)
          <label class="pm"><input type="radio" name="method" value="card"><div class="pm-b"><b><x-icon name="receipt" class="sm"/> کارت‌به‌کارت</b><small>پس از واریز، کد پیگیری را ثبت کنید؛ با تأیید مدیر رزرو قطعی می‌شود</small></div></label>
          <div class="pm-card" hidden>
            @foreach($cards as $i => $c)
              <label class="bankcard"><input type="radio" name="card_to" value="{{ $c['number'] }}" @checked($i === 0)><div><b class="ltr" id="cn{{ $i }}">{{ \App\Support\Payments::fmtCard($c['number']) }}</b><small>{{ $c['holder'] }} @if($c['bank']) · {{ $c['bank'] }} @endif</small></div><button type="button" class="btn btn-sm btn-light" data-copy="#cn{{ $i }}">کپی</button></label>
            @endforeach
            <input class="input" name="tracking_code" placeholder="کد پیگیری یا ۴ رقم آخر کارت مبدأ" value="{{ old('tracking_code') }}">
            <label class="xs muted">تصویر رسید (اختیاری)</label><input class="input" type="file" name="receipt" accept="image/*">
          </div>
          @endif
          @error('method')<div class="msg-e">{{ $message }}</div>@enderror @error('pay')<div class="msg-e">{{ $message }}</div>@enderror @error('tracking_code')<div class="msg-e">{{ $message }}</div>@enderror
          <button class="btn btn-lg btn-block" id="pay-btn"><x-icon name="lock"/> <span id="pay-lbl">پرداخت {{ toman($b->total) }}</span></button>
        </form>
        <ul class="vp-trust"><li><x-i3 name="p_shield" class="sm"/>پرداخت امن</li><li><x-i3 name="p_lock" class="sm"/>اطلاعات محفوظ</li><li><x-i3 name="p_phone" class="sm"/>پشتیبانی</li></ul>
      </div>
    </aside>
    <script>
    (function(){var f=document.getElementById('pay-form');if(!f)return;var card=f.querySelector('.pm-card'),lbl=document.getElementById('pay-lbl');
    var full={{ $b->total }},first={{ $inst ? $firstAmt : 0 }},fa=function(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,'٬').replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d]})};
    function upd(){var m=(f.querySelector('input[name=method]:checked')||{}).value,pl=(f.querySelector('input[name=plan]:checked')||{value:'full'}).value;
    if(card)card.hidden=m!=='card';var amt=pl==='installment'?first:full;lbl.textContent=(m==='card'?'ثبت پرداخت کارت‌به‌کارت ':'پرداخت ')+fa(amt)+' تومان';
    f.querySelectorAll('.pm').forEach(function(l){var i=l.querySelector('input');l.classList.toggle('on',i.checked)})}
    f.addEventListener('change',upd);upd()})();
    </script>
    @elseif($isGuest && ! $paid && $b->status === 'payment_review')
    <aside class="pay noprint"><div class="pay-card"><h3>پرداخت در حال بررسی</h3><p class="small muted">پرداخت کارت‌به‌کارت شما ثبت شده و پس از تأیید مدیر، رزرو قطعی می‌شود.</p></div></aside>
    @endif
  </div>
</div>
@endsection
