@extends('layouts.app')
@section('title', 'دسترسی ندارید')
@section('nofooter', '1')
@section('bare')
<div class="auth"><div class="card center" style="max-width:460px;padding:30px 22px"><x-icon name="info" class="lg"/><h2>دسترسی ندارید</h2><p class="muted">شما اجازه‌ی دیدن این صفحه را ندارید. اگر فکر می‌کنید اشتباه شده، با پشتیبانی تماس بگیرید.</p><a class="btn" href="{{ url('/') }}">بازگشت به صفحه‌ی اول</a></div></div>
@endsection
