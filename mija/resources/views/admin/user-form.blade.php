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
  <div class="row pf-nav glass"><button class="btn btn-lg">ذخیره</button></div>
</form>
@if($u->exists)
<div class="grid2 mt" style="gap:18px;align-items:start">
  <form class="card" method="post" action="{{ route('admin.users.wallet', $u) }}">@csrf<h3>کیف پول — موجودی {{ toman($u->wallet) }}</h3>
    <div class="grid2"><div class="field"><label>مبلغ (منفی = کسر)</label><input class="input" dir="ltr" type="number" name="amount" required></div><div class="field"><label>دلیل</label><input class="input" name="reason" required></div></div><button class="btn btn-sm btn-gold">اعمال</button>
    @foreach($txs as $t)<div class="row between small" style="padding:6px 0;border-bottom:1px solid var(--line)"><span>{{ $t->reason }}</span><b class="ltr" style="color:{{ $t->type === 'credit' ? 'var(--ok)' : 'var(--sale)' }}">{{ $t->type === 'credit' ? '+' : '−' }}{{ fa(number_format($t->amount)) }}</b></div>@endforeach</form>
  <div class="card"><h3>اقدامات</h3><div class="stack">
    @unless($u->isAdmin())<form method="post" action="{{ route('admin.users.toggle', $u) }}">@csrf<button class="btn btn-ghost btn-block">{{ $u->is_active ? 'مسدود کردن حساب' : 'رفع مسدودی' }}</button></form>@endunless
    @if($u->id !== auth()->id())<form method="post" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('این کاربر و همه‌ی اطلاعات وابسته (اقامتگاه‌ها، رزروها) برای همیشه حذف شود؟')">@csrf @method('DELETE')<button class="btn btn-danger btn-block"><x-icon name="trash" class="sm"/> حذف کاربر</button></form>@endif</div></div>
</div>
@endif
@endsection
