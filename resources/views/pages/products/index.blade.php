@extends('layouts.layout')

@php
    // Page title from the strongest filter: sale, category, model, brand, gender; else "Sve patike"
    $heading = match (true) {
        (bool) request('sale') => 'Patike na akciji',
        (bool) request('category') => $categories->firstWhere('slug', request('category'))?->name,
        (bool) request('model') => $modelGroups->flatten(1)->firstWhere('slug', request('model'))?->name,
        (bool) request('brand') => $brands->firstWhere('slug', request('brand'))?->name,
        request('gender') === 'men' => 'Muške patike',
        request('gender') === 'women' => 'Ženske patike',
        request('gender') === 'unisex' => 'Unisex patike',
        default => null,
    } ?? 'Sve patike';

    // <title> and description for Google: "Nike patike", "Running – patike", page 2...
    $seoHeading = match (true) {
        (bool) request('q') => 'Pretraga: ' . request('q'),
        (bool) request('brand') && !request('category') && !request('model') && !request('sale') => $heading . ' patike',
        default => $heading,
    };
    $seoPage = $products->currentPage() > 1 ? ' – strana ' . $products->currentPage() : '';
@endphp

@section('pageTitle', $seoHeading . $seoPage)
@section('metaDescription', $seoHeading . ' u ShopModa prodavnici: ' . \App\Support\Sr::plural($products->total(), 'model', 'modela', 'modela') . ' na stanju. Originalne patike, plaćanje pouzećem, dostava širom Srbije za 1–3 radna dana.')

@section('pageContent')
    <section class="container pt-4">
        <nav class="page-crumbs" aria-label="Putanja">
            <a href="{{ route('home') }}">Početna</a> / <a href="{{ route('products.index') }}">Patike</a>@if($heading !== 'Sve patike') / {{ $heading }}@endif
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-3 pb-4 rule-ink">
            <h1 class="page-title">
                @if(request('q'))
                    Rezultati za <em>„{{ request('q') }}“</em>
                @else
                    {{ $heading }}
                @endif
                <span class="page-title-count">{{ $products->total() }}</span>
            </h1>
            <form method="GET" action="{{ route('products.index') }}" class="d-flex align-items-center gap-2">
                {{-- keep the current filters when changing the sort --}}
                @foreach(request()->except(['sort', 'page']) as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <label for="sort" class="label-caps text-nowrap">Sortiraj</label>
                <select name="sort" id="sort" class="form-select" onchange="this.form.submit()">
                    <option value="newest" @selected($sort === 'newest')>Najnovije</option>
                    <option value="best" @selected($sort === 'best')>Najprodavanije</option>
                    <option value="price_asc" @selected($sort === 'price_asc')>Cena: od najniže</option>
                    <option value="price_desc" @selected($sort === 'price_desc')>Cena: od najviše</option>
                </select>
            </form>
        </div>
    </section>

    <div class="container py-4 py-lg-5">
        <div class="row g-5">
            <!-- Filters -->
            <aside class="col-lg-3" aria-label="Filteri">
                <form method="GET" action="{{ route('products.index') }}" class="filters">
                    <h2 class="label-caps text-body fw-semibold mb-4">Filteri</h2>

                    <div class="mb-4">
                        <label class="form-label" for="q">Pretraga</label>
                        <input type="search" class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Naziv ili model...">
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="gender">Za</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">Sve</option>
                            @foreach(\App\Models\Product::GENDERS as $gender)
                                <option value="{{ $gender }}" @selected(request('gender') === $gender)>{{ \App\Models\Product::genderLabel($gender) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="brand">Brend</label>
                        <select class="form-select" id="brand" name="brand">
                            <option value="">Svi brendovi</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->slug }}" @selected(request('brand') === $brand->slug)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="category">Kategorija</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Sve kategorije</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="model">Model</label>
                        <select class="form-select" id="model" name="model">
                            <option value="">Svi modeli</option>
                            @foreach($modelGroups as $brandName => $models)
                                <optgroup label="{{ $brandName }}">
                                    @foreach($models as $model)
                                        <option value="{{ $model->slug }}" @selected(request('model') === $model->slug)>{{ $model->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="border-top pt-4 mb-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="in_stock" name="in_stock" value="1" @checked(request('in_stock'))>
                            <label class="form-check-label" for="in_stock">Samo na stanju</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sale" name="sale" value="1" @checked(request('sale'))>
                            <label class="form-check-label text-sale" for="sale">Samo na akciji</label>
                        </div>
                    </div>

                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <div class="d-flex align-items-center gap-4">
                        <button class="btn btn-ink">Primeni</button>
                        <a href="{{ route('products.index') }}" class="link-caps">Poništi</a>
                    </div>
                </form>
            </aside>

            <!-- Products -->
            <div class="col-lg-9">
                @if($products->isEmpty())
                    <div class="empty-state">
                        <p class="serif-lg mb-2">Nema patika koje odgovaraju ovim filterima.</p>
                        <a href="{{ route('products.index') }}" class="link-caps">Poništi filtere</a>
                    </div>
                @else
                    <div class="row row-cols-2 row-cols-md-3 g-3 g-md-4 gy-5">
                        @foreach($products as $product)
                            <div class="col">@include('partials.product-card', ['product' => $product])</div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-center mt-5">{{ $products->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
