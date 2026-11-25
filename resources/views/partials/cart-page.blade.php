{{--
    Contents of the cart page (pages/cart/index.blade.php): the rows and the summary.
    Also rendered by CartController::cartPageResponse(), so public/js/cart-page.js can redraw it after
    a quantity change or "Remove" without reloading (promotions like "2nd pair -20%" can change every total).
    $items, $totals, $couponsAvailable as in CartController::index().
--}}
@if($items->isEmpty())
    <div class="empty-state rule-ink-top">
        <p class="serif-lg mb-3">Vaša korpa je prazna.</p>
        <a href="{{ route('products.index') }}" class="btn btn-ink">Pogledajte patike</a>
    </div>
@else
    <div class="row g-5">
        <div class="col-lg-8">
            <div class="rule-ink-top">
                @foreach($items as $item)
                    <div class="cart-row" data-cart-row="{{ $item['key'] }}">
                        <a href="{{ route('products.show', $item['product']) }}" class="cart-row-image flex-shrink-0" tabindex="-1" aria-hidden="true">
                            <img src="{{ $item['product']->mainImage?->url() ?? asset('images/placeholder.png') }}" alt="{{ $item['product']->brand->name }} {{ $item['product']->name }}" width="120" height="150" loading="lazy">
                        </a>
                        <div class="flex-grow-1 min-w-0">
                            <div class="product-card-brand">{{ $item['product']->brand->name }}</div>
                            <a href="{{ route('products.show', $item['product']) }}" class="serif-lg d-block text-decoration-none text-body">{{ $item['product']->name }}</a>
                            <div class="small text-body-secondary mt-1">
                                Veličina {{ $item['size'] }} &middot; @money($item['unit_price']) po paru
                                @if($item['product']->hasDiscount())
                                    <span class="price-old ms-1">@money($item['product']->price())</span>
                                @endif
                            </div>
                            @foreach($item['product']->cartPromotions() as $promotion)
                                <a href="{{ route('promotions.show', $promotion) }}" class="badge-promo text-decoration-none mt-2">{{ $promotion->badge() }}</a>
                            @endforeach
                            @if($item['quantity'] > $item['stock'])
                                <div class="small text-sale mt-2">Na stanju je još samo {{ $item['stock'] }}, smanjite količinu.</div>
                            @endif
                            {{-- Server error for this row ("only 2 left"), filled by public/js/cart-page.js --}}
                            <div class="small text-sale mt-2 d-none" data-cart-row-error role="alert"></div>
                        </div>

                        <div class="d-flex flex-column align-items-end justify-content-between gap-3">
                            <span class="fw-medium text-nowrap" data-cart-row-subtotal>@money($item['subtotal'])</span>

                            <form method="POST" action="{{ route('cart.update', $item['key']) }}" class="d-flex gap-1 align-items-start" data-cart-quantity>
                                @csrf
                                @method('PATCH')
                                <div class="input-group cart-stepper">
                                    <button type="button" class="btn btn-square" data-cart-step="-1" aria-label="Smanji količinu" @disabled($item['quantity'] <= 1)><i class="bi bi-dash" aria-hidden="true"></i></button>
                                    <label class="visually-hidden" for="qty{{ $loop->index }}">Količina</label>
                                    <input type="number" id="qty{{ $loop->index }}" name="quantity" value="{{ $item['quantity'] }}" min="1" max="{{ min(10, max(1, $item['stock'])) }}" inputmode="numeric"
                                           class="form-control text-center quantity-input" data-label="Količina" data-rules="required|integer|min:1|max:10">
                                    <button type="button" class="btn btn-square" data-cart-step="1" aria-label="Povećaj količinu" @disabled($item['quantity'] >= min(10, $item['stock']))><i class="bi bi-plus" aria-hidden="true"></i></button>
                                </div>
                                {{-- Without JavaScript this button sends the form; cart-page.js hides it and saves on every change --}}
                                <button class="btn btn-square" title="Ažuriraj količinu" aria-label="Ažuriraj količinu" data-cart-update-button><i class="bi bi-arrow-repeat"></i></button>
                            </form>

                            <form method="POST" action="{{ route('cart.destroy', $item['key']) }}" data-cart-page-remove>
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link link-caps p-0 text-body-secondary" aria-label="Ukloni {{ $item['product']->name }}">Ukloni</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('products.index') }}" class="link-caps d-inline-block mt-4">Nastavi kupovinu</a>
        </div>

        <div class="col-lg-4">
            <aside class="panel-soft" aria-label="Pregled porudžbine">
                <h2 class="label-caps text-body fw-semibold mb-3">Pregled</h2>
                @include('partials.totals', ['subtotal' => $totals->subtotal(), 'discounts' => $totals->discounts, 'total' => $totals->total()])
                <a href="{{ route('checkout.create') }}" class="btn btn-ink w-100 mt-4">Idi na plaćanje</a>
                <p class="small text-body-secondary mt-3 mb-0">Plaćate pouzećem, prilikom preuzimanja. Cena ne uključuje dostavu, troškove dostave plaća kupac. Nalog nije potreban.@if($couponsAvailable) Imate kupon? Unesite ga na stranici za plaćanje.@endif</p>
            </aside>
        </div>
    </div>
@endif
