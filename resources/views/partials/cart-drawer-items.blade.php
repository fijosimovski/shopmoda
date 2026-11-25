{{--
    Inside of the cart drawer (partials/cart-drawer.blade.php), rendered by CartController::drawerResponse().
    $items and $totals as on the cart page; $addedKey = the row that was just added (highlighted), or null.
--}}
@if($items->isEmpty())
    <div class="text-center py-5 my-auto">
        <p class="serif-lg mb-3">Vaša korpa je prazna.</p>
        <a href="{{ route('products.index') }}" class="btn btn-ink">Pogledajte patike</a>
    </div>
@else
    <div class="flex-grow-1">
        @foreach($items as $item)
            <div class="cart-drawer-item {{ $item['key'] === $addedKey ? 'cart-drawer-item-new' : '' }}">
                <a href="{{ route('products.show', $item['product']) }}" class="cart-row-image cart-row-image-xs flex-shrink-0" tabindex="-1" aria-hidden="true">
                    <img src="{{ $item['product']->mainImage?->url() ?? asset('images/placeholder.png') }}" alt="{{ $item['product']->name }}" width="64" height="80" loading="lazy">
                </a>
                <div class="flex-grow-1 min-w-0">
                    <div class="product-card-brand">{{ $item['product']->brand->name }}</div>
                    <a href="{{ route('products.show', $item['product']) }}" class="d-block text-decoration-none text-body fw-medium text-truncate">{{ $item['product']->name }}</a>
                    <div class="small text-body-secondary">
                        Veličina {{ $item['size'] }} &middot; {{ $item['quantity'] }} &times; @money($item['unit_price'])
                    </div>
                    @if($item['key'] === $addedKey)
                        <div class="small text-success mt-1"><i class="bi bi-check2 me-1"></i>Upravo dodato</div>
                    @endif
                </div>
                <div class="d-flex flex-column align-items-end justify-content-between">
                    <span class="fw-medium text-nowrap small">@money($item['subtotal'])</span>
                    <form method="POST" action="{{ route('cart.destroy', $item['key']) }}" data-cart-remove>
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link link-caps p-0 text-body-secondary" aria-label="Ukloni {{ $item['product']->name }}">Ukloni</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="cart-drawer-footer">
        @include('partials.totals', ['subtotal' => $totals->subtotal(), 'discounts' => $totals->discounts, 'total' => $totals->total()])
        <a href="{{ route('checkout.create') }}" class="btn btn-ink w-100 mt-3">Idi na plaćanje</a>
        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-link link-caps p-0" data-bs-dismiss="offcanvas">Nastavi kupovinu</button>
            <a href="{{ route('cart.index') }}" class="link-caps">Pogledaj korpu</a>
        </div>
    </div>
@endif
