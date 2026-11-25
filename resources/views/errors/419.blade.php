@extends('errors.shop')

@section('code', '419')
@section('errorTitle', 'Stranica je istekla')
@section('errorText', 'Iz bezbednosnih razloga stranica je istekla jer je dugo bila otvorena. Vratite se, osvežite stranicu i pokušajte ponovo. Sadržaj korpe je sačuvan.')

@section('errorActions')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="btn btn-ink">Vrati se nazad</a>
    <a href="{{ route('cart.index') }}" class="btn btn-outline-dark rounded-0">Korpa</a>
@endsection
