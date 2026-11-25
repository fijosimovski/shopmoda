@extends('layouts.layout')

@section('pageTitle') Plaćanje i dostava @endsection

@push('scripts')
    <script src="{{ asset('js/checkout-coupon.js') }}"></script>
    <script src="{{ asset('js/checkout-submit.js') }}"></script>
@endpush

@section('pageContent')
    <div class="container pt-5 pb-4">
        <h1 class="page-title mb-4">Plaćanje i <em>dostava</em></h1>

        @guest
            <p class="panel-line py-3 px-4 small text-body-secondary mb-4">
                Naručujete kao gost. <a href="{{ route('login') }}" class="text-body">Prijavite se</a> ako želite da kasnije vidite ovu porudžbinu na svom nalogu.
            </p>
        @endguest

        <div class="row g-5">
            <div class="col-lg-7">
                @include('partials.errors')

                <form method="POST" action="{{ route('checkout.store') }}" data-validate data-checkout-form data-no-spinner novalidate>
                    @csrf
                    @include('partials.honeypot')

                    <fieldset class="rule-ink-top pt-4">
                        <legend class="label-caps text-body fw-semibold mb-4">Podaci za dostavu</legend>
                        <p class="panel-line d-flex gap-2 py-3 px-4 small text-body-secondary mb-4">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <span>Na email adresu koju unesete poslaćemo <strong class="text-body fw-semibold">kod za potvrdu porudžbine</strong>. Proverite da je adresa tačna i da imate pristup tom inboxu, jer porudžbinu pripremamo tek kada je potvrdite kodom.</span>
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Ime i prezime</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user?->name) }}" data-rules="required|min:3|max:100|name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user?->email) }}" data-rules="required|email|max:100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefon</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" placeholder="06x xxx xxxx" data-rules="required|min:6|max:20|phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="city">Grad</label>
                                <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" data-rules="required|min:2|max:80|city">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address">Adresa</label>
                                <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" data-rules="required|min:5|max:150|address">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="note">Napomena (opciono)</label>
                                <textarea class="form-control" id="note" name="note" rows="3" data-rules="max:500">{{ old('note') }}</textarea>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Only shown while at least one coupon can be used. --}}
                    @if($showCoupon)
                        <fieldset class="rule-ink-top pt-4 mt-5">
                            <legend class="label-caps text-body fw-semibold mb-4">Kupon</legend>
                            <div class="d-flex gap-2 align-items-start" data-coupon data-url="{{ route('checkout.coupon') }}" data-base-total="@money($totals->total() + $totals->discounts->whereNotNull('coupon_id')->sum('amount'))">
                                <div class="flex-grow-1">
                                    <label class="visually-hidden" for="coupon_code">Kupon kod</label>
                                    <input type="text" class="form-control text-uppercase @error('coupon_code') is-invalid @enderror @if($couponError) is-invalid @endif" id="coupon_code" name="coupon_code"
                                           value="{{ old('coupon_code') }}" placeholder="Unesite kupon kod" autocomplete="off" data-label="Kupon kod" data-rules="max:50|coupon">
                                    <div class="invalid-feedback d-block" data-coupon-error>{{ $errors->first('coupon_code') ?: $couponError }}</div>
                                    <div class="small text-success" data-coupon-message></div>
                                </div>
                                <button type="button" class="btn btn-outline-ink btn-match-field" data-coupon-apply>Primeni</button>
                            </div>
                        </fieldset>
                    @endif

                    <p class="small text-body-secondary mt-5 mb-2">Plaćanje: pouzećem, prilikom preuzimanja. Dostavu plaća kupac.</p>
                    <p class="small text-body-secondary d-flex gap-2 mb-3">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <span>Nakon slanja proverite inbox email adrese iz porudžbine (i spam folder): tamo vas čeka kod kojim potvrđujete porudžbinu.</span>
                    </p>
                    <button class="btn btn-ink w-100" data-checkout-submit>
                        <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true" data-checkout-spinner></span>
                        <span data-checkout-label>Poruči &middot; <span data-order-total>@money($totals->total())</span></span>
                    </button>
                </form>

                {{-- Shown by checkout-submit.js while the order is being placed --}}
                <div class="checkout-overlay position-fixed top-0 start-0 w-100 h-100 d-none flex-column align-items-center justify-content-center bg-white bg-opacity-75" role="status" aria-live="polite" data-checkout-overlay>
                    <div class="spinner-border checkout-overlay-spinner mb-3" aria-hidden="true"></div>
                    <div class="serif-lg">Šaljemo vašu porudžbinu...</div>
                    <div class="small text-body-secondary">Molimo vas da ne zatvarate i ne osvežavate stranicu.</div>
                </div>
            </div>

            <div class="col-lg-5">
                <aside class="panel-soft" aria-label="Vaša porudžbina">
                    <div class="d-flex justify-content-between align-items-baseline mb-3">
                        <h2 class="label-caps text-body fw-semibold mb-0">Vaša porudžbina</h2>
                        <a href="{{ route('cart.index') }}" class="link-caps">Izmeni korpu</a>
                    </div>
                    @foreach($totals->items as $item)
                        <div class="order-line">
                            <img src="{{ $item['product']->mainImage?->url() ?? asset('images/placeholder.png') }}" alt="{{ $item['product']->name }}" class="order-line-image" width="48" height="60">
                            <span class="flex-grow-1">{{ $item['quantity'] }} &times; {{ $item['product']->name }} <span class="text-body-secondary">(veličina {{ $item['size'] }})</span></span>
                            <span class="text-nowrap">@money($item['subtotal'])</span>
                        </div>
                    @endforeach
                    <div class="mt-3">
                        @include('partials.totals', ['subtotal' => $totals->subtotal(), 'discounts' => $totals->discounts, 'total' => $totals->total(), 'showSubtotal' => true])
                    </div>
                    <p class="d-flex gap-2 small text-body-secondary mt-3 mb-0">
                        <i class="bi bi-truck" aria-hidden="true"></i>
                        <span>Cena ne uključuje dostavu. <strong class="text-body fw-semibold">Troškove dostave plaća kupac</strong>, kuriru prilikom preuzimanja, po cenovniku kurirske službe.</span>
                    </p>
                </aside>
            </div>
        </div>
    </div>
@endsection
