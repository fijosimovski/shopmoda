@extends('layouts.layout')

@section('pageTitle') Profil @endsection

@section('pageContent')
    <div class="container pt-5 pb-4">
        <h1 class="page-title">Profil</h1>
        @include('partials.account-nav')

        <div class="account-form mt-5">
            @include('partials.errors')

            <form method="POST" action="{{ route('account.profile.update') }}" data-validate novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label" for="name">Ime i prezime</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" data-rules="required|min:3|max:100|name">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    {{-- Read only and without a name, so it's never sent; the server ignores it anyway. --}}
                    <input type="email" class="form-control" id="email" value="{{ $user->email }}" autocomplete="username" disabled readonly>
                    <div class="form-text">Email ne može da se promeni. <a href="{{ route('contact') }}" class="text-body">Pišite nam</a> ako vam je potrebna pomoć.</div>
                </div>

                <fieldset class="rule-ink-top pt-4 mt-5">
                    <legend class="label-caps text-body fw-semibold mb-2">Lozinka</legend>
                    <p class="small text-body-secondary mb-4">Ostavite polja za lozinku prazna ako ne želite da je menjate.</p>

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Trenutna lozinka</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" data-rules="required_with:password" data-msg-required-with="Unesite trenutnu lozinku da biste postavili novu.">
                    </div>
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Nova lozinka</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" data-rules="min:8|max:100|password|different:current_password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirmation">Ponovite novu lozinku</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" data-rules="required_with:password|same:password" data-msg-same="Lozinke se ne poklapaju.">
                        </div>
                    </div>
                    <div class="form-text mb-4">Najmanje 8 karaktera, sa velikim slovom, malim slovom i brojem.</div>
                </fieldset>

                <button class="btn btn-ink btn-lg" data-loading-text="Čuvamo...">Sačuvaj</button>
            </form>
        </div>
    </div>
@endsection
