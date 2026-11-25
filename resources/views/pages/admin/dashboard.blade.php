@extends('layouts.admin')

@section('pageTitle') Dashboard @endsection

@section('pageContent')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <div class="small text-body-secondary">{{ now()->format('l, j F Y') }}</div>
            <h1 class="h3 mb-0">Dashboard</h1>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-ink">New product</a>
    </div>

    <div class="admin-kpis mb-4">
        @foreach([
            ['New orders', $stats['new_orders'], route('admin.orders.index', ['status' => 'new'])],
            ['Orders today', $stats['orders_today'], route('admin.orders.index')],
            ['Revenue this month', number_format($stats['revenue_month'], 0, ',', '.') . ' RSD', null],
            ['Products', $stats['products'], route('admin.products.index')],
            ['Customers', $stats['customers'], route('admin.users.index')],
        ] as [$label, $value, $link])
            <div class="admin-kpi position-relative">
                <div class="label-caps">{{ $label }}</div>
                <div class="admin-kpi-value">{{ $value }}</div>
                @if($link)<a href="{{ $link }}" class="stretched-link" aria-label="{{ $label }}"></a>@endif
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-baseline">
                    <span>Latest orders</span>
                    <a href="{{ route('admin.orders.index') }}" class="link-caps">All orders</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                        @forelse($latestOrders as $order)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-medium">{{ $order->number }}</a></td>
                                <td>{{ $order->name }} @if($order->isGuest())<span class="admin-chip">guest</span>@endif</td>
                                <td class="small text-body-secondary">{{ $order->created_at->diffForHumans() }}</td>
                                <td class="text-nowrap">@money($order->total)</td>
                                <td>@include('partials.status-badge', ['status' => $order->status])</td>
                            </tr>
                        @empty
                            <tr><td class="text-body-secondary">No orders yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card mb-4">
                <div class="card-header">Best sellers</div>
                <ul class="list-group list-group-flush">
                    @foreach($bestSellers as $product)
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a>
                            <span class="text-body-secondary">{{ (int) $product->sold_count }} sold</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div @class(['card', 'card-alert' => $lowStock->isNotEmpty()])>
                <div class="card-header">Low stock (2 or less)</div>
                <ul class="list-group list-group-flush">
                    @forelse($lowStock as $size)
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="{{ route('admin.products.edit', $size->product) }}">{{ $size->product->name }}</a>
                            <span @class(['text-sale' => (int) $size->stock === 0])>EU {{ $size->size }} · {{ (int) $size->stock === 0 ? 'sold out' : $size->stock . ' left' }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">Everything is well stocked.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
