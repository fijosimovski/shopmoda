{{-- Why buy here: delivery, returns, support. Strip on the homepage (under the hero). Phones: icon + short title only. --}}
<section class="row row-cols-3 g-0 perks mb-5" aria-label="Kupovina u ShopModa prodavnici">
    @foreach([
        ['truck', 'Brza dostava', '1–3 dana', 'Na vašoj adresi za 1–3 radna dana. Dostavu plaća kupac.', null],
        ['arrow-left-right', 'Povraćaj i zamena', 'Lak povraćaj', 'Pogrešna veličina ili vam se ne dopada? Vratite ili zamenite.', route('how-to-order') . '#returns'],
        ['headset', 'Korisnička podrška', 'Podrška', 'Registrovani kupci nam mogu pisati u bilo kom trenutku.', route('contact')],
    ] as [$icon, $title, $shortTitle, $text, $link])
        <div class="col">
            <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start text-center text-md-start gap-2 gap-md-3 h-100 py-3 py-md-4 pe-md-4 position-relative">
                <i class="bi bi-{{ $icon }} perk-icon flex-shrink-0"></i>
                <div>
                    <div class="perk-title d-none d-md-block">{{ $title }}</div>
                    <div class="perk-title-short d-md-none">{{ $shortTitle }}</div>
                    <div class="perk-text d-none d-md-block">{{ $text }}</div>
                </div>
                @if($link)
                    <a href="{{ $link }}" class="stretched-link" aria-label="{{ $title }}"></a>
                @endif
            </div>
        </div>
    @endforeach
</section>
