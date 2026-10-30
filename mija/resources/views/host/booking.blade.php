@extends('layouts.panel')
@section('title', 'رزرو '.$b->code)
@section('content')
@php
  $p = $b->property; $g = $b->guest; $unlocked = $b->detailsUnlocked();
  [$at, $bad] = $b->stepState('host');
  $steps = [['درخواست رسید', 'receipt'], ['پاسخ شما', 'check'], ['پرداخت مهمان', 'card'], ['رزرو قطعی', 'shield'], ['اقامت', 'home'], ['امتیاز به مهمان', 'star']];
@endphp
<div class="page-h"><div><a href="{{ route('host.bookings') }}" class="small muted">← رزروها</a><h1>رزرو <span class="ltr">{{ $b->code }}</span></h1></div><span class="badge {{ $b->statusColor() }}" style="font-size:13px;padding:5px 14px">{{ $b->statusLabel() }}</span></div>

@include('partials.stepper', ['steps' => $steps, 'at' => $at, 'bad' => $bad])

@if($b->status === 'pending_host')
<div class="now-card hot"><div class="grow">
  <h3><x-i3 name="p_bell" class="sm"/> درخواست جدید؛ پاسخ شما لازم است</h3>
  <p>اگر تأیید کنید، مهمان {{ fa(setting('payment_window_minutes')) }} دقیقه برای پرداخت فرصت دارد و آن روزها برایش نگه داشته می‌شود. اگر نمی‌توانید بپذیرید، می‌توانید روز دیگری با تخفیف پیشنهاد دهید.</p>
  <div class="row wrap" style="gap:8px"><form method="post" action="{{ route('host.bookings.accept', $b) }}">@csrf<button class="btn btn-lg btn-green"><x-icon name="check"/> تأیید؛ این روزها خالی است</button></form>
    <button type="button" class="btn btn-lg btn-light" data-toggle="#reject-box"><x-icon name="x"/> رد درخواست</button></div>
</div>@include('partials.countdown', ['until' => $b->respond_by, 'total' => setting('host_response_minutes') * 60, 'label' => 'فرصت پاسخ شما'])</div>

<form method="post" action="{{ route('host.bookings.reject', $b) }}" class="card" id="reject-box" hidden>@csrf
  <h3>رد درخواست</h3>
  <div class="field"><label>دلیل (اختیاری)</label><input class="input" name="reason" placeholder="مثلاً: این تاریخ را برای خانواده‌ی خودم نگه داشته‌ام" value="{{ old('reason') }}">@error('reason')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <label class="check"><input type="checkbox" name="offer" value="1" id="offer-chk" @checked(old('offer'))><div><b><x-i3 name="p_gift" class="sm"/> می‌خواهم روز دیگری با تخفیف به مهمان پیشنهاد دهم</b><div class="xs muted" style="font-weight:500">اگر مهمان بپذیرد، رزرو با همان روز جدید و قیمت تخفیف‌دار ادامه پیدا می‌کند؛ اگر نپذیرد، رزرو بسته می‌شود.</div></div></label>
  <div id="offer-fields" class="grid2 mt" hidden>
    <div class="field"><label>روز جایگزین (هم‌طول رزرو، فقط روزهای خالی شما)</label>
      <select name="offer_window">@forelse($offerOptions as $o)<option value="{{ $o['value'] }}">{{ $o['label'] }}</option>@empty<option value="">روز خالی مناسبی در ۷۵ روز آینده پیدا نشد</option>@endforelse</select>@error('offer_window')<div class="msg-e">{{ $message }}</div>@enderror @error('offer_check_in')<div class="msg-e">{{ $message }}</div>@enderror</div>
    <div class="field"><label>درصد تخفیف برای مهمان</label><select name="offer_discount">@foreach([5,10,15,20,25,30,40] as $d)<option value="{{ $d }}" @selected($d === 15)>{{ fa($d) }}٪</option>@endforeach</select><div class="help">این تخفیف از دریافتی شما کم می‌شود.</div></div>
  </div>
  <button class="btn btn-danger mt">ثبت پاسخ</button>
</form>
<script>(function(){var t=document.querySelector('[data-toggle]');t&&t.addEventListener('click',function(){var b=document.querySelector(t.dataset.toggle);b.hidden=!b.hidden;b.scrollIntoView({behavior:'smooth',block:'center'})});var c=document.getElementById('offer-chk'),f=document.getElementById('offer-fields');function u(){f.hidden=!c.checked}c&&c.addEventListener('change',u);u();@if($errors->any())document.getElementById('reject-box').hidden=false;@endif})()</script>
@elseif($b->status === 'awaiting_payment')
<div class="now-card"><div class="grow"><h3><x-i3 name="p_hourglass" class="sm"/> منتظر پرداخت مهمان</h3><p>این روزها تا پایان مهلت برای مهمان نگه داشته شده است.</p></div>@include('partials.countdown', ['until' => $b->pay_by, 'total' => setting('payment_window_minutes') * 60, 'label' => 'فرصت پرداخت مهمان'])</div>
@elseif($b->status === 'offered')
<div class="now-card"><div class="grow"><h3><x-i3 name="p_gift" class="sm"/> پیشنهاد شما برای مهمان ارسال شد</h3><p>{{ jdate($b->offer_check_in, 'j F') }} تا {{ jdate($b->offer_check_out, 'j F') }} با {{ fa($b->offer_discount) }}٪ تخفیف — منتظر پاسخ مهمان.</p></div>@include('partials.countdown', ['until' => $b->offer_expires_at, 'total' => setting('offer_minutes') * 60, 'label' => 'مهلت پاسخ مهمان'])</div>
@elseif($b->status === 'payment_review')
<div class="alert warn"><x-icon name="clock"/>مهمان با کارت‌به‌کارت پرداخت کرده و پرداخت در انتظار تأیید مدیر سایت است.</div>
@endif

<div class="bk-grid">
  <div class="stack">
    <div class="card">
      <h3>{{ $p->title }}</h3>
      <div class="bk-facts">
        <div><x-icon name="calendar" class="sm"/><small>ورود</small><b>{{ jdate($b->check_in, 'l j F') }}</b></div>
        <div><x-icon name="calendar" class="sm"/><small>خروج</small><b>{{ jdate($b->check_out, 'l j F') }}</b></div>
        <div><x-icon name="clock" class="sm"/><small>مدت</small><b>{{ fa($b->nights) }} شب</b></div>
        <div><x-icon name="users" class="sm"/><small>مسافران</small><b>{{ fa($b->adults) }} بزرگسال@if($b->children) + {{ fa($b->children) }} کودک @endif</b></div>
      </div>
      @if($b->guest_note)<div class="alert info mt" style="margin-bottom:0"><x-icon name="chat"/>پیام مهمان: {{ $b->guest_note }}</div>@endif
      <div class="row wrap" style="gap:8px;margin-top:12px"><a class="btn btn-sm btn-light" href="{{ route('booking.invoice', $b) }}"><x-icon name="receipt" class="sm"/> فاکتور</a></div>
    </div>

    {{-- مهمان و پیشینه --}}
    <div class="card">
      <h3><x-i3 name="user" class="sm"/> مهمان و پیشینه‌ی او</h3>
      <div class="bk-host"><a href="{{ route('host.guest', $g) }}"><img class="avatar lg" src="{{ $g->avatarUrl() }}" alt=""></a><div class="grow"><a href="{{ route('host.guest', $g) }}"><b>{{ $g->name }}</b></a><div class="small muted">عضو از {{ jdate($gstat['since'], 'F Y') }}</div></div>
        @if($unlocked)<a class="btn btn-sm" href="tel:{{ $g->phone }}"><x-icon name="phone" class="sm"/> <span class="ltr">{{ fa($g->phone) }}</span></a>@else<span class="badge gray"><x-icon name="lock" class="sm"/> شماره پس از پرداخت</span>@endif</div>
      <div class="bk-facts"><div><small>سفرهای موفق</small><b>{{ fa($gstat['trips']) }}</b></div><div><small>امتیاز میزبان‌ها</small><b>{{ $gstat['rating'] ? fa($gstat['rating']).' از ۵' : 'بدون سابقه' }}</b></div><div><small>لغو توسط مهمان</small><b>{{ fa($gstat['cancels']) }}</b></div><div><small>سطح</small><b>{{ $g->level()['level']['name'] }}</b></div></div>
      @if($greviews->count())
        <h4 style="margin:14px 0 6px;font-size:13px">نظر میزبان‌های دیگر <span class="muted xs">(فقط میزبان‌ها می‌بینند)</span></h4>
        @foreach($greviews as $gr)<div class="sr-item"><div class="row between"><span class="stars"><x-icon name="star"/>{{ fa($gr->rating) }}</span><span class="xs muted">{{ jdate($gr->created_at, 'j F Y') }}</span></div>@if($gr->tags)<div class="row wrap" style="gap:4px;margin-top:4px">@foreach($gr->tags as $t)<span class="badge">{{ $t }}</span>@endforeach</div>@endif @if($gr->comment)<p class="small" style="margin:4px 0 0">{{ $gr->comment }}</p>@endif</div>@endforeach
      @else<p class="small muted" style="margin:10px 0 0">هنوز میزبانی برای این مهمان نظر ثبت نکرده است.</p>@endif
    </div>

    @if($b->serviceRequests->count())
    <div class="card" id="requests"><h3><x-i3 name="p_bell" class="sm"/> درخواست‌های مهمان</h3>
      <p class="xs muted">هزینه‌ی این موارد را مستقیماً با مهمان حساب کنید؛ می‌جا در آن نقشی ندارد.</p>
      @foreach($b->serviceRequests as $sr)
      <div class="sr-item {{ $sr->status }}"><div class="row between"><b>{{ config('mija.service_categories')[$sr->category] ?? 'سایر' }}</b><span class="badge {{ ['pending' => 'gold', 'accepted' => 'green', 'declined' => 'red', 'done' => 'dark'][$sr->status] }}">{{ ['pending' => 'در انتظار پاسخ', 'accepted' => 'می‌توانم تأمین کنم', 'declined' => 'نمی‌توانم', 'done' => 'انجام شد'][$sr->status] }}</span></div>
        <p class="small" style="margin:4px 0 8px">{{ $sr->text }}</p>
        <form method="post" action="{{ route('host.requests.reply', $sr) }}" class="row wrap" style="gap:6px">@csrf<input class="input" name="host_reply" placeholder="پاسخ یا توضیح (مثلاً قیمت)" value="{{ $sr->host_reply }}" style="flex:1;min-width:160px"><button class="btn btn-sm btn-green" name="status" value="accepted">می‌توانم</button><button class="btn btn-sm btn-ghost" name="status" value="declined">نمی‌توانم</button><button class="btn btn-sm btn-light" name="status" value="done">انجام شد</button></form></div>
      @endforeach
    </div>
    @endif

    @if($b->status === 'completed' && ! $b->guestReview)
    <form class="card" method="post" action="{{ route('host.bookings.rate', $b) }}">@csrf
      <h3><x-i3 name="p_star" class="sm"/> به مهمان امتیاز دهید</h3>
      @include('partials.help', ['title' => 'این امتیاز چه کاربردی دارد؟', 'text' => 'امتیاز و نظر شما فقط برای میزبان‌های دیگر نمایش داده می‌شود تا هنگام دریافت درخواست رزرو این مهمان، راحت‌تر تصمیم بگیرند. مهمان آن را نمی‌بیند.'])
      <div class="field"><label>امتیاز</label><select name="rating">@foreach([5,4,3,2,1] as $n)<option value="{{ $n }}">{{ fa($n) }} از ۵</option>@endforeach</select></div>
      <div class="chips" style="margin-bottom:10px">@foreach(config('mija.guest_tags') as $t)<label class="qchip"><input type="checkbox" name="tags[]" value="{{ $t }}"><span>{{ $t }}</span></label>@endforeach</div>
      <div class="field"><textarea class="input" name="comment" maxlength="400" placeholder="توضیح کوتاه (اختیاری)"></textarea>@error('comment')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <button class="btn">ثبت امتیاز</button>
    </form>
    @elseif($b->guestReview)<div class="card"><h3>امتیاز شما به مهمان</h3><span class="stars"><x-icon name="star"/>{{ fa($b->guestReview->rating) }}</span> @foreach($b->guestReview->tags ?? [] as $t)<span class="badge">{{ $t }}</span>@endforeach</div>@endif

    @if($b->review)<div class="card"><h3>نظر مهمان</h3><span class="stars"><x-icon name="star"/>{{ fa($b->review->rating) }}</span><p class="small">{{ $b->review->comment }}</p><a class="btn btn-sm btn-light" href="{{ route('host.reviews') }}">مدیریت نظرات</a></div>@endif
  </div>

  <div class="stack">
    <div class="card"><h3>مالی</h3>
      <div class="qrow"><span>مبلغ پرداختی مهمان</span><span>{{ toman($b->total) }}</span></div>
      <div class="qrow"><span>کارمزد و کمیسیون می‌جا</span><span>−{{ toman($b->commission) }}</span></div>
      <div class="qrow total"><span>دریافتی شما</span><span>{{ toman($b->host_payout) }}</span></div>
      <p class="xs muted">{{ $b->settled_at ? 'به کیف پول شما اضافه شد ('.jdate($b->settled_at, 'j F').'). تسویه‌ی کیف پول با واریز به حساب شما '.fa(setting('payout_hours_min')).' تا '.fa(setting('payout_hours_max')).' ساعت زمان می‌برد.' : 'پس از ورود مهمان و پرداخت کامل، به کیف پول شما اضافه می‌شود.' }}</p>
      @if($b->is_installment)<div class="alert info"><x-icon name="info"/>پرداخت این رزرو اقساطی است؛ سهم شما پس از پرداخت کامل اقساط واریز می‌شود.</div>@endif
    </div>
    @if($unlocked)<div class="card unlocked"><h3><x-i3 name="p_pin" class="sm"/> محل اقامتگاه</h3><p class="small muted" style="margin:0">{{ $p->address }}</p></div>@endif
    @if(in_array($b->status, ['confirmed','awaiting_payment','payment_review']))
    <form method="post" action="{{ route('host.bookings.cancel', $b) }}" class="card" onsubmit="return confirm('لغو رزرو توسط میزبان؟ مبلغ کامل به مهمان بازگردانده می‌شود.')">@csrf
      <input class="input" name="reason" placeholder="دلیل لغو" style="margin-bottom:8px"><button class="btn btn-ghost btn-block">لغو رزرو توسط میزبان</button></form>
    @endif
  </div>
</div>
@include('partials.chat', ['b' => $b])
@endsection
