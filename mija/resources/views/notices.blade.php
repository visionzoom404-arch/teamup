@extends('layouts.panel')
@section('title', 'اعلان‌ها')
@section('content')
<div class="page-h"><h1>اعلان‌ها</h1></div>
@forelse($items as $n)
  <a href="{{ $n->url ?: '#' }}" class="item-row"><span class="tile" style="padding:0;width:42px;height:42px;display:grid;place-items:center;background:var(--grad-warm);color:#fff;border-radius:14px"><x-icon name="bell"/></span><div class="grow"><b>{{ $n->title }}</b>@if($n->body)<div class="small muted">{{ $n->body }}</div>@endif</div><span class="xs muted nowrap">{{ jdate($n->created_at, 'j F · H:i') }}</span></a>
@empty
  <div class="card empty"><x-icon name="bell"/><p>اعلانی ندارید.</p></div>
@endforelse
@include('partials.pager', ['paginator' => $items])
@endsection
