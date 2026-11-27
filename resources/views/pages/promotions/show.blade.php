@extends('layouts.layout')

@section('pageTitle') {{ $promotion->name }} @endsection
@section('metaDescription') {{ \App\Support\Seo::description($promotion->description ?: $promotion->name . ' – ' . $promotion->badge() . '. Akcija u ShopModa prodavnici, plaćanje pouzećem.') }} @endsection
@if($promotion->imageUrl())
    @section('ogImage', $promotion->imageUrl())
@endif
@if($promotion->hasEnded())
    @section('robots', 'noindex, follow')
@endif

@section('pageContent')
    <!-- Banner -->
    <section class="container pt-4">
        <nav class="page-crumbs mb-3" aria-label="Putanja">
            <a href="{{ route('home') }}">Početna</a> / Akcije / {{ $promotion->name }}
        </nav>
        <div class="row g-0 home-cover-slide">
            <div class="col-lg-6 d-flex flex-column justify-content-between gap-4 p-4 p-md-5">
                <div>
                    <span class="badge-promo badge-promo-lg">@unless($promotion->hasEnded())<span class="announce-dot"></span>@endunless{{ $promotion->badge() }}</span>
                    <h1 class="display-serif mt-4 mb-3">{{ $promotion->name }}</h1>
                    @if($promotion->description)
                        <p class="lead-muted mb-0">{{ $promotion->description }}</p>
                    @endif
                </div>

                @if($promotion->hasEnded())
                    <div class="label-caps">Završena {{ $promotion->ends_at->format('d.m.Y.') }}</div>
                @elseif($promotion->isUpcoming())
                    <div>
                        <div class="label-caps mb-2">Počinje za</div>
                        <div class="home-cover-countdown" data-countdown="{{ $promotion->starts_at->toIso8601String() }}" data-countdown-done="sada – osvežite stranicu">{{ $promotion->starts_at->format('d.m.Y. H:i') }}</div>
                    </div>
                @else
                    <div>
                        <div class="label-caps mb-2">Ističe za <span class="normal-case">({{ $promotion->ends_at->format('d.m.Y. \u H:i') }})</span></div>
                        <div class="home-cover-countdown" data-countdown="{{ $promotion->ends_at->toIso8601String() }}">{{ $promotion->ends_at->format('d.m.Y. H:i') }}</div>
                    </div>
                @endif
            </div>
            <div class="col-lg-6 home-cover-media">
                @if($promotion->imageUrl())
                    <img src="{{ $promotion->imageUrl() }}" alt="{{ $promotion->name }}">
                @endif
            </div>
        </div>
    </section>

    <div class="container pb-4">
        @if($promotion->hasEnded())
            <!-- Ended -->
            <div class="empty-state">
                <h2 class="serif-lg mb-2">Ova akcija je završena</h2>
                <p class="text-body-secondary mb-4">Hvala na poseti. Pogledajte šta je trenutno u ponudi.</p>
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-4">
                    <a href="{{ route('products.index', ['sale' => 1]) }}" class="btn btn-ink">Patike na akciji</a>
                    <a href="{{ route('products.index') }}" class="link-caps">Sve patike</a>
                </div>
            </div>

            @if($otherPromotions->isNotEmpty())
                <div class="rule-ink-top pt-3">
                    <h2 class="label-caps text-body fw-semibold mb-2">Aktuelne akcije</h2>
                    @foreach($otherPromotions as $other)
                        <a href="{{ route('promotions.show', $other) }}" class="list-row">
                            <span><strong class="fw-semibold">{{ $other->name }}</strong> <span class="text-sale">&middot; {{ $other->badge() }}</span></span>
                            <span class="text-body-secondary">Ističe {{ $other->ends_at->format('d.m. \u H:i') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @else
            <!-- How it works -->
            <div class="rule-ink-top mt-5 pt-3">
                <h2 class="label-caps text-body fw-semibold mb-2">Kako funkcioniše</h2>
                <p class="mb-0 lh-lg">
                    {{ $promotion->howItWorks() }}
                    @if($promotion->isUpcoming())
                        <strong class="fw-semibold">Cene se menjaju kada akcija počne.</strong>
                    @endif
                </p>
                @if($promotion->members_only)
                    <p class="mt-2 mb-0 lh-lg">
                        <strong class="fw-semibold">Samo za registrovane kupce.</strong>
                        @guest
                            Cene ispod još ne uključuju ovaj popust: <a href="{{ route('login') }}" class="text-body">prijavite se</a> ili <a href="{{ route('register') }}" class="text-body">napravite nalog</a> da biste ga dobili.
                        @else
                            Prijavljeni ste, popust je vaš.
                        @endguest
                    </p>
                @endif
            </div>

            <section class="home-section pt-5">
                <div class="section-head">
                    <h2 class="section-title">{{ \App\Support\Sr::plural($products->total(), 'model', 'modela', 'modela') }} <em>na ovoj akciji</em></h2>
                    <form method="GET" action="{{ route('promotions.show', $promotion) }}" class="d-flex align-items-center gap-2">
                        <label for="sort" class="label-caps text-nowrap">Sortiraj</label>
                        <select name="sort" id="sort" class="form-select" onchange="this.form.submit()">
                            <option value="newest" @selected($sort === 'newest')>Najnovije</option>
                            <option value="best" @selected($sort === 'best')>Najprodavanije</option>
                        </select>
                    </form>
                </div>

                @if($products->isEmpty())
                    <div class="empty-state">
                        <p class="serif-lg mb-3">Trenutno nema patika na ovoj akciji.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-ink">Svi artikli</a>
                    </div>
                @else
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4 gy-5">
                        @foreach($products as $product)
                            <div class="col">@include('partials.product-card', ['product' => $product])</div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-center mt-5">{{ $products->links() }}</div>
                @endif
            </section>
        @endif
    </div>
@endsection

{{-- pushOnce: the announcement bar loads the same script --}}
@pushOnce('scripts', 'countdown')
    <script src="{{ asset('js/countdown.js') }}"></script>
@endPushOnce
