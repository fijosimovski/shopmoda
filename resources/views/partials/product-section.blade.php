{{-- Homepage product row: title, "See all" link and 4 product cards. Needs $title, $list, $moreLink. --}}
@if($list->isNotEmpty())
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h2 class="h4 mb-0">{{ $title }}</h2>
            <a href="{{ $moreLink }}" class="text-decoration-none">Pogledaj sve <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            @foreach($list as $product)
                <div class="col">@include('partials.product-card', ['product' => $product])</div>
            @endforeach
        </div>
    </section>
@endif
