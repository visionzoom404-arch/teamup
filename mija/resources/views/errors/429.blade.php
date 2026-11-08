@extends('layouts.app')
@section('title', 'درخواست‌ها زیاد بود')
@section('nofooter', '1')
@section('bare')
<div class="auth"><div class="card center" style="max-width:460px;padding:30px 22px"><x-icon name="info" class="lg"/><h2>درخواست‌ها زیاد بود</h2><p class="muted">در مدت کوتاهی درخواست‌های زیادی فرستاده شد. چند لحظه صبر کنید و دوباره تلاش کنید.</p><a class="btn" href="{{ url('/') }}">بازگشت به صفحه‌ی اول</a></div></div>
@endsection
