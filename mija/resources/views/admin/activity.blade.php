@extends('layouts.panel')
@section('title', 'گزارش فعالیت')
@section('content')
<div class="page-h"><h1>گزارش فعالیت مدیران</h1></div>
<div class="row wrap mb"><a class="btn btn-sm btn-light" href="{{ route('admin.backup') }}"><x-icon name="download" class="sm"/> دانلود نسخه‌ی پشتیبان دیتابیس</a><a class="btn btn-sm btn-light" href="{{ route('admin.export', 'bookings') }}">خروجی رزروها (CSV)</a></div>
<div class="card pad0"><div class="table-wrap"><table class="tbl"><thead><tr><th>زمان</th><th>مدیر</th><th>اقدام</th><th>مورد</th><th>آی‌پی</th></tr></thead><tbody>
@foreach($items as $a)<tr><td class="small nowrap">{{ jdate($a->created_at, 'Y/m/d H:i') }}</td><td>{{ $a->user?->name ?? '—' }}</td><td class="ltr small">{{ $a->action }}</td><td class="small">{{ $a->subject }} <span class="xs muted">{{ $a->meta }}</span></td><td class="ltr xs">{{ $a->ip }}</td></tr>@endforeach
</tbody></table></div></div>
@include('partials.pager', ['paginator' => $items])
@endsection
