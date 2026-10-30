@extends('layouts.panel')
@section('title', 'پروفایل مهمان')
@section('content')
<div class="page-h"><div><a href="javascript:history.back()" class="small muted">← بازگشت</a><h1>پروفایل مهمان</h1></div></div>
<section class="prof-hero guest" style="margin-bottom:16px"><div class="ph-main"><div class="ph-avatar" style="cursor:default"><img src="{{ $g->avatarUrl() }}" alt=""></div><div class="grow"><h1>{{ $g->name }}</h1><div class="ph-meta">@if($g->city)<span><x-icon name="pin" class="sm"/> {{ $g->city }}</span>@endif<span><x-icon name="calendar" class="sm"/> عضو از {{ jdate($g->created_at, 'F Y') }}</span></div>@if($g->bio)<p class="ph-bio">{{ $g->bio }}</p>@endif</div></div></section>
<div class="tiles"><div class="tile card"><div class="ic3"><x-i3 name="p_calendar"/></div><b>{{ fa($trips) }}</b><span>سفر موفق</span></div><div class="tile card"><div class="ic3"><x-i3 name="p_star"/></div><b>{{ $rating ? fa($rating) : '—' }}</b><span>امتیاز میزبان‌ها</span></div><div class="tile card"><div class="ic3"><x-i3 name="p_trophy"/></div><b>{{ $g->level()['level']['name'] }}</b><span>سطح</span></div></div>
<div class="card"><h3>نظر میزبان‌ها درباره‌ی این مهمان <span class="muted xs">(فقط میزبان‌ها می‌بینند)</span></h3>
@forelse($reviews as $gr)<div class="sr-item"><div class="row between"><span class="stars"><x-icon name="star"/>{{ fa($gr->rating) }}</span><span class="xs muted">{{ jdate($gr->created_at, 'j F Y') }}</span></div>@if($gr->tags)<div class="row wrap" style="gap:4px;margin-top:4px">@foreach($gr->tags as $t)<span class="badge">{{ $t }}</span>@endforeach</div>@endif @if($gr->comment)<p class="small" style="margin:4px 0 0">{{ $gr->comment }}</p>@endif</div>@empty<p class="muted small">هنوز نظری ثبت نشده است.</p>@endforelse</div>
@endsection
