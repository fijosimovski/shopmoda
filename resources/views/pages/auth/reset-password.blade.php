@extends('layouts.layout')

@section('pageTitle') Nova lozinka @endsection

@section('pageContent')
    <div class="container py-5 auth-box">
        <h1 class="page-title page-title-sm mt-lg-4 mb-3">Postavite novu <em>lozinku</em></h1>
        <p class="text-body-secondary mb-4">Unesite šestocifreni kod iz emaila i izaberite novu lozinku.</p>

        @include('partials.errors')

        <form method="POST" action="{{ route('password.update') }}" class="rule-ink-top pt-4" data-validate novalidate>
            @csrf
            @include('partials.honeypot')
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $email) }}" data-rules="required|email|max:100" autocomplete="email">
            </div>
            <div class="mb-3">
                <label class="form-label" for="code">Kod iz emaila</label>
                <input type="text" class="form-control text-center confirm-order-code" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                       placeholder="000000" data-rules="required|min:6|max:6" data-msg-min="Kod ima 6 cifara." data-msg-max="Kod ima 6 cifara." @if($email) autofocus @endif>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Nova lozinka</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" data-rules="required|min:8|max:100|password">
                <div class="form-text">Najmanje 8 karaktera, sa velikim i malim slovima i brojem.</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">Ponovite novu lozinku</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" data-rules="required|same:password" data-msg-same="Lozinke se ne poklapaju.">
            </div>
            <button class="btn btn-ink w-100" data-loading-text="Menjamo lozinku...">Promeni lozinku</button>
        </form>

        <p class="text-center text-body-secondary mt-4">
            Nemate kod ili je istekao? <a href="{{ route('password.request') }}" class="text-body">Pošalji novi kod</a>
        </p>
    </div>
@endsection
