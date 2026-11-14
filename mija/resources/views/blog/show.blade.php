@extends('layouts.app')
@section('title', $a->meta_title ?: $a->title)
@section('desc', $a->excerpt)
@section('keywords', trim(($a->keyword ? $a->keyword.', ' : '').'اجاره ویلا, رزرو ویلا, رزرو کلبه, می‌جا'))
@section('canonical', $a->url())
@section('ogimage', $a->imageUrl())
@section('ogtype', 'article')
@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [
  ['@type' => 'Article', '@id' => $a->url().'#article', 'mainEntityOfPage' => $a->url(), 'headline' => mb_substr($a->title, 0, 110), 'description' => $a->excerpt, 'image' => [$a->imageUrl()], 'inLanguage' => 'fa-IR',
   'datePublished' => ($a->published_at ?? $a->created_at)->toIso8601String(), 'dateModified' => $a->updated_at->toIso8601String(), 'articleSection' => $a->category, 'keywords' => $a->keyword, 'wordCount' => str_word_count(strip_tags($a->body), 0, 'آابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیئءةۀ'),
   'author' => ['@type' => 'Organization', 'name' => 'تیم می‌جا', 'url' => url('/')], 'publisher' => ['@type' => 'Organization', 'name' => 'می‌جا', 'logo' => ['@type' => 'ImageObject', 'url' => asset('icons/icon-512.png')]]],
  ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => url('/')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'مجله', 'item' => route('blog')], ['@type' => 'ListItem', 'position' => 3, 'name' => $a->title, 'item' => $a->url()]]],
]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<div class="container bl-wrap">
  <nav class="bl-bc" aria-label="مسیر"><a href="{{ url('/') }}">خانه</a> <span>›</span> <a href="{{ route('blog') }}">مجله</a> <span>›</span> <b>{{ \Illuminate\Support\Str::limit($a->title, 40) }}</b></nav>
  <article class="bl-art">
    <header>
      <span class="badge orange">{{ $a->category }}</span>
      <h1>{{ $a->title }}</h1>
      <div class="bl-meta"><span>تیم می‌جا</span><span>{{ jdate($a->published_at ?? $a->created_at, 'j F Y') }}</span><span>به‌روزرسانی: {{ jdate($a->updated_at, 'j F Y') }}</span><span>{{ fa($a->readMinutes()) }} دقیقه مطالعه</span></div>
    </header>
    <figure class="bl-cover"><img src="{{ $a->imageUrl() }}" alt="{{ $a->image_alt ?: $a->title }}" width="1200" height="675" fetchpriority="high"></figure>
    @if(count($toc) > 2)
    <nav class="bl-toc" aria-label="فهرست مطالب"><b>فهرست مطالب</b><ol>@foreach($toc as $t)<li><a href="#{{ $t['id'] }}">{{ $t['text'] }}</a></li>@endforeach</ol></nav>
    @endif
    <div class="bl-body">{!! $html !!}</div>
    <aside class="bl-cta"><div><b>ویلا یا کلبه‌ی مناسبتان را پیدا کنید</b><span>عکس واقعی، قیمت شفاف، رزرو مستقیم از میزبان و پرداخت امن</span></div><a class="btn" href="{{ route('search') }}">جستجوی اقامتگاه</a></aside>
  </article>
  @if($related->count())
  <section class="bl-rel"><h2>مقاله‌های مرتبط</h2><div class="bl-grid">@foreach($related as $r)<a class="bl-card" href="{{ $r->url() }}"><div class="bl-img"><img src="{{ $r->imageUrl() }}" alt="{{ $r->image_alt ?: $r->title }}" width="600" height="338" loading="lazy"></div><div class="bl-b"><h3>{{ $r->title }}</h3><small>{{ fa($r->readMinutes()) }} دقیقه مطالعه</small></div></a>@endforeach</div></section>
  @endif
</div>
@endsection
