@extends('layouts.panel')
@section('title', 'جستجو')
@section('content')
<div class="page-h"><h1>نتایج جستجو: «{{ $q }}»</h1></div>
<div class="grid2" style="gap:18px;align-items:start"><div class="card"><h3>کاربران</h3>@forelse($users as $u)<a class="row between" style="padding:8px 0;border-bottom:1px solid var(--line)" href="{{ route('admin.users.edit', $u) }}"><span><b>{{ $u->name }}</b> <span class="badge">{{ $u->roleLabel() }}</span></span><span class="ltr small">{{ fa($u->phone) }}</span></a>@empty<p class="muted small">—</p>@endforelse</div>
<div class="stack"><div class="card"><h3>رزروها</h3>@forelse($bookings as $b)<a class="row between" style="padding:8px 0;border-bottom:1px solid var(--line)" href="{{ route('admin.bookings.show', $b) }}"><b class="ltr">{{ $b->code }}</b><span class="small">{{ $b->property->title }}</span></a>@empty<p class="muted small">—</p>@endforelse</div>
<div class="card"><h3>اقامتگاه‌ها</h3>@forelse($properties as $p)<a class="row between" style="padding:8px 0;border-bottom:1px solid var(--line)" href="{{ route('admin.properties.show', $p) }}"><b>{{ $p->title }}</b><span class="small muted">{{ $p->city }}</span></a>@empty<p class="muted small">—</p>@endforelse</div></div></div>
@endsection
