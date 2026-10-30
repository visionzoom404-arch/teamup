@extends('layouts.app')
@section('title', $g->name)
@section('bare')
<div class="container" style="padding-top:20px;padding-bottom:30px;max-width:920px">
  <section class="prof-hero guest" style="margin-bottom:18px">
    <div class="ph-main">
      <div class="ph-avatar" style="cursor:default"><img src="{{ $g->avatarUrl() }}" alt=""></div>
      <div class="grow"><div class="row wrap" style="gap:8px"><h1>{{ $g->name }}</h1><span class="role-pill blue"><x-i3 name="user" class="sm"/> مهمان</span><span class="badge gold" style="--c:{{ $lv['level']['color'] }}"><x-i3 name="p_trophy" class="sm"/> سطح {{ $lv['level']['name'] }}</span></div>
        <div class="ph-meta">@if($g->city)<span><x-icon name="pin" class="sm"/> {{ $g->city }}</span>@endif<span><x-icon name="calendar" class="sm"/> عضو از {{ jdate($g->created_at, 'F Y') }}</span></div>
        @if($g->bio)<p class="ph-bio">{{ $g->bio }}</p>@endif</div>
    </div>
  </section>
  <div class="tiles"><div class="tile card"><div class="ic3"><x-i3 name="p_calendar"/></div><b>{{ fa($trips->count()) }}</b><span>اقامتگاه رزروشده</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="p_star"/></div><b>{{ fa($reviews->count()) }}</b><span>نظر منتشرشده</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="sun"/></div><b>{{ fa($lv['nights']) }}</b><span>شب اقامت</span></div></div>
  @if($trips->count())
  <div class="sec-h"><h2>اقامتگاه‌هایی که رزرو کرده</h2></div>
  <div class="grid-cards">@foreach($trips as $t)@php $p = $t->property; $rec = $reviews->firstWhere('property_id', $p->id); @endphp
    <div class="trip-wrap">@include('partials.pcard')@if($rec && $rec->recommend)<span class="rec-tag"><x-icon name="check" class="sm"/> پیشنهاد می‌کند</span>@endif</div>@endforeach</div>
  @endif
  @if($photos->count())
  <div class="sec-h" style="margin-top:28px"><h2>عکس‌های ثبت‌شده توسط {{ \Illuminate\Support\Str::before($g->name, ' ') }}</h2></div>
  <div class="rv-ph big">@foreach($photos as $u)<a href="{{ $u }}" data-lightbox><img src="{{ $u }}" alt=""></a>@endforeach</div><div class="lightbox" id="lightbox"><button class="vp-ic"><x-icon name="x"/></button><img src="" alt=""></div>
  @endif
  @if($reviews->count())
  <div class="sec-h" style="margin-top:28px"><h2>نظرات</h2></div>
  @foreach($reviews as $rv)<div class="card" style="margin-bottom:10px"><div class="row between"><b><a href="{{ route('property', $rv->property) }}">{{ $rv->property->title }}</a></b><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span></div><p class="small" style="margin:6px 0 0">{{ $rv->comment }}</p></div>@endforeach
  @endif
</div>
@endsection
