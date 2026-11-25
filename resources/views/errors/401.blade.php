@extends('errors.shop')

@section('code', '401')
@section('errorTitle', 'Potrebna je prijava')
@section('errorText', 'Za ovu stranicu morate biti prijavljeni.')

@section('errorActions')
    <a href="{{ route('login') }}" class="btn btn-ink">Prijava</a>
    <a href="{{ route('home') }}" class="btn btn-outline-dark rounded-0">Početna strana</a>
@endsection
