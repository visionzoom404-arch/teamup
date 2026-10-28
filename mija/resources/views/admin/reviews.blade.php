@extends('layouts.panel')
@section('title', 'نظرات')
@section('content')
<div class="page-h"><h1>مدیریت نظرات</h1></div>
@forelse($items as $r)<div class="card mb"><div class="row between wrap"><div><b>{{ $r->guest->name }}</b> <span class="muted small">← {{ $r->property->title }}</span> <span class="stars"><x-icon name="star"/>{{ fa($r->rating) }}</span><p style="margin:6px 0 0">{{ $r->comment ?: '—' }}</p></div>
<form method="post" action="{{ route('admin.reviews.toggle', $r) }}">@csrf<button class="btn btn-sm {{ $r->is_published ? 'btn-ghost' : 'btn-green' }}">{{ $r->is_published ? 'عدم نمایش' : 'نمایش' }}</button></form></div></div>
@empty<div class="card empty"><x-icon name="star"/><p>نظری ثبت نشده است.</p></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
