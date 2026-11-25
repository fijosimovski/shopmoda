@extends('layouts.layout')

@section('pageTitle') Potvrda porudžbine {{ $order->number }} @endsection

@section('pageContent')
    <div class="container py-5 confirm-order text-center">
        @if($order->isUnconfirmed())
            <h1 class="page-title page-title-sm mt-lg-4">Potvrdite <em>porudžbinu</em></h1>
            <div class="label-caps mt-3">{{ $order->number }}</div>
            <p class="text-body-secondary mt-4 mb-5">Unesite šestocifreni kod koji smo poslali na <strong class="text-body fw-semibold">{{ $order->email }}</strong>.</p>

            <div class="text-start">
                @include('partials.errors')
            </div>

            {{-- fullUrl() keeps the signature from the email link, the POST route checks it too. --}}
            <form method="POST" action="{{ request()->fullUrl() }}" class="panel-line text-start" data-validate novalidate>
                @csrf
                <div class="mb-3">
                    <label class="label-caps text-body fw-semibold d-block mb-3" for="code">Kod za potvrdu</label>
                    <input type="text" class="form-control text-center confirm-order-code" id="code" name="code"
                           inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" autofocus
                           value="{{ old('code') }}" data-rules="required|integer|min:100000|max:999999" data-msg-integer="Kod ima 6 cifara, nalazi se u emailu." data-msg-min="Kod ima 6 cifara, nalazi se u emailu." data-msg-max="Kod ima 6 cifara, nalazi se u emailu.">
                </div>
                <button class="btn btn-ink w-100" data-loading-text="Potvrđujemo...">Potvrdi porudžbinu</button>
            </form>
        @else
            <div class="label-caps mt-lg-4">{{ $order->number }}</div>
            <h1 class="page-title page-title-sm mt-3">Porudžbina je <em>potvrđena</em></h1>
            <p class="text-body-secondary mt-4 mb-5">
                @if($order->confirmed_at)
                    Potvrđena {{ $order->confirmed_at->format('d.m.Y. \u H:i') }}.
                @endif
                Detalje porudžbine poslali smo na {{ $order->email }}. Prilikom preuzimanja plaćate <strong class="text-body fw-semibold">@money($order->total)</strong>.
            </p>
            <a href="{{ route('products.index') }}" class="btn btn-ink">Nastavi kupovinu</a>
        @endif
    </div>
@endsection
