@extends('layouts.layout')

@section('pageTitle') Moje porudžbine @endsection

@section('pageContent')
    <div class="container pt-5 pb-4">
        <h1 class="page-title">Moje <em>porudžbine</em></h1>
        @include('partials.account-nav')

        @if($orders->isEmpty())
            <div class="empty-state">
                <p class="serif-lg mb-3">Još uvek niste ništa naručili.</p>
                <a href="{{ route('products.index') }}" class="btn btn-ink">Započnite kupovinu</a>
            </div>
        @else
            {{-- position-relative: makes the absolute .visually-hidden "Detalji" label scroll inside the table instead of widening the page on phones --}}
            <div class="table-responsive position-relative mt-4">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr><th>Porudžbina</th><th>Datum</th><th>Stavki</th><th class="text-end">Ukupno</th><th class="ps-4">Status</th><th><span class="visually-hidden">Detalji</span></th></tr>
                    </thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td class="fw-medium">{{ $order->number }}</td>
                            <td class="text-body-secondary">{{ $order->created_at->format('d.m.Y.') }}</td>
                            <td class="text-body-secondary">{{ $order->items_count }}</td>
                            <td class="text-end text-nowrap">@money($order->total)</td>
                            <td class="ps-4">@include('partials.status-badge', ['status' => $order->status])</td>
                            <td class="text-end"><a href="{{ route('account.orders.show', $order) }}" class="link-caps">Detalji</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-5">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
