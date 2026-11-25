@extends('layouts.layout')

@section('pageTitle') Kako naručiti, povraćaj i zamena @endsection
@section('metaDescription', 'Kako se naručuje u ShopModa prodavnici: korak po korak, potvrda porudžbine kodom, plaćanje pouzećem, povraćaj i zamena u roku od 14 dana, akcije i kuponi.')

@section('pageContent')
    {{-- Header --}}
    <section class="container pt-5">
        <h1 class="display-serif display-serif-xl">Kako se <em>naručuje</em></h1>
        <p class="lead-muted mt-3 mb-0">Sve o naručivanju, povraćaju i zameni, akcijama i kuponima na jednom mestu.</p>
        <nav class="help-nav mt-5" aria-label="Na ovoj stranici">
            <a href="#ordering">Naručivanje</a>
            <a href="#returns">Povraćaj i zamena</a>
            <a href="#discounts">Akcije</a>
            <a href="#coupons">Kuponi</a>
            <a href="#questions">Pitanja</a>
        </nav>
    </section>

    <div class="container">

        {{-- ---------- Ordering ---------- --}}
        <section id="ordering" class="help-section home-section">
            <h2 class="section-title mb-4">Naručivanje, <em>korak po korak</em></h2>
            <ol class="list-unstyled row row-cols-1 row-cols-md-2 row-cols-lg-4 g-0 border-top mb-0">
                @foreach([
                    ['Izaberite par', 'Na stranici proizvoda izaberite veličinu i dodajte patike u korpu. Možete naručiti kao gost ili preko svog naloga.'],
                    ['Popunite podatke', 'Ime i prezime, email, telefon i adresa za dostavu. Ako imate kupon, unesite ga. Plaćanje je pouzećem.'],
                    ['Potvrdite kodom iz emaila', 'Na email vam šaljemo šestocifreni kod. Unesite ga na stranici za potvrdu, kako bismo znali da porudžbina zaista dolazi od vas.'],
                    ['Šaljemo paket', 'Nakon potvrde pripremamo porudžbinu, a kurir je donosi na vašu adresu za 1–3 radna dana. Troškove dostave plaćate kuriru prilikom preuzimanja.'],
                ] as $i => [$title, $text])
                    <li class="col help-step">
                        <span class="help-step-number">{{ $i + 1 }}</span>
                        <h3 class="serif-lg mt-3 mb-2">{{ $title }}</h3>
                        <p class="text-body-secondary mb-0">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="panel-soft d-md-flex gap-5 mt-4">
                <h3 class="label-caps text-body fw-semibold help-aside-title mb-3 mb-md-0">Još niste potvrdili?</h3>
                <p class="mb-0 lh-lg">
                    Link za potvrdu važi {{ \App\Support\Sr::plural(\App\Services\OrderConfirmation::LINK_VALID_HOURS, 'sat', 'sata', 'sati') }}.
                    Ako porudžbina ni posle {{ \App\Support\Sr::plural(\App\Services\OrderConfirmation::REMINDER_AFTER_HOURS, 'sata', 'sata', 'sati') }} nije potvrđena, šaljemo podsetnik sa novim kodom.
                    Porudžbine koje nikada nisu potvrđene automatski se otkazuju.
                    Prijavljeni kupci mogu da prate svaku porudžbinu u delu <em>Moje porudžbine</em>.
                </p>
            </div>
        </section>

        {{-- ---------- Returns & exchanges ---------- --}}
        <section id="returns" class="help-section home-section">
            <div class="row g-5">
                <div class="col-lg-5">
                    <h2 class="section-title mb-3">Povraćaj i <em>zamena</em></h2>
                    <p class="lead-muted">Pogrešna veličina ili vam se model ne dopada? Patike možete vratiti ili zameniti za drugu veličinu ili model.</p>
                    <div class="panel-line mt-4">
                        <h3 class="label-caps text-body fw-semibold mb-3">Dobro je znati</h3>
                        <ul class="mb-0 ps-3 lh-lg">
                            <li>Povraćaj ili zamenu zatražite u roku od 14 dana od prijema porudžbine.</li>
                            <li>Dobro zapakujte patike, u originalnoj kutiji, kako bi stigle neoštećene.</li>
                            <li>Troškove dostave za povraćaj i zamenu snosi kupac.</li>
                            <li>Nošene ili oštećene patike, kao i patike bez originalne kutije, ne mogu se vratiti.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-7">
                    <ol class="list-unstyled rule-ink-top mb-0">
                        @foreach([
                            ['Javite nam se', 'preko kontakt forme (Podrška) i napišite broj porudžbine i da li želite povraćaj ili zamenu.'],
                            ['Pošaljite nam prethodnu porudžbinu:', 'patike nenošene, u originalnoj kutiji, sa svim etiketama. Zamena počinje tek kada ih primimo.'],
                            ['Dostavu plaćate vi', 'za slanje patika nazad, a kod zamene i za novi par.'],
                            ['Rešavamo za oko 5 radnih dana', 'od dana kada paket stigne: šaljemo novi par, ili vraćamo novac kod povraćaja.'],
                        ] as $i => [$title, $text])
                            <li class="d-flex gap-4 py-4 border-bottom">
                                <span class="help-step-number help-step-number-sm">{{ $i + 1 }}</span>
                                <p class="mb-0 lh-lg text-body-secondary"><strong class="text-body fw-semibold">{{ $title }}</strong> {{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        {{-- ---------- Promotions ---------- --}}
        <section id="discounts" class="help-section home-section">
            <h2 class="section-title mb-3">Akcije <em>i popusti</em></h2>
            <p class="lead-muted mw-100 mb-4">Aktuelne akcije prikazane su na početnoj strani i u traci ispod menija. Postoje tri vrste:</p>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                @foreach([
                    ['-20%', 'Niže cene', 'npr. -20% ili -1.000 RSD na brend ili model. Nova cena je već prikazana svuda, a stara je precrtana.'],
                    ['2. par -30%', 'Svaki n-ti par', 'Stavite dovoljno pari sa akcije u korpu; popust dobijaju jeftiniji parovi. Obračunava se u korpi.'],
                    ['-1.500 RSD preko 15.000', 'Potroši i uštedi', 'Dostignite iznos proizvodima sa akcije i popust se pojavljuje u korpi.'],
                ] as [$tag, $title, $text])
                    <div class="col">
                        <div class="panel-line h-100">
                            <span class="badge-promo">{{ $tag }}</span>
                            <h3 class="serif-lg mt-3 mb-2">{{ $title }}</h3>
                            <p class="text-body-secondary mb-0">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="rule-ink-top d-md-flex gap-5 mt-4 pt-4">
                <h3 class="label-caps text-body fw-semibold help-aside-title mb-3 mb-md-0">Da li se popusti sabiraju?</h3>
                <ul class="mb-0 ps-3 lh-lg">
                    <li>Proizvod dobija najviše <strong>jednu</strong> akciju na cenu. Ako je već snižen, dobijate povoljniju cenu, osim ako akcija ne kaže da se sabira sa sniženom cenom.</li>
                    <li>Akcije u korpi (n-ti par, potroši i uštedi) sabiraju se samo kada su obe označene kao kombinovane; u suprotnom važi veći popust.</li>
                    <li>Popust nikada ne može biti veći od vrednosti proizvoda, a svaki popust je posebno prikazan u korpi, na stranici za plaćanje i u emailu porudžbine.</li>
                </ul>
            </div>
        </section>

        {{-- ---------- Coupons ---------- --}}
        <section id="coupons" class="help-section home-section">
            <div class="row g-5">
                <div class="col-lg-6">
                    <h2 class="section-title mb-3">Kupon <em>kodovi</em></h2>
                    <p class="lead-muted mw-100">Unesite kod na stranici za plaćanje i kliknite <strong>Primeni</strong>: popust se prikazuje odmah. Velika i mala slova nisu bitna.</p>
                    <ul class="ps-3 lh-lg mb-0">
                        <li><strong>Jedan kupon po porudžbini.</strong></li>
                        <li>Kupon se obračunava <strong>posle</strong> akcija u korpi, pa se mogu koristiti zajedno.</li>
                        <li>Kupon je procenat (npr. 10%) ili fiksni iznos (npr. 1.000 RSD), ponekad sa najvećim mogućim popustom.</li>
                        <li>Ako se porudžbina otkaže ili nikada ne potvrdi, korišćenje kupona se ne računa i možete ga ponovo iskoristiti.</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="panel-soft">
                        <h3 class="label-caps text-body fw-semibold mb-3">Kupon može imati uslove</h3>
                        <ul class="ps-3 lh-lg mb-0">
                            <li>Važi samo između datuma početka i kraja.</li>
                            <li>Minimalan iznos porudžbine.</li>
                            <li>Samo za određene brendove, modele ili proizvode.</li>
                            <li>Samo za proizvode po punoj ceni (ne za već snižene).</li>
                            <li>Samo za prvu porudžbinu, ili ograničen broj korišćenja po kupcu.</li>
                            <li>Ograničen ukupan broj korišćenja: kada se potroše, kod prestaje da važi.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- ---------- Questions ---------- --}}
        <section id="questions" class="help-section home-section">
            <div class="rule-ink-top rule-ink py-5 d-md-flex align-items-center justify-content-between gap-4">
                <div>
                    <h2 class="section-title">Imate još <em>pitanja?</em></h2>
                    <p class="text-body-secondary mt-2 mb-md-0">
                        Registrovani kupci nam mogu poslati poruku preko kontakt forme, a mi odgovaramo emailom.
                        @guest Prijavite se ili napravite besplatan nalog da biste je koristili. @endguest
                    </p>
                </div>
                <div class="d-flex align-items-center gap-4 flex-shrink-0">
                    @auth
                        <a href="{{ route('contact') }}" class="btn btn-ink btn-lg">Kontakt podrška</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ink btn-lg">Prijava</a>
                        <a href="{{ route('register') }}" class="link-caps">Registracija</a>
                    @endauth
                </div>
            </div>
        </section>
    </div>
@endsection
