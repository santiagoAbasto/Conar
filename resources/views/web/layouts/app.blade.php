<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $metaCanonical }}">
<meta name="robots" content="{{ $metaRobots }}">
<meta name="referrer" content="strict-origin-when-cross-origin">

@if($metaKeywords)
<meta name="keywords" content="{{ $metaKeywords }}">
@endif

<link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon/favicon-96x96.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
<meta name="theme-color" content="{{ $metaThemeColor }}">
<meta name="application-name" content="{{ $metaApplicationName }}">
<meta name="apple-mobile-web-app-title" content="{{ $metaApplicationName }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="mobile-web-app-capable" content="yes">
<meta name="format-detection" content="telephone=no">

<meta property="og:locale" content="{{ $metaLocale }}">
<meta property="og:type" content="{{ $metaOgType }}">
<meta property="og:site_name" content="{{ $metaSiteName }}">
<meta property="og:url" content="{{ $metaCanonical }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:image" content="{{ $metaOgImage }}">
<meta property="og:image:secure_url" content="{{ $metaOgImage }}">
<meta property="og:image:width" content="{{ $metaOgImageWidth }}">
<meta property="og:image:height" content="{{ $metaOgImageHeight }}">
<meta property="og:image:alt" content="{{ $metaOgImageAlt }}">

<meta name="twitter:card" content="{{ $metaTwitterCard }}">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $metaOgImage }}">
<meta name="twitter:image:alt" content="{{ $metaOgImageAlt }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

@vite(['resources/css/web.css','resources/js/web.js'])

@if($metaStructuredData)
<script type="application/ld+json">{!! $metaStructuredData !!}</script>
@endif

@stack('head')

<x-tracker />

</head>

<body>

@include('web.layouts.header')

<main>

@yield('content')

</main>

@include('web.layouts.footer')

{{-- TOAST NOTIFICATION --}}
@if(session('success') || session('error'))
<script>
    window.__webFlashToast = {
        message: @json(session('success') ?? session('error')),
        type: @json(session('error') ? 'error' : 'success')
    };
</script>
@endif

@stack('scripts')

</body>

</html>
