@extends('layouts.panel')
@section('title', 'پروفایل')
@section('content')
<div class="page-h"><h1>پروفایل</h1></div>
<form class="card" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
  @csrf
  <div class="row" style="margin-bottom:18px"><img class="avatar lg" src="{{ $u->avatarUrl() }}" alt=""><div><label>تصویر پروفایل</label><input type="file" name="avatar" accept="image/*"></div></div>
  <div class="grid2">
    <div class="field"><label>نام و نام خانوادگی</label><input class="input" name="name" value="{{ old('name', $u->name) }}" required></div>
    <div class="field"><label>شماره موبایل</label><input class="input" dir="ltr" value="{{ $u->phone }}" disabled></div>
    <div class="field"><label>ایمیل</label><input class="input" dir="ltr" type="email" name="email" value="{{ old('email', $u->email) }}"></div>
    <div class="field"><label>شهر</label><input class="input" name="city" value="{{ old('city', $u->city) }}"></div>
  </div>
  <div class="field"><label>درباره من</label><textarea class="input" name="bio">{{ old('bio', $u->bio) }}</textarea></div>
  @if($u->isHost())
    <h3 style="margin-top:8px">اطلاعات حساب بانکی (برای دریافت درآمد)</h3>
    <div class="grid2">
      <div class="field"><label>کد ملی</label><input class="input" dir="ltr" name="national_id" value="{{ old('national_id', $u->national_id) }}" maxlength="10"></div>
      <div class="field"><label>نام صاحب حساب</label><input class="input" name="bank_owner" value="{{ old('bank_owner', $u->bank_owner) }}"></div>
    </div>
    <div class="field"><label>شماره شبا</label><input class="input" dir="ltr" name="sheba" value="{{ old('sheba', $u->sheba) }}" placeholder="IR000000000000000000000000" maxlength="26"><div class="help">شبا باید به نام خودتان باشد.</div></div>
  @endif
  <button class="btn">ذخیره تغییرات</button>
</form>
<form class="card mt" method="post" action="{{ route('profile.password') }}">
  @csrf <h3>تغییر رمز عبور</h3>
  <div class="grid3"><div class="field"><label>رمز فعلی</label><input class="input" dir="ltr" type="password" name="current" required></div><div class="field"><label>رمز جدید</label><input class="input" dir="ltr" type="password" name="password" required></div><div class="field"><label>تکرار رمز جدید</label><input class="input" dir="ltr" type="password" name="password_confirmation" required></div></div>
  <button class="btn btn-dark">تغییر رمز</button>
</form>
@endsection
