@extends('layouts.layout')

@section('pageTitle') Prijava @endsection

@section('pageContent')
    <div class="container py-5">
        <div class="row g-0 auth-split">
            <div class="col-lg-6 my-3 auth-split-side d-lg-flex">
                <p class="display-serif mb-0">Drago nam je što ste <em>ponovo tu.</em></p>
            </div>
            <div class="col-lg-6 d-flex justify-content-center py-lg-5">
                <div class="auth-box w-100 px-lg-4 py-lg-4">
                    <h1 class="page-title mb-4">Prijava</h1>

                    @include('partials.errors')

                    <form method="POST" action="{{ route('login.auth') }}" data-validate novalidate>
                        @csrf
                        @include('partials.honeypot')
                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="username" data-rules="required|email|max:100" autofocus>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Lozinka</label>
                                <a href="{{ route('password.request') }}" class="small text-body-secondary">Zaboravili ste lozinku?</a>
                            </div>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" data-rules="required|max:100">
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">Zapamti me</label>
                        </div>
                        <button class="btn btn-ink w-100" data-loading-text="Prijavljujemo...">Prijavi se</button>
                    </form>

                    <p class="text-center text-body-secondary mt-4 mb-0">Nemate nalog? <a href="{{ route('register') }}" class="text-body">Registrujte se</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
