@php
  $siteName = 'می‌جا';
  $title = trim($__env->yieldContent('title')) ?: 'رزرو ویلا و کلبه';
  $fullTitle = $title.' | '.$siteName;
  $desc = trim($__env->yieldContent('desc')) ?: 'می‌جا — جای خوب، سفر خوب. رزرو آنلاین ویلا، کلبه، سوئیت و اقامتگاه بوم‌گردی در شمال و سراسر ایران؛ مستقیم از میزبان، با قیمت شفاف و پرداخت امن.';
  $private = request()->is('panel*', 'host*', 'admin*', 'login', 'register', 'bookings*', 'notices', 'profile', 'gateway*', 'pay*', 'impersonate*', 'offline');
  $robots = trim($__env->yieldContent('robots')) ?: ($private ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1');
  $canonical = trim($__env->yieldContent('canonical')) ?: (request()->path() === '/' ? url('/') : request()->url());
  $ogImage = trim($__env->yieldContent('ogimage')) ?: asset('img/og.png');
  $keywords = trim($__env->yieldContent('keywords')) ?: 'رزرو ویلا, اجاره ویلا, رزرو کلبه, اجاره کلبه, ویلا شمال, کلبه جنگلی, اجاره روزانه ویلا, ویلا استخردار, می‌جا, mija';
@endphp
<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($desc), 300, '') }}">
<meta name="keywords" content="{{ $keywords }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">
<title>{{ $fullTitle }}</title>
<meta name="author" content="می‌جا">
<meta property="og:site_name" content="می‌جا — رزرو ویلا و کلبه">
<meta property="og:locale" content="fa_IR">
<meta property="og:type" content="{{ trim($__env->yieldContent('ogtype')) ?: 'website' }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($desc), 200, '') }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($desc), 200, '') }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@if($gv = setting('google_verify'))<meta name="google-site-verification" content="{{ $gv }}">@endif
@if($bv = setting('bing_verify'))<meta name="msvalidate.01" content="{{ $bv }}">@endif
@unless($private)
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [
  ['@type' => 'Organization', '@id' => url('/').'#org', 'name' => 'می‌جا', 'alternateName' => ['Mija', 'mija.click'], 'url' => url('/'), 'logo' => asset('img/mija-logo.png'), 'description' => 'پلتفرم رزرو آنلاین ویلا، کلبه و اقامتگاه در سراسر ایران'],
  ['@type' => 'WebSite', '@id' => url('/').'#web', 'url' => url('/'), 'name' => 'می‌جا — رزرو ویلا و کلبه', 'inLanguage' => 'fa-IR', 'publisher' => ['@id' => url('/').'#org'],
    'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endunless
@stack('jsonld')
@if($ga = setting('ga4_id'))<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $ga }}');</script>@endif
