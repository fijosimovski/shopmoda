@extends('layouts.layout')

{{-- No pageTitle: the homepage gets the full "ShopModa | Patike za trčanje, lifestyle i košarku" title (fixed/head.blade.php) --}}
@section('metaDescription', 'Originalne patike za trčanje, lifestyle i košarku – Nike, Adidas, Asics i drugi brendovi. Plaćanje pouzećem i dostava širom Srbije za 1–3 radna dana.')

{{-- Google: who the shop is (logo, Instagram) and the search box in the results --}}
@push('jsonld')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            array_filter([
                '@type' => 'Organization',
                '@id' => url('/') . '#organization',
                'name' => 'ShopModa',
                'url' => url('/'),
                'logo' => asset('images/logo.png'),
                'sameAs' => array_values(array_filter([\App\Models\Setting::getValue(\App\Models\Setting::INSTAGRAM_URL)])) ?: null,
            ]),
            [
                '@type' => 'WebSite',
                '@id' => url('/') . '#website',
                'name' => 'ShopModa',
                'url' => url('/'),
                'inLanguage' => 'sr-Latn',
                'publisher' => ['@id' => url('/') . '#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('products.index') . '?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('pageContent')
    @if($covers->isNotEmpty())
        <!-- Promotion cover: shown instead of the hero while promotions are running (slideshow when there are more) -->
        <div class="container mt-4">
            <section id="promoCover" class="carousel slide home-cover" data-bs-ride="carousel" data-bs-interval="6000" aria-label="Akcije">
                <div class="carousel-inner">
                    @foreach($covers as $promotion)
                        <div @class(['carousel-item', 'active' => $loop->first])>
                            <div class="row g-0 home-cover-slide">
                                <div class="col-lg-6 d-flex flex-column justify-content-between gap-4 p-4 p-md-5">
                                    <div>
                                        <span class="badge-promo badge-promo-lg"><span class="announce-dot"></span>{{ $promotion->badge() }}</span>
                                        @if($promotion->members_only)<span class="label-caps ms-2">Samo za registrovane kupce</span>@endif
                                        <h1 class="display-serif mt-4 mb-3">{{ $promotion->name }}</h1>
                                        @if($promotion->description)
                                            <p class="lead-muted mb-0">{{ Str::limit($promotion->description, 160) }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="label-caps mb-2">Ističe za</div>
                                        <div class="home-cover-countdown mb-4" data-countdown="{{ $promotion->ends_at->toIso8601String() }}">{{ $promotion->ends_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</div>
                                        <div class="d-flex flex-wrap align-items-center gap-4">
                                            <a href="{{ route('promotions.show', $promotion) }}" class="btn btn-ink btn-lg">Pogledaj akciju</a>
                                            <a href="{{ route('products.index') }}" class="link-caps">Svi artikli</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 home-cover-media">
                                    @if($promotion->imageUrl())
                                        <img src="{{ $promotion->imageUrl() }}" alt="{{ $promotion->name }}">
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            @if($covers->count() > 1)
                <div class="d-flex justify-content-between align-items-center pt-3">
                    <div class="d-flex align-items-center gap-2 home-cover-dots">
                        @foreach($covers as $promotion)
                            <button type="button" data-bs-target="#promoCover" data-bs-slide-to="{{ $loop->index }}" @class(['active' => $loop->first])
                                    @if($loop->first) aria-current="true" @endif aria-label="{{ $promotion->name }}"></button>
                        @endforeach
                        <span class="small text-body-secondary ms-2">{{ \App\Support\Sr::plural($covers->count(), 'akcija', 'akcije', 'akcija') }}</span>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-square" type="button" data-bs-target="#promoCover" data-bs-slide="prev" aria-label="Prethodna akcija"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn btn-square btn-square-dark" type="button" data-bs-target="#promoCover" data-bs-slide="next" aria-label="Sledeća akcija"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- Hero (the first featured sneaker is the cover photo) -->
        @php($coverProduct = $featured->first(fn ($product) => $product->mainImage))
        <section class="container mt-4">
            <div class="row g-5 align-items-end m-0 m-sm-3">
                <div class="col-lg-6 py-lg-5">
                    <div class="label-caps">Lifestyle · Comfort · Streetwear</div>
                    <h1 class="display-serif display-serif-xl my-4">Pronađite svoj<br><em>sledeći</em> par.</h1>
                    <p class="lead-muted mb-5">Obuća za svaki dan — od brendova koje volite. Izaberite par, a mi ga donosimo na vašu adresu.</p>
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        <a href="{{ route('products.index') }}" class="btn btn-ink btn-lg">Kupujte sada</a>
                        <a href="{{ route('products.index', ['sale' => 1]) }}" class="link-caps">Na akciji</a>
                    </div>
                </div>
                @if($coverProduct)
                    <div class="col-lg-6">
                        <figure class="mb-0">
                            <a href="{{ route('products.show', $coverProduct) }}" class="d-block hero-media">
                                <img src="{{ $coverProduct->mainImage->url() }}" alt="{{ $coverProduct->name }}">
                            </a>
                            <figcaption class="d-flex justify-content-between gap-3 pt-3 small text-body-secondary">
                                <span>Na naslovnoj — {{ $coverProduct->brand->name }} {{ $coverProduct->name }}</span>
                                <span>@money($coverProduct->finalPrice())</span>
                            </figcaption>
                        </figure>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <div class="container mt-5">
        <!-- Delivery, returns, support -->
        @include('partials.perks')

        <!-- Brands slider (2 brands per slide) -->
        @if($brands->isNotEmpty())
            <section class="home-section">
                <div class="section-head">
                    <h2 class="section-title">Naši <em>brendovi</em></h2>
                    @if($brands->count() > 2)
                        <div class="d-flex align-items-center gap-1">
                            <button class="btn btn-square border-0" type="button" data-bs-target="#brandsSlider" data-bs-slide="prev" aria-label="Prethodni brendovi"><i class="bi bi-chevron-left"></i></button>
                            <button class="btn btn-square border-0" type="button" data-bs-target="#brandsSlider" data-bs-slide="next" aria-label="Sledeći brendovi"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    @endif
                </div>

                <div id="brandsSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                    <div class="carousel-inner">
                        @foreach($brands->chunk(2) as $pair)
                            <div class="carousel-item @if($loop->first) active @endif">
                                <div class="row g-3 g-md-4">
                                    @foreach($pair as $brand)
                                        <div class="col-6">
                                            <a href="{{ route('products.index', ['brand' => $brand->slug]) }}" class="brand-slide d-block">
                                                <div class="brand-slide-media">
                                                    @if($brand->imageUrl())
                                                        <img src="{{ $brand->imageUrl() }}" alt="{{ $brand->name }}" loading="lazy">
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mt-3">
                                                    <span class="brand-slide-name">{{ $brand->name }}</span>
                                                    <span class="small text-body-secondary">{{ \App\Support\Sr::plural($brand->products_count, 'model', 'modela', 'modela') }} · <span class="text-decoration-underline">Pogledaj {{ $brand->name }}</span></span>
                                                </div>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- Categories: text tiles (categories have no images) -->
        @if($categories->isNotEmpty())
            <section class="home-section">
                <div class="section-head">
                    <h2 class="section-title">Kupujte po <em>kategoriji</em></h2>
                    <a href="{{ route('products.index') }}" class="link-caps">Svi artikli</a>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-0 category-tiles">
                    @foreach($categories as $category)
                        <div class="col">
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="category-tile">
                                <span class="category-tile-count">{{ $category->products_count }}</span>
                                <span>
                                    <span class="category-tile-name">{{ $category->name }}</span>
                                    <span class="category-tile-meta">{{ \App\Support\Sr::plural($category->products_count, 'model', 'modela', 'modela') }} · <span class="text-decoration-underline">Pogledaj</span></span>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    @foreach([
        ['Izdvojeno', '', $featured, route('products.index'), false],
        ['Najprodavanije', 'patike', $bestSellers, route('products.index', ['sort' => 'best']), true],
        ['Novo', 'u ponudi', $newest, route('products.index', ['sort' => 'newest']), false],
    ] as [$title, $titleItalic, $list, $moreLink, $band])
        @if($list->isNotEmpty())
            <section @class(['home-section', 'home-band' => $band])>
                <div class="container">
                    <div class="section-head">
                        <h2 class="section-title">{{ $title }} @if($titleItalic)<em>{{ $titleItalic }}</em>@endif</h2>
                        <a href="{{ $moreLink }}" class="link-caps">Pogledaj sve</a>
                    </div>
                    <div class="row row-cols-2 row-cols-md-4 g-3 g-md-4 gy-5">
                        @foreach($list as $product)
                            <div class="col">@include('partials.product-card', ['product' => $product])</div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach

    <!-- All models (parents only, e.g. "Air Jordan 1" instead of every colorway) -->
    @if($models->isNotEmpty())
        <section class="home-section">
            <div class="container">
                <div class="section-head">
                    <h2 class="section-title">Svi <em>modeli</em></h2>
                    <div class="d-flex align-items-center gap-4">
                        <a href="{{ route('products.index') }}" class="link-caps">Svi artikli </a>
                        <div class="d-flex gap-1">
                            <button class="btn btn-square" type="button" data-models-prev aria-label="Prethodni modeli"><i class="bi bi-chevron-left"></i></button>
                            <button class="btn btn-square btn-square-dark" type="button" data-models-next aria-label="Sledeći modeli"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
                <!-- Slider: 1 card on phones, 2 on small, 3 on tablets, 4 on desktop; moves one card per step -->
                <div id="modelsSlider" class="row flex-nowrap g-3 g-md-4 models-slider" data-interval="4000">
                    @foreach($models as $model)
                        <div class="col-10 col-sm-6 col-md-4 col-lg-3">
                            <a href="{{ route('products.index', ['model' => $model->slug]) }}" class="model-card d-block h-100">
                                <div class="model-card-media">
                                    @if($model->imageUrl())
                                        <img src="{{ $model->imageUrl() }}" alt="{{ $model->brand->name }} {{ $model->name }}" loading="lazy">
                                    @endif
                                </div>
                                <div class="p-3 p-md-4">
                                    <div class="product-card-brand">{{ $model->brand->name }}</div>
                                    <div class="model-card-name">{{ $model->name }}</div>
                                    <div class="small text-body-secondary">{{ \App\Support\Sr::plural($model->products_count, 'boja', 'boje', 'boja') }}</div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/models-slider.js') }}"></script>
    <script src="{{ asset('js/cover-height.js') }}"></script>
@endpush

{{-- pushOnce: the announcement bar loads the same script --}}
@pushOnce('scripts', 'countdown')
    <script src="{{ asset('js/countdown.js') }}"></script>
@endPushOnce
