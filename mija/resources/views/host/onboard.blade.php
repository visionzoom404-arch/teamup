@extends('layouts.panel')
@section('title', 'تکمیل اطلاعات میزبان')
@section('content')
@php $step = (int) $step; @endphp
<div class="page-h"><div><h1>پذیرش میزبان</h1><div class="muted">قبل از ثبت اقامتگاه، اطلاعات هویتی و حساب بانکی خود را کامل کنید.</div></div></div>
@if(session('warn'))<div class="alert warn"><x-icon name="info"/>{{ session('warn') }}</div>@endif
@include('partials.stepper', ['steps' => [['اطلاعات هویتی', 'user'], ['کارت و شبا', 'card'], ['ثبت اقامتگاه', 'home']], 'at' => min($step, 3)])
@include('partials.help', ['title' => 'چرا این اطلاعات لازم است؟', 'text' => 'طبق قوانین کشور و برای امنیت مهمانان، هویت میزبان باید ثبت شود. شماره کارت و شبا هم فقط برای واریز تسویه‌ی درآمد شماست. این اطلاعات نزد مدیر سایت محفوظ می‌ماند و هرگز به مهمان نمایش داده نمی‌شود.', 'open' => true])

@if($step === 1)
<form class="card" method="post" action="{{ route('host.onboard.identity') }}" enctype="multipart/form-data" style="max-width:640px">@csrf
  <h3><x-i3 name="user" class="sm"/> ۱. اطلاعات هویتی</h3>
  <div class="field"><label>نام و نام خانوادگی (مطابق کارت ملی)</label><input class="input" name="name" value="{{ old('name', $u->name) }}" required>@error('name')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="grid2"><div class="field"><label>کد ملی</label><input class="input" dir="ltr" name="national_code" inputmode="numeric" maxlength="10" value="{{ old('national_code', $u->national_code) }}" required>@error('national_code')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="field"><label>تاریخ تولد (شمسی)</label><input class="input" dir="ltr" name="birth_date" placeholder="1365/04/21" value="{{ old('birth_date', $u->birth_date) }}" required>@error('birth_date')<div class="msg-e">{{ $message }}</div>@enderror</div></div>
  @if($u->id_status === 'rejected')<div class="alert bad"><x-icon name="info"/><div><b>مدارک شما تأیید نشد.</b><div>{{ $u->id_note ?: 'لطفاً مدارک را اصلاح و دوباره ارسال کنید.' }}</div></div></div>@elseif($u->id_status === 'pending' && $u->docs_submitted_at)<div class="alert info"><x-icon name="clock"/>مدارک شما ارسال شده و مدیر سایت در حال بررسی است.</div>@elseif($u->id_status === 'verified')<div class="alert ok"><x-icon name="check"/>هویت شما تأیید شده است.</div>@endif
  <div class="field"><label>تصویر کارت ملی <b style="color:var(--sale)">(الزامی)</b> @if($u->id_photo)<span class="badge green" style="padding:0 8px">ارسال شده ✓</span>@endif</label><input class="input" type="file" name="id_photo" accept="image/*" @required(! $u->id_photo)>@error('id_photo')<div class="msg-e">{{ $message }}</div>@enderror<div class="help">عکس واضح از روی کارت ملی (JPG یا PNG، حداکثر ۶ مگابایت). فقط مدیر سایت می‌بیند.</div></div>
  <div class="field"><label>عکس خودتان همراه کارت ملی (اختیاری؛ تأیید را سریع‌تر می‌کند) @if($u->selfie_photo)<span class="badge green" style="padding:0 8px">ارسال شده ✓</span>@endif</label><input class="input" type="file" name="selfie_photo" accept="image/*">@error('selfie_photo')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="field"><label>مدرک مالکیت یا اجاره‌نامه‌ی اقامتگاه (اختیاری؛ تصویر یا PDF) @if($u->ownership_doc)<span class="badge green" style="padding:0 8px">ارسال شده ✓</span>@endif</label><input class="input" type="file" name="ownership_doc" accept="image/*,application/pdf">@error('ownership_doc')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <button class="btn btn-lg">ذخیره و ادامه <x-icon name="chev-l" class="sm"/></button>
</form>
@elseif($step === 2)
<form class="card" method="post" action="{{ route('host.onboard.bank') }}" style="max-width:640px">@csrf
  <h3><x-i3 name="p_wallet" class="sm"/> ۲. حساب بانکی برای تسویه</h3>
  <div class="field"><label>شماره کارت (۱۶ رقم)</label><input class="input" dir="ltr" name="card_number" inputmode="numeric" maxlength="19" value="{{ old('card_number', $u->card_number) }}" placeholder="6037 9970 0000 1234" required>@error('card_number')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="field"><label>شماره شبا</label><input class="input" dir="ltr" name="sheba" value="{{ old('sheba', $u->sheba) }}" placeholder="IR062960000000100324200001" required>@error('sheba')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="field"><label>نام صاحب حساب</label><input class="input" name="bank_owner" value="{{ old('bank_owner', $u->bank_owner ?: $u->name) }}" required><div class="help">حساب باید به نام خودتان باشد.</div>@error('bank_owner')<div class="msg-e">{{ $message }}</div>@enderror</div>
  <div class="row"><a class="btn btn-light" href="{{ route('host.onboard', ['step' => 1]) }}">قبلی</a><button class="btn btn-lg">تأیید و ثبت اقامتگاه <x-icon name="chev-l" class="sm"/></button></div>
</form>
@else
<div class="card" style="max-width:640px"><h3><x-i3 name="check" class="sm"/> اطلاعات شما کامل است</h3>
  <div class="qrow"><span>کد ملی</span><b class="ltr">{{ fa($u->national_code) }}</b></div><div class="qrow"><span>کارت</span><b class="ltr">{{ $u->maskedCard() }} @if($u->bank_name)· {{ $u->bank_name }}@endif</b></div><div class="qrow"><span>وضعیت هویت</span><b>{{ ['none' => '—', 'pending' => 'در انتظار بررسی مدیر', 'verified' => 'تأییدشده', 'rejected' => 'رد شده'][$u->id_status] }}</b></div>
  <div class="row wrap mt"><a class="btn btn-lg" href="{{ route('host.properties.create') }}">ثبت اقامتگاه جدید</a><a class="btn btn-light" href="{{ route('host.onboard', ['step' => 1]) }}">ویرایش اطلاعات</a></div></div>
@endif
@endsection
