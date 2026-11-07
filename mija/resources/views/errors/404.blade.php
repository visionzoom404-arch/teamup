@extends('layouts.app')
@section('title', 'صفحه پیدا نشد')
@section('nofooter', '1')
@section('bare')
<div class="auth"><div class="card center" style="max-width:440px;padding:30px 22px"><x-icon name="search" class="lg"/><h2>صفحه‌ای که دنبالش بودید پیدا نشد</h2><p class="muted">ممکن است آدرس اشتباه باشد یا صفحه جابه‌جا شده باشد.</p><a class="btn" href="{{ url('/') }}">بازگشت به صفحه‌ی اول</a></div></div>
@endsection
