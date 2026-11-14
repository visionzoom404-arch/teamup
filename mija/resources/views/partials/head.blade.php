<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#E8860C">
<meta name="color-scheme" content="light dark">
<script>(function(){try{if(/MijaApp\//.test(navigator.userAgent)||navigator.standalone||(window.matchMedia&&matchMedia('(display-mode: standalone)').matches))document.documentElement.classList.add('is-app')}catch(e){}try{var t=localStorage.getItem('mija-theme');if(t!=='dark'&&t!=='light')t=(window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light';document.documentElement.setAttribute('data-theme',t);if(t==='dark'){var m=document.querySelector('meta[name=theme-color]');if(m)m.setAttribute('content','#17120D')}}catch(e){document.documentElement.setAttribute('data-theme','light')}})()</script>
@include('partials.seo')
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="64x64">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="می‌جا">
<link rel="preload" href="{{ asset('fonts/Estedad-800.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/lux.css') }}?v={{ filemtime(public_path('css/lux.css')) }}">
<link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ filemtime(public_path('css/glass.css')) }}">
<link rel="stylesheet" href="{{ asset('css/resp.css') }}?v={{ filemtime(public_path('css/resp.css')) }}">
<link rel="stylesheet" href="{{ asset('css/more.css') }}?v={{ filemtime(public_path('css/more.css')) }}">
<link rel="stylesheet" href="{{ asset('vendor/swiper-bundle.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/dark.auto.css') }}?v={{ filemtime(public_path('css/dark.auto.css')) }}">
<link rel="stylesheet" href="{{ asset('css/dark.css') }}?v={{ filemtime(public_path('css/dark.css')) }}">
<link rel="stylesheet" href="{{ asset('css/liquid.css') }}?v={{ filemtime(public_path('css/liquid.css')) }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
@auth<meta name="notice-last" content="{{ (int) auth()->user()->notices()->max('id') }}">@endauth
