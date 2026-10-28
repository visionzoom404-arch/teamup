@extends('layouts.panel')
@section('title', 'علاقه‌مندی‌ها')
@section('content')
<div class="page-h"><h1>علاقه‌مندی‌ها</h1></div>
@if($items->count())<div class="grid-cards">@foreach($items as $p)@include('partials.pcard')@endforeach</div>
@else<div class="card empty"><x-icon name="heart"/><h3>فهرست علاقه‌مندی‌ها خالی است</h3><p>روی ♡ در کارت اقامتگاه‌ها بزنید.</p><a class="btn" href="{{ route('search') }}">جستجو</a></div>@endif
@endsection
