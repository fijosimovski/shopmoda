{{--
    Shared error page inside the shop layout (navigation, footer, cart drawer), used by 401, 403, 404, 405, 410, 419 and 429.
    The child view sets the code, title, text and optional extra content (search on 404).
    500 and 503 use errors/minimal.blade.php instead, because the shop layout reads the database
    (categories, announcements, cart), which may be exactly what is broken.
--}}
@extends('layouts.layout')

@section('pageTitle')@yield('errorTitle')@endsection
@section('robots', 'noindex, nofollow')

@section('pageContent')
    <div class="container py-5">
        <div class="error-page rule-ink-top text-center mx-auto">
            <div class="error-code" aria-hidden="true">@yield('code')</div>
            <h1 class="page-title page-title-sm mb-3">@yield('errorTitle')</h1>
            <p class="lead-muted mb-4">@yield('errorText')</p>

            @yield('errorExtra')

            <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                @section('errorActions')
                    <a href="{{ route('home') }}" class="btn btn-ink">Početna strana</a>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-dark rounded-0">Sve patike</a>
                @show
            </div>
        </div>
    </div>
@endsection
