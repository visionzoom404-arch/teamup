@extends('layouts.app')
@section('title', $page->title)
@section('content')
<div class="container page-doc">
  <article class="doc card">
    <h1>{{ $page->title }}</h1>
    <div class="doc-body">{!! $page->body !!}</div>
    <div class="doc-links">
      @foreach(\App\Support\DefaultPages::SLUGS as $sl => $tt)@if($sl !== $page->slug)<a href="{{ route('page', $sl) }}">{{ $tt }}</a>@endif @endforeach
    </div>
  </article>
</div>
@endsection
