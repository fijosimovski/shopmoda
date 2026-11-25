<!DOCTYPE html>
<html lang="en">

@include('fixed.head')

<body class="admin">
<div class="d-flex flex-column flex-md-row min-vh-100">
    <!-- Sidebar -->
    <aside class="admin-side">
        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="shop-logo">SHOPMODA<span>.</span></span>
                <span class="admin-tag">Admin</span>
            </a>
            <button class="btn btn-square border-0 d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminMenu" aria-controls="adminMenu" aria-expanded="false" aria-label="Open admin menu">
                <i class="bi bi-list fs-4"></i>
            </button>
        </div>

        @php
            // Groups of [link route, routes that highlight this link, Bootstrap icon, label, (optional) count badge]
            $menu = [
                'Overview' => [
                    ['admin.dashboard', 'admin.dashboard', 'speedometer2', 'Dashboard'],
                    ['admin.analytics', 'admin.analytics', 'graph-up', 'Analytics'],
                    ['admin.visits.index', 'admin.visits.*', 'eye', 'Visits'],
                ],
                'Sales' => [
                    ['admin.orders.index', 'admin.orders.*', 'bag-check', 'Orders'],
                    ['admin.promotions.index', 'admin.promotions.*', 'lightning-charge', 'Promotions'],
                    ['admin.coupons.index', 'admin.coupons.*', 'ticket-perforated', 'Coupons'],
                ],
                'Catalog' => [
                    ['admin.products.index', 'admin.products.*', 'box-seam', 'Products'],
                    ['admin.models.index', 'admin.models.*', 'collection', 'Models'],
                    ['admin.brands.index', 'admin.brands.*', 'tags', 'Brands'],
                    ['admin.categories.index', 'admin.categories.*', 'grid', 'Categories'],
                ],
                'Site' => [
                    ['admin.announcements.index', 'admin.announcements.*', 'megaphone', 'Announcements'],
                    ['admin.messages.index', 'admin.messages.*', 'envelope', 'Messages', \App\Models\ContactMessage::unread()->count()],
                    ['admin.users.index', 'admin.users.*', 'people', 'Users'],
                    ['admin.logs.index', 'admin.logs.*', 'clock-history', 'User logs'],
                    ['admin.settings.edit', 'admin.settings.*', 'gear', 'Settings'],
                ],
            ];
        @endphp
        <div class="collapse d-md-block" id="adminMenu">
            @foreach($menu as $group => $items)
                <nav class="admin-menu" aria-label="{{ $group }}">
                    <div class="admin-menu-title">{{ $group }}</div>
                    @foreach($items as $item)
                        @php([$link, $active, $icon, $label] = $item)
                        <a href="{{ route($link) }}" @class(['active' => request()->routeIs($active)]) @if(request()->routeIs($active)) aria-current="page" @endif>
                            <span><i class="bi bi-{{ $icon }} admin-menu-icon" aria-hidden="true"></i>{{ $label }}</span>
                            @if(!empty($item[4]))
                                <span class="admin-count">{{ $item[4] }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            @endforeach

            <div class="admin-side-foot">
                <a href="{{ route('home') }}" class="link-caps" target="_blank">View shop</a>
                <div class="small text-body-secondary mt-3">{{ auth()->user()->name }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="btn btn-outline-ink btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Page Content -->
    <main class="admin-main flex-grow-1">
        @include('fixed.flash')
        @yield('pageContent')
    </main>
</div>

@include('fixed.scripts')
</body>

</html>
