{{-- Tabs on the account pages (My orders, order details, profile) --}}
<nav class="account-nav" aria-label="Nalog">
    <a href="{{ route('account.orders') }}" @class(['active' => request()->routeIs('account.orders*')]) @if(request()->routeIs('account.orders*')) aria-current="page" @endif>Moje porudžbine</a>
    <a href="{{ route('account.profile') }}" @class(['active' => request()->routeIs('account.profile*')]) @if(request()->routeIs('account.profile*')) aria-current="page" @endif>Profil</a>
    <a href="{{ route('contact') }}">Podrška</a>
</nav>
