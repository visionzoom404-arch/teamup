@extends('layouts.panel')
@section('title', 'گزارش خطاها')
@section('content')
<div class="page-h"><div><h1>گزارش خطاها</h1><div class="muted">آخرین خطاهای ثبت‌شده‌ی سرور؛ اگر صفحه‌ای ارور ۵۰۰ داد، اینجا علتش دیده می‌شود</div></div></div>
@forelse($items as $it)
<div class="card mb err-row"><div class="row between wrap" style="gap:8px"><span class="badge red">خطا</span><span class="xs muted ltr">{{ $it['at'] }}</span></div><div style="margin-top:6px;overflow-wrap:anywhere">{{ $it['msg'] }}</div>@if($it['at_file'])<div class="xs muted ltr" style="text-align:left;margin-top:4px;overflow-wrap:anywhere">{{ $it['at_file'] }}</div>@endif</div>
@empty
<div class="card empty"><x-icon name="check"/><p>خطایی ثبت نشده است.</p></div>
@endforelse
@endsection
