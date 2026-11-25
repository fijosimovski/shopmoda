@extends('layouts.layout')

@section('pageTitle') Registracija @endsection

@section('pageContent')
    <div class="container py-5">
        <div class="row g-0 auth-split">
            <div class="col-lg-6 d-flex justify-content-center py-lg-5">
                <div class="auth-box w-100 px-lg-4 py-lg-4">
                    <h1 class="page-title mb-4">Napravite <em>nalog</em></h1>

                    @include('partials.errors')

                    <form method="POST" action="{{ route('register.store') }}" data-validate novalidate>
                        @csrf
                        @include('partials.honeypot')
                        <div class="mb-3">
                            <label class="form-label" for="name">Ime i prezime</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" data-rules="required|min:3|max:100|name" autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="username" data-rules="required|email|max:100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Lozinka</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" data-rules="required|min:8|max:100|password">
                            <div class="form-text">Najmanje 8 karaktera, sa velikim slovom, malim slovom i brojem.</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Ponovite lozinku</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" data-rules="required|same:password" data-msg-same="Lozinke se ne poklapaju.">
                        </div>
                        <button class="btn btn-ink w-100" data-loading-text="Pravimo nalog...">Registruj se</button>
                    </form>

                    <p class="text-center text-body-secondary mt-4 mb-0">Već imate nalog? <a href="{{ route('login') }}" class="text-body">Prijavite se</a></p>
                </div>
            </div>
            <div class="col-lg-6 panel-soft d-lg-flex flex-column justify-content-center p-5 my-3">
                <p class="display-serif auth-side-title mb-4">Sa nalogom <em>možete</em></p>
                <ul class="list-unstyled rule-ink-top mb-0">
                    <li class="list-row">Da pratite svaku porudžbinu u delu Moje porudžbine</li>
                    <li class="list-row">Da nam pišete preko kontakt forme</li>
                    <li class="list-row">Da brže naručujete, sa već popunjenim imenom i emailom</li>
                    <li class="list-row">Da dobijate akcije i kupone samo za članove</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
