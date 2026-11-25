@extends('layouts.layout')

@section('pageTitle') Korpa @endsection
@section('robots') noindex, follow @endsection

@section('pageContent')
    <div class="container pt-5 pb-4">
        <h1 class="page-title mb-4 mb-lg-5">Vaša <em>korpa</em></h1>

        {{-- public/js/cart-page.js redraws this box (partials/cart-page.blade.php) after every change --}}
        <div class="cart-page" data-cart-page aria-busy="false">
            @include('partials.cart-page')
        </div>
        <div class="visually-hidden" aria-live="polite" data-cart-page-status></div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/cart-page.js') }}"></script>
@endpush
