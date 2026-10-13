@props(['routeName'])
@php
    $page = config('public_pages.'.$routeName);
    $baseUrl = rtrim((string) config('app.url'), '/');
    $canonicalUrl = $baseUrl.route($routeName, [], false);
    $imageUrl = $baseUrl.'/share-card.png';
@endphp
<title>{{ $page['title'] }}</title>
<meta name="description" content="{{ $page['description'] }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="icon" type="image/svg+xml" href="{{ $baseUrl }}/icon.svg">
<link rel="icon" type="image/x-icon" href="{{ $baseUrl }}/favicon.ico">
<link rel="sitemap" type="application/xml" href="{{ $baseUrl }}/sitemap.xml">
<meta property="og:type" content="website">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="Plataforma Jogo da Velha">
<meta property="og:title" content="{{ $page['title'] }}">
<meta property="og:description" content="{{ $page['description'] }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Peças X e O em um tabuleiro geométrico com a marca Plataforma Jogo da Velha">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $page['title'] }}">
<meta name="twitter:description" content="{{ $page['description'] }}">
<meta name="twitter:image" content="{{ $imageUrl }}">
<meta name="twitter:image:alt" content="Peças X e O em um tabuleiro geométrico com a marca Plataforma Jogo da Velha">
