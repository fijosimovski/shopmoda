{{--
    <head> of every shop page. A page can set:
      @section('pageTitle')        - "Nike Air Max 90" => <title>Nike Air Max 90 | ShopModa</title>
      @section('metaDescription')  - text for Google and link previews (max ~155 characters)
      @section('canonical')        - the official URL (default: App\Support\Seo::canonical())
      @section('robots')           - e.g. "noindex, follow" (default: App\Support\Seo::robots())
      @section('ogType')           - "website" (default) or "product"
      @section('ogImage')          - picture for Facebook / Instagram / Viber / WhatsApp previews (default: the logo image)
      @push('jsonld')              - structured data (<script type="application/ld+json">)
    Section values are already HTML-escaped by Blade, so they are printed with {!! !!} here.
--}}
@php
    $seoTitle = trim($__env->yieldContent('pageTitle'));
    $seoTitle = $seoTitle !== '' ? $seoTitle . ' | ShopModa' : 'ShopModa | Patike za trčanje, lifestyle i svaki dan';
    $seoDescription = trim(preg_replace('/\s+/u', ' ', $__env->yieldContent('metaDescription')));
    $seoDescription = $seoDescription !== '' ? $seoDescription : e('ShopModa – originalne patike za trčanje, lifestyle i svakodnevnu udobnost. Nike, Adidas, Asics i drugi brendovi. Plaćanje pouzećem, dostava širom Srbije za 1–3 radna dana.');
    $seoCanonical = trim($__env->yieldContent('canonical')) ?: e(\App\Support\Seo::canonical());
    $seoRobots = trim($__env->yieldContent('robots')) ?: \App\Support\Seo::robots();
    $seoImage = trim($__env->yieldContent('ogImage'));
    $seoDefaultImage = $seoImage === '';
    $seoImage = $seoDefaultImage ? asset('images/og-image.png') : $seoImage;
@endphp
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{!! $seoTitle !!}</title>
    <meta name="description" content="{!! $seoDescription !!}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{!! $seoCanonical !!}">

    <!-- Link previews (Facebook, Instagram, Viber, WhatsApp, LinkedIn) and X / Twitter -->
    <meta property="og:site_name" content="ShopModa">
    <meta property="og:locale" content="sr_RS">
    <meta property="og:type" content="{{ trim($__env->yieldContent('ogType')) ?: 'website' }}">
    <meta property="og:title" content="{!! $seoTitle !!}">
    <meta property="og:description" content="{!! $seoDescription !!}">
    <meta property="og:url" content="{!! $seoCanonical !!}">
    <meta property="og:image" content="{!! $seoImage !!}">
    @if($seoDefaultImage)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/png">
    @endif
    <meta property="og:image:alt" content="{!! $seoTitle !!}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{!! $seoTitle !!}">
    <meta name="twitter:description" content="{!! $seoDescription !!}">
    <meta name="twitter:image" content="{!! $seoImage !!}">
    @stack('meta')

    <!-- Icons: browser tab, phone home screen -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#1c1917">

    <!-- Bootstrap 5 + Bootstrap Icons (from a CDN, nothing to install) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Shop fonts: Cormorant Garamond (headings), Jost (text), Inter Tight (logo) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@400;500;600&family=Inter+Tight:wght@800&display=swap" rel="stylesheet">

    <!-- Our own styles -->
    <link href="{{ asset('css/shopmoda.css') }}" rel="stylesheet">

    <!-- Structured data for Google (products, breadcrumbs, the shop) -->
    @stack('jsonld')
</head>
