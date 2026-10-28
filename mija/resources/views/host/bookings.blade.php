@extends('layouts.panel')
@section('title', 'رزروها')
@section('content')
<div class="page-h"><h1>رزروها</h1></div>
<form class="row wrap mb" method="get">
  <select name="status" style="width:auto" onchange="this.form.submit()"><option value="">همه‌ی وضعیت‌ها</option>@foreach(config('mija.booking_statuses') as $k => $s)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $s[0] }}</option>@endforeach</select>
  <select name="property" style="width:auto" onchange="this.form.submit()"><option value="">همه‌ی اقامتگاه‌ها</option>@foreach($properties as $pr)<option value="{{ $pr->id }}" @selected(request('property') == $pr->id)>{{ $pr->title }}</option>@endforeach</select>
</form>
@forelse($items as $b)@include('partials.booking-row', ['b' => $b, 'to' => route('host.bookings.show', $b), 'who' => 'مهمان: '.$b->guest->name])
@empty<div class="card empty"><x-icon name="calendar"/><p>رزروی یافت نشد.</p></div>@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
