<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#E8860C">
@include('partials.seo')
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="می‌جا">
<link rel="preload" href="{{ asset('fonts/Estedad-800.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/lux.css') }}?v={{ filemtime(public_path('css/lux.css')) }}">
<link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ filemtime(public_path('css/glass.css')) }}">
<link rel="stylesheet" href="{{ asset('css/resp.css') }}?v={{ filemtime(public_path('css/resp.css')) }}">
<link rel="stylesheet" href="{{ asset('vendor/swiper-bundle.min.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
