@extends('layouts.layout')

@section('pageTitle') Porudžbina {{ $order->number }} @endsection

@section('pageContent')
    <div class="container pt-5 pb-4 account-narrow">
        <a href="{{ route('account.orders') }}" class="link-caps text-body-secondary">&larr; Moje porudžbine</a>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-3 pb-4 rule-ink">
            <div>
                <h1 class="page-title page-title-sm">{{ $order->number }}</h1>
                <div class="text-body-secondary mt-2">{{ $order->created_at->format('d.m.Y. H:i') }}</div>
            </div>
            @include('partials.status-badge', ['status' => $order->status])
        </div>

        <div>
            @foreach($order->items as $item)
                <div class="cart-row">
                    <span class="cart-row-image cart-row-image-sm flex-shrink-0">
                        <img src="{{ $item->product->mainImage?->url() ?? asset('images/placeholder.png') }}" alt="{{ $item->product->name }}" width="80" height="100" loading="lazy">
                    </span>
                    <div class="flex-grow-1">
                        <div class="serif-lg">{{ $item->product->name }}</div>
                        <div class="small text-body-secondary mt-1">Veličina {{ $item->size }} &middot; {{ $item->quantity }} &times; @money($item->unit_price)</div>
                    </div>
                    <div class="text-nowrap">@money($item->subtotal())</div>
                </div>
            @endforeach
        </div>

        <div class="row g-4 mt-2">
            <div class="col-md-6">
                <div class="panel-soft h-100">
                    <div class="label-caps text-body fw-semibold mb-2">Adresa za dostavu</div>
                    <p class="mb-0 lh-lg">{{ $order->name }}, {{ $order->address }}, {{ $order->city }} &middot; {{ $order->phone }}</p>
                    @if($order->note)
                        <div class="label-caps text-body fw-semibold mt-3 mb-1">Napomena</div>
                        <p class="mb-0 text-body-secondary">{{ $order->note }}</p>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                @include('partials.totals', ['subtotal' => $order->subtotal, 'discounts' => $order->discounts, 'total' => $order->total, 'totalLabel' => 'Ukupno (plaćanje pouzećem)'])
            </div>
        </div>
    </div>
@endsection
