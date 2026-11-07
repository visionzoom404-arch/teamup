@extends('layouts.panel')
@section('title', $u->exists ? 'ویرایش کاربر' : 'کاربر جدید')
@section('content')
@php $v = fn ($k, $d = null) => old($k, $u->$k ?? $d); @endphp
<div class="page-h"><div><a href="{{ route('admin.users') }}" class="small muted">← کاربران</a><h1>{{ $u->exists ? 'ویرایش: '.$u->name : 'کاربر جدید' }}</h1></div>
  @if($u->exists && ! $u->isAdmin())<form method="post" action="{{ route('admin.users.impersonate', $u) }}">@csrf<button class="btn btn-gold"><x-icon name="eye"/> ورود به پنل این کاربر</button></form>@endif</div>
<form method="post" enctype="multipart/form-data" action="{{ $u->exists ? route('admin.users.update', $u) : route('admin.users.store') }}" class="stack">@csrf
  <div class="card"><h3>اطلاعات اصلی</h3>
    <div class="grid3">
      <div class="field"><label>نام و نام خانوادگی</label><input class="input" name="name" value="{{ $v('name') }}" required>@error('name')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>موبایل</label><input class="input" dir="ltr" name="phone" value="{{ $v('phone') }}" required>@error('phone')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>نقش</label><select name="role">@foreach(['guest' => 'مهمان', 'host' => 'میزبان', 'admin' => 'مدیر سایت'] as $k => $l)<option value="{{ $k }}" @selected($v('role') === $k)>{{ $l }}</option>@endforeach</select>@error('role')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>ایمیل</label><input class="input" dir="ltr" name="email" value="{{ $v('email') }}">@error('email')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>شهر</label><input class="input" name="city" value="{{ $v('city') }}"></div>
      <div class="field"><label>{{ $u->exists ? 'رمز جدید (خالی = بدون تغییر)' : 'رمز عبور' }}</label><input class="input" dir="ltr" name="password" type="text" autocomplete="off" @required(! $u->exists)>@error('password')<div class="msg-e">{{ $message }}</div>@enderror</div>
    </div>
    <div class="field"><label>معرفی</label><textarea class="input" name="bio">{{ $v('bio') }}</textarea></div>
    <div class="field"><label>عکس پروفایل</label><input class="input" type="file" name="avatar" accept="image/*"></div>
    <div class="row wrap" style="gap:14px"><label class="check"><input type="checkbox" name="is_active" value="1" @checked($v('is_active', true))> حساب فعال</label><label class="check"><input type="checkbox" name="host_verified" value="1" @checked($v('host_verified'))> میزبان تأییدشده (نشان)</label><label class="check"><input type="checkbox" name="installment_unlocked" value="1" @checked($v('installment_unlocked'))> رزرو اقساطی فعال</label></div>
  </div>
  <div class="card"><h3>هویت و حساب بانکی</h3>
    <div class="grid3">
      <div class="field"><label>کد ملی</label><input class="input" dir="ltr" name="national_code" value="{{ $v('national_code') }}">@error('national_code')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>تاریخ تولد</label><input class="input" dir="ltr" name="birth_date" value="{{ $v('birth_date') }}" placeholder="1365/04/21"></div>
      <div class="field"><label>وضعیت هویت</label><select name="id_status">@foreach(['none' => '—', 'pending' => 'در انتظار بررسی', 'verified' => 'تأییدشده', 'rejected' => 'ردشده'] as $k => $l)<option value="{{ $k }}" @selected($v('id_status', 'none') === $k)>{{ $l }}</option>@endforeach</select></div>
      <div class="field"><label>شماره کارت</label><input class="input" dir="ltr" name="card_number" value="{{ $v('card_number') }}">@error('card_number')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>شبا</label><input class="input" dir="ltr" name="sheba" value="{{ $v('sheba') }}">@error('sheba')<div class="msg-e">{{ $message }}</div>@enderror</div>
      <div class="field"><label>نام صاحب حساب</label><input class="input" name="bank_owner" value="{{ $v('bank_owner') }}"></div>
      <div class="field"><label>بانک</label><input class="input" name="bank_name" value="{{ $v('bank_name') }}"></div>
      <div class="field"><label>کمیسیون اختصاصی (٪)</label><input class="input" dir="ltr" type="number" name="commission_override" value="{{ $v('commission_override') }}" placeholder="پیش‌فرض سایت: {{ fa(setting('commission_percent')) }}"><div class="help">فقط برای این میزبان؛ خالی = کمیسیون عمومی.</div></div>
      <div class="field"><label>یادداشت هویت</label><input class="input" name="id_note" value="{{ $v('id_note') }}"></div>
    </div>
    @if($u->exists && $u->id_photo)<a class="btn btn-sm btn-light" target="_blank" href="{{ route('admin.users.idphoto', $u) }}"><x-icon name="eye" class="sm"/> مشاهده‌ی تصویر کارت ملی</a>@endif
  </div>
  @unless($u->exists)
  <div class="card"><label class="check"><input type="checkbox" name="send_invite" value="1" checked><div><b>بعد از ساخت، پیامک «لینک ورود شخصی» برای کاربر ارسال شود</b><div class="xs muted" style="font-weight:500">متن پیام: «سلام جناب/آقای (نام) … از پشتیبانی (میزبانان/مهمانان طبق نقش) پلتفرم می‌جا … وارد لینک زیر شوید؛ شماره موبایل شما (شماره‌ی ثبت‌شده) به‌عنوان نام کاربری و گذرواژه‌ی شما ثبت گردید … لینک ورود: mija.click/j/…». کاربر با زدن لینک بدون رمز وارد پنل می‌شود و ثبت‌نام (برای میزبان: هویت و ثبت اقامتگاه) را ادامه می‌دهد. برای نقش «مدیر سایت» لینک ساخته نمی‌شود.</div></div></label></div>
  @endunless
  <div class="row pf-nav glass"><button class="btn btn-lg">{{ $u->exists ? 'ذخیره' : 'ساخت کاربر' }}</button></div>
</form>
@if($u->exists && ! $u->isAdmin())
<div class="card mt"><h3><x-i3 name="p_phone" class="sm"/> لینک ورود شخصی (پیامکی)</h3>
  @if(session('invite_link'))<div class="copybox mb"><span class="ltr small" id="invl" style="overflow-wrap:anywhere">{{ session('invite_link') }}</span><button type="button" class="btn btn-sm" data-copy="#invl"><x-icon name="share" class="sm"/> کپی لینک</button></div>@endif
  <p class="small muted" style="line-height:2">وضعیت: @if($u->invite_used_at)<b>کاربر با لینک وارد شده است ({{ jdate($u->invite_used_at, 'j F H:i') }})</b>@elseif($u->invite_sent_at)لینک پیامک شده ({{ jdate($u->invite_sent_at, 'j F H:i') }})؛ هنوز استفاده نشده@else لینکی ارسال نشده@endif @if($u->invite_expires_at) · اعتبار تا {{ jdate($u->invite_expires_at, 'j F') }}@endif</p>
  <form method="post" action="{{ route('admin.users.invite', $u) }}" onsubmit="return confirm('لینک جدید ساخته و پیامک می‌شود؛ لینک قبلی باطل می‌شود. ادامه؟')">@csrf<button class="btn btn-sm btn-light"><x-icon name="send" class="sm"/> ارسال لینک جدید به {{ fa($u->phone) }}</button></form>
</div>
@endif
@if($u->exists)
<div class="grid2 mt" style="gap:18px;align-items:start">
  <form class="card" method="post" action="{{ route('admin.users.wallet', $u) }}">@csrf<h3>کیف پول — موجودی {{ toman($u->wallet) }}</h3>
    <div class="grid2"><div class="field"><label>مبلغ (منفی = کسر)</label><input class="input" dir="ltr" type="number" name="amount" required></div><div class="field"><label>دلیل</label><input class="input" name="reason" required></div></div><button class="btn btn-sm btn-gold">اعمال</button>
    @foreach($txs as $t)<div class="row between small" style="padding:6px 0;border-bottom:1px solid var(--line)"><span>{{ $t->reason }}</span><b class="ltr" style="color:{{ $t->type === 'credit' ? 'var(--ok)' : 'var(--sale)' }}">{{ $t->type === 'credit' ? '+' : '−' }}{{ fa(number_format($t->amount)) }}</b></div>@endforeach</form>
  @if($u->isHost())
  <form class="card" method="post" action="{{ route('admin.users.settle', $u) }}" enctype="multipart/form-data">@csrf<h3>تسویه‌ی کیف پول میزبان</h3>
    <p class="small muted">موجودی قابل برداشت: <b>{{ toman($u->wallet) }}</b> — پس از واریز دستی به شبا <span class="ltr">{{ $u->sheba ?: '—' }}</span>، اینجا رسید را ثبت کنید؛ مبلغ از کیف پول کم می‌شود و رسید برای میزبان (اعلان + پیامک) می‌رود.</p>
    <div class="grid2"><div class="field"><label>مبلغ (خالی = همه‌ی موجودی)</label><input class="input" dir="ltr" type="number" name="amount" max="{{ $u->wallet }}" placeholder="{{ $u->wallet }}"></div><div class="field"><label>کد پیگیری بانکی</label><input class="input" dir="ltr" name="tracking_code"></div></div>
    <div class="field"><label>تصویر رسید بانکی</label><input class="input" type="file" name="receipt" accept="image/*"></div>
    <div class="field"><label>توضیح (اختیاری)</label><input class="input" name="note" maxlength="200"></div>
    @error('receipt')<div class="msg-e">{{ $message }}</div>@enderror
    <button class="btn btn-sm btn-gold" @disabled($u->wallet < 1) onclick="return confirm('تسویه ثبت و رسید ارسال شود؟')">ثبت واریز و ارسال رسید</button></form>
  @endif
  <div class="card"><h3>اقدامات</h3><div class="stack">
    @unless($u->isAdmin())<form method="post" action="{{ route('admin.users.toggle', $u) }}">@csrf<button class="btn btn-ghost btn-block">{{ $u->is_active ? 'مسدود کردن حساب' : 'رفع مسدودی' }}</button></form>@endunless
    @if($u->id !== auth()->id())<form method="post" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('این کاربر و همه‌ی اطلاعات وابسته (اقامتگاه‌ها، رزروها) برای همیشه حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-danger btn-block"><x-icon name="trash" class="sm"/> حذف کاربر</button></form>@endif</div></div>
</div>
@endif
@endsection
