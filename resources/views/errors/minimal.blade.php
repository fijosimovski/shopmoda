{{--
    Standalone error page for 500 and 503: no database, session or view composers, so it still shows
    when the database is down or during maintenance (php artisan down). Same look as the shop, from public/css/shopmoda.css.
--}}
<!DOCTYPE html>
<html lang="sr-Latn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('errorTitle') | ShopModa</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Jost:wght@400;500&family=Inter+Tight:wght@800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/shopmoda.css') }}" rel="stylesheet">
</head>
<body class="shop d-flex flex-column min-vh-100">
    <header class="border-bottom bg-white">
        <div class="container py-3">
            <a class="shop-logo" href="{{ url('/') }}">SHOPMODA<span>.</span></a>
        </div>
    </header>

    <main class="flex-grow-1 d-flex align-items-center">
        <div class="container py-5">
            <div class="error-page rule-ink-top text-center mx-auto">
                <div class="error-code" aria-hidden="true">@yield('code')</div>
                <h1 class="page-title page-title-sm mb-3">@yield('errorTitle')</h1>
                <p class="lead-muted mb-4">@yield('errorText')</p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ url()->current() }}" class="btn btn-ink">Pokušaj ponovo</a>
                    <a href="{{ url('/') }}" class="btn btn-outline-dark rounded-0">Početna strana</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="border-top">
        <div class="container py-3 small text-body-secondary">&copy; {{ date('Y') }} ShopModa</div>
    </footer>
</body>
</html>
