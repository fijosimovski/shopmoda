@extends('layouts.layout')

@section('pageTitle') Zaboravljena lozinka @endsection

@section('pageContent')
    <div class="container py-5 auth-box">
        <h1 class="page-title page-title-sm mt-lg-4 mb-3">Zaboravili ste <em>lozinku?</em></h1>
        <p class="text-body-secondary mb-4">Unesite email svog naloga. Poslaćemo vam šestocifreni kod za postavljanje nove lozinke.</p>

        @include('partials.errors')

        <form method="POST" action="{{ route('password.email') }}" class="rule-ink-top pt-4" data-validate novalidate>
            @csrf
            @include('partials.honeypot')
            <div class="mb-4">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" data-rules="required|email|max:100" autocomplete="email" autofocus>
            </div>
            <button class="btn btn-ink w-100" data-loading-text="Šaljemo kod...">Pošalji mi kod</button>
        </form>

        <p class="text-center text-body-secondary mt-4">
            Već imate kod? <a href="{{ route('password.reset') }}" class="text-body">Unesite ga ovde</a><br>
            <a href="{{ route('login') }}" class="link-caps d-inline-block mt-3">Nazad na prijavu</a>
        </p>
    </div>
@endsection
