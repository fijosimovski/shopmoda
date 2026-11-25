{{-- $footerInstagram, $footerPhone and $footerEmail come from AppServiceProvider (View::composer) --}}
<footer class="site-footer mt-5">
    <div class="container py-5">
        <div class="row g-5 py-lg-3">
            {{-- Brand --}}
            <div class="col-lg-4">
                <a href="{{ route('home') }}" class="shop-logo shop-logo-light">SHOPMODA<span>.</span></a>
                <p class="footer-tagline mt-3 mb-3">Patike za svaki korak — dostavljene na vaša vrata.</p>
                <div class="d-flex flex-wrap gap-3 small footer-muted">
                    <span>Plaćanje pouzećem</span>
                    <span>Dostava za 1–3 radna dana</span>
                </div>
            </div>

            {{-- Shop --}}
            <div class="col-6 col-md-4 col-lg-2">
                <h2 class="footer-title">Prodavnica</h2>
                <ul class="list-unstyled mb-0">
                    <li><a href="{{ route('products.index') }}" class="footer-link">Sve patike</a></li>
                    <li><a href="{{ route('products.index', ['gender' => 'men']) }}" class="footer-link">Muškarci</a></li>
                    <li><a href="{{ route('products.index', ['gender' => 'women']) }}" class="footer-link">Žene</a></li>
                    <li><a href="{{ route('products.index', ['sale' => 1]) }}" class="footer-link">Akcija</a></li>
                </ul>
            </div>

            {{-- Help --}}
            <div class="col-6 col-md-4 col-lg-3">
                <h2 class="footer-title">Pomoć</h2>
                <ul class="list-unstyled mb-0">
                    <li><a href="{{ route('how-to-order') }}#ordering" class="footer-link">Kako naručiti</a></li>
                    <li><a href="{{ route('how-to-order') }}#returns" class="footer-link">Povraćaj i zamena</a></li>
                    <li><a href="{{ route('how-to-order') }}#discounts" class="footer-link">Akcije i kuponi</a></li>
                    <li><a href="{{ route('contact') }}" class="footer-link">Kontakt podrška</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="col-md-4 col-lg-3">
                <h2 class="footer-title">Kontakt</h2>
                <ul class="list-unstyled mb-3">
                    @if($footerPhone)
                        <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $footerPhone) }}" class="footer-link">{{ $footerPhone }}</a></li>
                    @endif
                    @if($footerEmail)
                        <li><a href="mailto:{{ $footerEmail }}" class="footer-link">{{ $footerEmail }}</a></li>
                    @endif
                    <li class="small footer-muted pt-1">Kontakt forma za registrovane kupce</li>
                </ul>
                @if($footerInstagram)
                    <a href="{{ $footerInstagram }}" target="_blank" rel="noopener noreferrer" class="footer-link footer-link-underline">
                        <i class="bi bi-instagram me-1"></i>Pratite nas na Instagramu
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-1 py-3 small footer-muted">
            <span>&copy; {{ date('Y') }} ShopModa. Sva prava zadržana.</span>
            <span>Cene su u dinarima, sa uračunatim PDV-om.</span>
        </div>
    </div>
</footer>
