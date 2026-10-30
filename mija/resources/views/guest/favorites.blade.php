@extends('layouts.panel')
@section('title', 'علاقه‌مندی‌ها')
@section('content')
<div class="page-h"><h1>علاقه‌مندی‌ها</h1><div class="muted small">هر اقامتگاه به‌طور خودکار در دسته‌ی شهر خودش ذخیره می‌شود.</div></div>
@if($items->count())
<div class="city-tabs"><button class="qchip on" data-city="all">همه ({{ fa($items->count()) }})</button>@foreach($byCity as $city => $g)<button class="qchip" data-city="{{ $city }}">{{ $city }} ({{ fa($g->count()) }})</button>@endforeach</div>
@foreach($byCity as $city => $g)
<section class="fav-city" data-city="{{ $city }}"><div class="sec-h"><h2><x-i3 name="p_pin" class="sm"/> {{ $city }}</h2></div><div class="grid-cards">@foreach($g as $p)@include('partials.pcard')@endforeach</div></section>
@endforeach
<script>document.querySelectorAll('.city-tabs .qchip').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.city-tabs .qchip').forEach(function(x){x.classList.toggle('on',x===b)});document.querySelectorAll('.fav-city').forEach(function(s){s.hidden=b.dataset.city!=='all'&&s.dataset.city!==b.dataset.city})})})</script>
@else<div class="card empty"><x-icon name="heart"/><h3>فهرست علاقه‌مندی‌ها خالی است</h3><p>روی دکمه‌ی قلب در کارت اقامتگاه‌ها بزنید.</p><a class="btn" href="{{ route('search') }}">جستجو</a></div>@endif
@endsection
