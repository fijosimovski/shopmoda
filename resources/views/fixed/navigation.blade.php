{{-- $navCategories and $cartCount come from AppServiceProvider (View::composer) --}}
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shop-nav">
    <div class="container">
        <a class="navbar-brand shop-logo" href="{{ route('home') }}">SHOPMODA<span>.</span></a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Otvori meni">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto gap-lg-3 ms-lg-4">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.*') && !request('gender') && !request('sale') ? 'active' : '' }}" href="{{ route('products.index') }}">Sve patike</a></li>
                <li class="nav-item"><a class="nav-link {{ request('gender') === 'men' ? 'active' : '' }}" href="{{ route('products.index', ['gender' => 'men']) }}">Muškarci</a></li>
                <li class="nav-item"><a class="nav-link {{ request('gender') === 'women' ? 'active' : '' }}" href="{{ route('products.index', ['gender' => 'women']) }}">Žene</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Kategorije</a>
                    <ul class="dropdown-menu">
                        @foreach($navCategories as $category)
                            <li><a class="dropdown-item" href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link nav-link-sale {{ request('sale') ? 'active' : '' }}" href="{{ route('products.index', ['sale' => 1]) }}">Akcija</a></li>
            </ul>

            <form class="d-flex align-items-center me-lg-4 my-3 my-lg-0 shop-search" action="{{ route('products.index') }}" method="GET" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" placeholder="Pretraži patike..." aria-label="Pretraži patike" value="{{ request('q') }}">
            </form>

            <ul class="navbar-nav align-items-lg-center gap-lg-3">
                <li class="nav-item">
                    {{-- data-cart-drawer-toggle: public/js/cart-drawer.js opens the cart drawer instead (not on the cart and checkout pages,
                         they show the cart already). data-cart-icon / data-cart-count: the "Add to cart" animation flies here and updates the number. --}}
                    <a class="nav-link d-inline-flex align-items-center gap-1" href="{{ route('cart.index') }}"
                       @unless(request()->routeIs('cart.*', 'checkout.*')) data-cart-drawer-toggle aria-controls="cartDrawer" @endunless>
                        <i class="bi bi-bag fs-5" data-cart-icon aria-hidden="true"></i>
                        <span class="visually-hidden">Korpa</span>
                        <span class="cart-count {{ $cartCount > 0 ? '' : 'd-none' }}" data-cart-count>({{ $cartCount }})</span>
                    </a>
                </li>

                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ auth()->user()->name }}</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Admin panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('account.orders') }}"><i class="bi bi-bag-check me-2"></i>Moje porudžbine</a></li>
                            <li><a class="dropdown-item" href="{{ route('account.profile') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="{{ route('contact') }}"><i class="bi bi-headset me-2"></i>Podrška</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Odjava</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Prijava</a></li>
                    <li class="nav-item"><a class="btn btn-ink btn-sm mt-2 mt-lg-0" href="{{ route('register') }}">Registracija</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
