@extends('layouts.panel')
@section('title', 'سفرهای من')
@section('content')
<div class="page-h"><h1>سفرهای من</h1></div>
<div class="tabs">@foreach(['all' => 'همه', 'upcoming' => 'پیش‌رو', 'done' => 'انجام‌شده', 'cancelled' => 'لغو / منقضی'] as $k => $l)<a href="{{ route('guest.bookings', ['tab' => $k]) }}" class="{{ $tab === $k ? 'on' : '' }}">{{ $l }}</a>@endforeach</div>
@forelse($items as $b)@include('partials.booking-row', ['b' => $b, 'to' => route('guest.bookings.show', $b), 'who' => 'میزبان: '.$b->property->host->name])
@empty<div class="card empty"><x-icon name="calendar"/><h3>هنوز رزروی ندارید</h3><a class="btn" href="{{ route('search') }}">اولین سفرت را رزرو کن</a></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
