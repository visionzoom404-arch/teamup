@extends('layouts.app')
@section('title', 'میزبان شوید')
@section('bare')
<div class="container" style="padding-bottom:40px">
<section class="hero" style="padding-bottom:30px"><h1>ملکت را <span class="grad-text">درآمدزا</span> کن</h1><p class="sub">ثبت اقامتگاه در می‌جا رایگان است؛ فقط پس از هر رزرو موفق، {{ fa(setting('commission_percent')) }}٪ کمیسیون کسر می‌شود.</p><a class="btn btn-lg" href="{{ route('register', ['as' => 'host']) }}">همین حالا ثبت‌نام کن</a></section>
<div class="feat">
  <div class="card"><div class="ico"><x-icon name="home" class="lg"/></div><b>۱. اقامتگاه را ثبت کن</b><p class="small muted">عکس، قیمت و امکانات را وارد کن تا تیم می‌جا آن را بررسی و منتشر کند.</p></div>
  <div class="card"><div class="ico blue"><x-icon name="calendar" class="lg"/></div><b>۲. تقویم را مدیریت کن</b><p class="small muted">روزها را ببند، قیمت‌های ویژه بگذار و رزرو فوری را فعال کن.</p></div>
  <div class="card"><div class="ico green"><x-icon name="users" class="lg"/></div><b>۳. مهمان بپذیر</b><p class="small muted">درخواست‌ها را تأیید کن و با مهمان گفتگو کن.</p></div>
  <div class="card"><div class="ico dark"><x-icon name="wallet" class="lg"/></div><b>۴. درآمد بگیر</b><p class="small muted">پس از ورود مهمان، درآمد به کیف پولت واریز می‌شود و هر زمان برداشت می‌کنی.</p></div>
</div></div>
@endsection
