@extends('layouts.app')
@section('title', 'میزبان '.$h->name)
@section('bare')
<div class="container" style="padding-top:20px;padding-bottom:30px">
  <section class="prof-hero host" style="margin-bottom:22px">
    <div class="ph-art" aria-hidden="true"><x-i3 name="khatam" class="xl"/><x-i3 name="khatam" class="xl"/></div>
    <div class="ph-main">
      <div class="ph-avatar" style="cursor:default"><img src="{{ $h->avatarUrl() }}" alt=""></div>
      <div class="grow">
        <div class="row wrap" style="gap:8px"><h1>{{ $h->name }}</h1><span class="role-pill orange"><x-i3 name="p_key" class="sm"/> میزبان</span>
          @if($h->host_verified)<span class="badge green"><x-icon name="shield" class="sm"/> هویت تأییدشده</span>@endif
          @if($hs['superhost'])<span class="badge gold"><x-i3 name="p_crown" class="sm"/> میزبان برتر</span>@endif</div>
        <div class="ph-meta">@if($h->city)<span><x-icon name="pin" class="sm"/> {{ $h->city }}</span>@endif<span><x-icon name="calendar" class="sm"/> عضو از {{ jdate($h->created_at, 'F Y') }}</span></div>
        @if($h->bio)<p class="ph-bio">{{ $h->bio }}</p>@endif
      </div>
    </div>
  </section>
  <div class="tiles">
    <div class="tile card"><div class="ic3"><x-i3 name="p_star"/></div><b>{{ $hs['rating'] ? fa(number_format($hs['rating'], 1)) : '—' }}</b><span>امتیاز میانگین</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="p_chat"/></div><b>{{ fa($hs['reviews']) }}</b><span>نظر مهمانان</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="check"/></div><b>{{ fa($hs['completed']) }}</b><span>اقامت موفق</span></div>
    <div class="tile card"><div class="ic3"><x-i3 name="p_hourglass"/></div><b>{{ $hs['response_rate'] !== null ? fa($hs['response_rate']).'٪' : '—' }}</b><span>نرخ پاسخ‌گویی</span></div>
  </div>
  <div class="sec-h"><h2>اقامتگاه‌های {{ \Illuminate\Support\Str::before($h->name, ' ') }}</h2></div>
  @if($props->count())<div class="grid-cards">@foreach($props as $p)@include('partials.pcard')@endforeach</div>@else<div class="card empty"><x-i3 name="p_house"/><p>اقامتگاه فعالی ندارد.</p></div>@endif
  @if($reviews->count())
  <div class="sec-h" style="margin-top:34px"><h2>نظرات مهمانان</h2></div>
  <div class="card">@foreach($reviews as $rv)<div class="rev"><div class="row"><img class="avatar sm" src="{{ $rv->guest->avatarUrl() }}" alt=""><div class="grow"><b>{{ $rv->guest->name }}</b> <span class="muted small">درباره‌ی {{ $rv->property->title }}</span></div><span class="stars"><x-icon name="star"/>{{ fa($rv->rating) }}</span></div>@if($rv->comment)<p style="margin:8px 0 0">{{ $rv->comment }}</p>@endif</div>@endforeach</div>
  @endif
</div>
@endsection
