@extends('layouts.layout')

@section('pageTitle') Kontakt @endsection

@section('pageContent')
    <div class="container pt-5 pb-4">
        <div class="row g-5">
            <div class="col-lg-8">
                <h1 class="page-title">Kontaktirajte <em>nas</em></h1>
                <p class="lead-muted mt-3 mb-4">Pitanje o porudžbini, veličini ili povraćaju? Pošaljite nam poruku, a mi odgovaramo emailom.</p>

                @include('partials.errors')

                <form method="POST" action="{{ route('contact.store') }}" class="rule-ink-top pt-4" data-validate novalidate>
                    @csrf
                    @include('partials.honeypot')

                    <p class="small text-body-secondary mb-4">
                        Šaljete kao <strong class="text-body fw-semibold">{{ $user->name }}</strong> &middot; odgovor stiže na <strong class="text-body fw-semibold">{{ $user->email }}</strong>
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="subject">Naslov</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject') }}" placeholder="npr. Zamena za veći broj" data-rules="required|min:3|max:120">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="message">Poruka</label>
                        <textarea class="form-control" id="message" name="message" rows="7" data-rules="required|min:10|max:3000">{{ old('message') }}</textarea>
                        <div class="form-text">Navedite broj porudžbine ako se poruka odnosi na nju.</div>
                    </div>
                    <button class="btn btn-ink btn-lg" data-loading-text="Šaljemo poruku...">Pošalji poruku</button>
                </form>
            </div>

            <div class="col-lg-4">
                <aside class="panel-soft">
                    <h2 class="label-caps text-body fw-semibold mb-3">Drugi načini kontakta</h2>
                    @if($phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="serif-lg d-block text-body text-decoration-none">{{ $phone }}</a>
                        <p class="small text-body-secondary mb-0 mt-1">Pon–pet, 9–17h</p>
                    @else
                        <p class="small text-body-secondary mb-0">Pišite nam preko forme, a mi ćemo vam odgovoriti emailom.</p>
                    @endif
                    <a href="{{ route('how-to-order') }}" class="link-caps d-inline-block mt-4">Kako se naručuje</a>
                </aside>
            </div>
        </div>
    </div>
@endsection
