@extends('errors.shop')

@section('code', '404')
@section('errorTitle', 'Stranica nije pronađena')
@section('errorText', 'Stranica koju tražite ne postoji, premeštena je ili proizvod više nije u ponudi. Probajte pretragu ili pogledajte kategorije.')

@section('errorExtra')
    <form class="error-search d-flex mx-auto mb-4" action="{{ route('products.index') }}" method="GET" role="search">
        <label for="errorSearch" class="visually-hidden">Pretraga patika</label>
        <input id="errorSearch" class="form-control" type="search" name="q" placeholder="Npr. Nike Air Max, Gazelle...">
        <button class="btn btn-ink" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Traži</span></button>
    </form>

    @php($errorCategories = \App\Models\Category::orderBy('name')->get())
    @if($errorCategories->isNotEmpty())
        <div class="d-flex flex-wrap justify-content-center gap-2">
            @foreach($errorCategories as $category)
                <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="badge-promo text-decoration-none">{{ $category->name }}</a>
            @endforeach
        </div>
    @endif
@endsection
