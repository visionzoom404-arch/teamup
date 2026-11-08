@extends('layouts.app')
@section('title', 'سایت در حال به‌روزرسانی است')
@section('nofooter', '1')
@section('bare')
<div class="auth"><div class="card center" style="max-width:460px;padding:30px 22px"><x-icon name="info" class="lg"/><h2>سایت در حال به‌روزرسانی است</h2><p class="muted">کمی بعد دوباره سر بزنید؛ به‌زودی برمی‌گردیم.</p><a class="btn" href="{{ url('/') }}">بازگشت به صفحه‌ی اول</a></div></div>
@endsection
