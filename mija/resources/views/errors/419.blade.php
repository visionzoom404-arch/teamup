@extends('layouts.app')
@section('title', 'نشست شما منقضی شد')
@section('nofooter', '1')
@section('bare')
<div class="auth"><div class="card center" style="max-width:460px;padding:30px 22px"><x-icon name="info" class="lg"/><h2>نشست شما منقضی شد</h2><p class="muted">چون مدتی از باز شدن این صفحه گذشته، فرم ارسال نشد. صفحه را دوباره باز کنید و فرم را از نو بفرستید؛ اطلاعات شما حفظ نمی‌شود.</p><a class="btn" href="{{ url('/') }}">بازگشت به صفحه‌ی اول</a></div></div>
@endsection
