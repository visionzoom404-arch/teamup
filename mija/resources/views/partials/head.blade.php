<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#14110E">
<meta name="description" content="@yield('desc', 'می‌جا — جای خوب، سفر خوب. رزرو آنلاین ویلا، کلبه و اقامتگاه در سراسر ایران؛ مستقیم از میزبان')">
<title>@yield('title', 'رزرو ویلا و کلبه') | می‌جا</title>
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="می‌جا">
<meta property="og:title" content="@yield('title', 'رزرو ویلا و کلبه') | می‌جا">
<meta property="og:image" content="{{ asset('img/mija-logo.png') }}">
<link rel="preload" href="{{ asset('fonts/Estedad-800.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('css/lux.css') }}?v={{ filemtime(public_path('css/lux.css')) }}">
<link rel="stylesheet" href="{{ asset('vendor/swiper-bundle.min.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
