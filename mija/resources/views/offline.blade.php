@extends('layouts.app')
@section('title', 'آفلاین')
@section('nofooter', '1')
@section('bare')
<div class="auth"><div class="card center" style="max-width:420px"><x-icon name="wifi" class="lg"/><h2>اتصال اینترنت برقرار نیست</h2><p class="muted">لطفاً اتصال خود را بررسی کنید و دوباره تلاش کنید.</p><button class="btn" onclick="location.reload()">تلاش دوباره</button></div></div>
@endsection
