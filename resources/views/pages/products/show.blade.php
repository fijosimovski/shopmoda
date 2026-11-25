@extends('layouts.layout')

@php
    $main = $product->images->firstWhere('is_main', true) ?? $product->images->first();
    $genderWord = ['men' => 'muške', 'women' => 'ženske', 'unisex' => 'unisex'][$product->gender] ?? '';
    $inStock = $product->sizes->contains(fn ($s) => $s->inStock());
@endphp

@section('pageTitle', $product->name . ' – ' . $genderWord . ' patike')
@section('metaDescription', \App\Support\Seo::description(
    $product->name . ' (' . $product->brand->name . ', ' . $genderWord . ' patike) po ceni od ' . \App\Support\Money::format($product->finalPrice()) . '. '
    . ($product->description ?: 'Originalne patike, plaćanje pouzećem, dostava širom Srbije za 1–3 radna dana.')
))
@section('ogType', 'product')
@if($main)
    @section('ogImage', $main->url())
@endif

@push('meta')
    <meta property="product:price:amount" content="{{ number_format($product->finalPrice(), 2, '.', '') }}">
    <meta property="product:price:currency" content="RSD">
    <meta property="product:availability" content="{{ $inStock ? 'in stock' : 'out of stock' }}">
    <meta property="product:brand" content="{{ $product->brand->name }}">
@endpush

{{-- Google: price, stock and photos in the search results, and the breadcrumb trail --}}
@push('jsonld')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            array_filter([
                '@type' => 'Product',
                'name' => $product->name,
                'description' => \App\Support\Seo::description($product->description, 5000) ?: null,
                'sku' => 'SM-' . $product->id,
                'image' => $product->images->map(fn ($image) => $image->url())->values()->all() ?: [asset('images/placeholder.png')],
                'brand' => ['@type' => 'Brand', 'name' => $product->brand->name],
                'category' => $product->category->name,
                'url' => route('products.show', $product),
                'offers' => [
                    '@type' => 'Offer',
                    'url' => route('products.show', $product),
                    'price' => number_format($product->finalPrice(), 2, '.', ''),
                    'priceCurrency' => 'RSD',
                    'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                    'seller' => ['@type' => 'Organization', 'name' => 'ShopModa'],
                ],
            ]),
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Početna', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Patike', 'item' => route('products.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $product->category->name, 'item' => route('products.index', ['category' => $product->category->slug])],
                    ['@type' => 'ListItem', 'position' => 4, 'name' => $product->name],
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('pageContent')
    <div class="container pt-4">
        <nav class="page-crumbs" aria-label="Putanja">
            <a href="{{ route('products.index') }}">Patike</a> /
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a> /
            <span>{{ $product->name }}</span>
        </nav>

        <div class="row g-5 mt-0">
            <!-- Images -->
            <div class="col-lg-7">
                <div class="d-flex flex-column-reverse flex-md-row gap-3">
                    @if($product->images->count() > 1)
                        <div class="d-flex flex-md-column gap-2 product-thumbs">
                            @foreach($product->images as $image)
                                <button type="button" class="product-thumb" aria-label="Prikaži fotografiju {{ $loop->iteration }}"
                                        onclick="document.getElementById('mainImage').src = this.querySelector('img').src">
                                    <img src="{{ $image->url() }}" alt="{{ $product->name }} – fotografija {{ $loop->iteration }}" width="80" height="100" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div class="product-main-image position-relative flex-grow-1">
                        <img id="mainImage" src="{{ $main?->url() ?? asset('images/placeholder.png') }}" alt="{{ $product->brand->name }} {{ $product->name }}" width="800" height="1000" fetchpriority="high">
                        @if($product->hasDiscount())
                            <span class="badge-sale position-absolute top-0 start-0 m-3">-{{ $product->discountPercentTotal() }}%</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="col-lg-5">
                <a href="{{ route('products.index', ['brand' => $product->brand->slug]) }}" class="label-caps text-decoration-none">{{ $product->brand->name }}</a>
                <h1 class="page-title page-title-sm mt-2 mb-2">{{ $product->name }}</h1>
                <p class="text-body-secondary mb-4">{{ \App\Models\Product::genderLabel($product->gender) }} &middot; {{ $product->category->name }}</p>

                <div class="product-price mb-4">
                    @if($product->hasDiscount())
                        <span class="price-sale">@money($product->finalPrice())</span>
                        <span class="price-old ms-2">@money($product->price())</span>
                    @else
                        <span>@money($product->price())</span>
                    @endif
                </div>

                {{-- Promotions this sneaker is part of, with a link to the promotion page --}}
                @php $promotions = collect([$product->activePromotion()])->filter()->concat($product->cartPromotions()); @endphp
                @foreach($promotions as $promotion)
                    <a href="{{ route('promotions.show', $promotion) }}" class="panel-sale d-flex align-items-center gap-3 py-3 mb-2 text-decoration-none text-body">
                        <span class="announce-dot text-sale flex-shrink-0"></span>
                        <span><strong class="fw-semibold">{{ $promotion->name }}</strong> &middot; {{ $promotion->badge() }}
                            <span class="text-body-secondary small">&middot; ističe {{ $promotion->ends_at->format('d.m. \u H:i') }}</span></span>
                        <i class="bi bi-chevron-right ms-auto text-sale"></i>
                    </a>
                @endforeach

                <div class="mt-4">
                    @include('partials.errors')
                </div>

                {{-- data-cart-add: sent with AJAX by public/js/cart-drawer.js (photo flies to the bag, the cart drawer opens);
                     without JavaScript it is a normal form that goes to the cart page. --}}
                <form method="POST" action="{{ route('cart.store') }}" data-validate novalidate data-cart-add data-fly-image="#mainImage">
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-cart-error></div>
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <div class="label-caps text-body mb-3">Veličina (EU)</div>
                    <div class="size-grid mb-4" data-field="size" data-label="Veličina" data-msg-required="Izaberite veličinu." data-rules="required">
                        @foreach($product->sizes as $size)
                            <div class="size-option">
                                <input type="radio" class="d-none" name="size" id="size{{ $size->id }}" value="{{ $size->size }}"
                                       @disabled(!$size->inStock()) @checked(old('size') == $size->size)>
                                <label for="size{{ $size->id }}" title="{{ $size->inStock() ? 'Na stanju: ' . $size->stock : 'Rasprodato' }}">{{ $size->size }}</label>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <div>
                            <label class="visually-hidden" for="quantity">Količina</label>
                            <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" class="form-control text-center quantity-input" data-label="Količina" data-rules="required|integer|min:1|max:10">
                        </div>
                        @if($inStock)
                            <button class="btn btn-ink flex-grow-1" data-cart-add-button
                                    data-loading-text="Dodavanje..." data-added-text="Dodato">Dodaj u korpu</button>
                        @else
                            <button class="btn btn-outline-ink flex-grow-1" disabled>Rasprodato</button>
                        @endif
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-4 small text-body-secondary">
                    <span>Plaćanje pouzećem</span>
                    <span>Dostava za 1–3 radna dana, plaća kupac</span>
                    <a href="{{ route('how-to-order') }}#returns" class="text-body-secondary">Povraćaj i zamena</a>
                </div>

                @if($product->description)
                    <div class="border-top mt-5 pt-4">
                        <h2 class="label-caps text-body fw-semibold mb-3">Opis</h2>
                        <p class="text-body-secondary text-pre-line lh-lg mb-0">{{ $product->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if($related->isNotEmpty())
            <section class="home-section pb-2">
                <div class="section-head">
                    <h2 class="section-title">Možda vam se <em>dopadne</em></h2>
                </div>
                <div class="row row-cols-2 row-cols-md-4 g-3 g-md-4 gy-5">
                    @foreach($related as $item)
                        <div class="col">@include('partials.product-card', ['product' => $item])</div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
