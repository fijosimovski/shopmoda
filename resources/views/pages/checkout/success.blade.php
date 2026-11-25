@extends('layouts.layout')

@section('pageTitle') Hvala na porudžbini @endsection

@section('pageContent')
    <div class="container py-5 checkout-success text-center">
        <div class="label-caps mt-lg-4">Porudžbina {{ $order->number }}</div>
        <h1 class="page-title mt-3">Hvala na <em>porudžbini!</em></h1>
        <p class="text-body-secondary lh-lg mt-4">Porudžbina <strong class="text-body fw-semibold">{{ $order->number }}</strong> je primljena. Dostavljamo je na adresu
            {{ $order->address }}, {{ $order->city }}. Prilikom preuzimanja plaćate <strong class="text-body fw-semibold">@money($order->total)</strong> i troškove dostave, po cenovniku kurirske službe.</p>

        @if($order->isUnconfirmed())
            <div class="panel-sale d-flex gap-3 text-start mt-5">
                <i class="bi bi-envelope text-sale fs-4 lh-1"></i>
                <div>
                    <div class="label-caps text-sale fw-semibold">Još jedan korak</div>
                    <p class="mb-0 mt-2">Poslali smo kod za potvrdu na <strong class="fw-semibold">{{ $order->email }}</strong>.
                        Otvorite link iz tog emaila i unesite kod da potvrdite porudžbinu. Pripremu počinjemo tek nakon potvrde.</p>
                </div>
            </div>
        @endif

        <div class="rule-ink-top text-start mt-5">
            @foreach($order->items as $item)
                <div class="totals-line border-bottom py-3">
                    <span>{{ $item->quantity }} &times; {{ $item->product->name }} <span class="text-body-secondary">(veličina {{ $item->size }})</span></span>
                    <span class="text-nowrap ms-3">@money($item->subtotal())</span>
                </div>
            @endforeach
            <div class="mt-2">
                @include('partials.totals', ['subtotal' => $order->subtotal, 'discounts' => $order->discounts, 'total' => $order->total, 'totalLabel' => 'Ukupno (plaćanje pouzećem)'])
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 mt-5">
            <a href="{{ route('products.index') }}" class="btn btn-ink">Nastavi kupovinu</a>
            @auth
                <a href="{{ route('account.orders') }}" class="link-caps">Moje porudžbine</a>
            @endauth
        </div>
    </div>
@endsection
