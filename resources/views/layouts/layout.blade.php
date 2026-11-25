<!DOCTYPE html>
<html lang="sr-Latn">

<!-- Head -->
@include('fixed.head')

<body class="shop d-flex flex-column min-vh-100">
<!-- Navigation -->
@include('fixed.navigation')

<!-- Announcements (drops, restocks, promos) -->
@include('partials.announcements')

<main class="flex-grow-1">
    <!-- Flash messages (toast in the bottom right corner) -->
    @include('fixed.flash')

    <!-- Page Content -->
    @yield('pageContent')
</main>

<!-- Footer -->
@include('fixed.footer')

<!-- Cart drawer (side panel with the cart, opened by public/js/cart-drawer.js) -->
@include('partials.cart-drawer')

<!-- Bootstrap JavaScript -->
@include('fixed.scripts')

<!-- AJAX "Add to cart" + cart drawer (after validation.js, so invalid forms are stopped first) -->
<script src="{{ asset('js/cart-drawer.js') }}"></script>

</body>

</html>
