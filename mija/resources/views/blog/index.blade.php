@extends('layouts.app')
@section('title', 'مجله‌ی می‌جا؛ راهنمای اجاره ویلا و کلبه')
@section('desc', 'مقاله‌ها و راهنماهای می‌جا برای اجاره ویلا، رزرو کلبه، انتخاب مقصد در شمال و اطراف تهران، زمان مناسب سفر و رزرو امن.')
@section('keywords', 'اجاره ویلا, رزرو ویلا, اجاره کلبه, ویلا شمال, ویلا استخردار, راهنمای رزرو ویلا, مجله می‌جا')
@section('canonical', route('blog'))
@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [
  ['@type' => 'CollectionPage', '@id' => route('blog').'#page', 'url' => route('blog'), 'name' => 'مجله‌ی می‌جا', 'inLanguage' => 'fa-IR', 'isPartOf' => ['@id' => url('/').'#web']],
  ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => url('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'مجله', 'item' => route('blog')]]],
]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<div class="container bl-wrap">
  <nav class="bl-bc" aria-label="مسیر"><a href="{{ url('/') }}">خانه</a> <span>›</span> <b>مجله</b></nav>
  <header class="bl-head"><h1>مجله‌ی می‌جا</h1><p>راهنماهای ساده و کاربردی برای <a href="{{ route('blog.show', 'ejare-villa') }}">اجاره ویلا</a>، رزرو کلبه و سفرهای بدون دردسر؛ از انتخاب مقصد تا رزرو امن.</p></header>
  <div class="bl-grid">
    @foreach($items as $a)
    <a class="bl-card" href="{{ $a->url() }}">
      <div class="bl-img"><img src="{{ $a->imageUrl() }}" alt="{{ $a->image_alt ?: $a->title }}" width="600" height="338" loading="{{ $loop->first ? 'eager' : 'lazy' }}"></div>
      <div class="bl-b"><span class="badge orange">{{ $a->category }}</span><h2>{{ $a->title }}</h2><p>{{ $a->excerpt }}</p><small>{{ jdate($a->published_at ?? $a->created_at, 'j F Y') }} · {{ fa($a->readMinutes()) }} دقیقه مطالعه</small></div>
    </a>
    @endforeach
  </div>
  @include('partials.pager', ['paginator' => $items])
</div>
@endsection
