{{--
    Cart drawer: Bootstrap offcanvas sliding in from the right (included once in layouts/layout.blade.php).
    public/js/cart-drawer.js opens it after "Add to cart" and when the bag icon in the navigation is clicked,
    and fills [data-cart-drawer-body] with partials/cart-drawer-items.blade.php (from GET /cart/drawer).
--}}
<div class="offcanvas offcanvas-end cart-drawer" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerTitle"
     data-drawer-url="{{ route('cart.drawer') }}">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title label-caps text-body fw-semibold" id="cartDrawerTitle">Vaša korpa</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Zatvori"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column" data-cart-drawer-body>
        <div class="d-flex justify-content-center py-5">
            <div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Učitavanje...</span></div>
        </div>
    </div>

    {{-- "... added to the cart." is read out by screen readers --}}
    <div class="visually-hidden" aria-live="polite" data-cart-status></div>
</div>
