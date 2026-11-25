{{-- Usage: @include('partials.product-card', ['product' => $product]) --}}
<div class="product-card position-relative h-100">
    <a href="{{ route('products.show', $product) }}" class="product-card-image d-block position-relative" tabindex="-1" aria-hidden="true">
        <img src="{{ $product->mainImage?->url() ?? asset('images/placeholder.png') }}" alt="{{ $product->name }}" width="800" height="1000" loading="lazy" decoding="async">
        {{-- Price discount (own sale or product-level promotion) and cart-level promotions ("2nd pair -20%") --}}
        <div class="position-absolute top-0 start-0 m-3 d-flex flex-column align-items-start gap-1">
            @if($product->hasDiscount())
                <span class="badge-sale">-{{ $product->discountPercentTotal() }}%</span>
            @endif
            {{-- Whole-shop cart promotions would be on every card, they are shown on the product page and the cover instead --}}
            @foreach($product->cartPromotions()->reject->applies_to_all as $promotion)
                <span class="badge-promo">{{ $promotion->badge() }}</span>
            @endforeach
        </div>
    </a>
    <div class="pt-3">
        <div class="product-card-brand">{{ $product->brand->name }}</div>
        <a href="{{ route('products.show', $product) }}" class="product-card-name stretched-link">{{ $product->name }}</a>
        <div class="product-card-price mt-1">
            @if($product->hasDiscount())
                <span class="price-sale">@money($product->finalPrice())</span>
                <span class="price-old ms-2">@money($product->price())</span>
            @else
                <span>@money($product->price())</span>
            @endif
        </div>
    </div>
</div>
