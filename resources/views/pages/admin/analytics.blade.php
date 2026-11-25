@extends('layouts.admin')

@section('pageTitle') Analytics @endsection

@section('pageContent')
    @php
        // % change vs the previous period, or null when there is nothing to compare with.
        $change = function (string $key) use ($kpis, $prevKpis) {
            if (! $prevKpis || ! $prevKpis[$key]) {
                return null;
            }
            return ($kpis[$key] - $prevKpis[$key]) / $prevKpis[$key] * 100;
        };
        $rsd = fn ($v) => number_format($v, 0, ',', '.') . ' RSD';
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Analytics</h1>
            <div class="small text-secondary">
                {{ $from ? $from->format('d.m.Y.') . ' – ' . now()->format('d.m.Y.') : 'All time' }} · sales = processing, shipped and completed orders
                (<a href="#otherOrders" class="link-secondary">the rest is below</a>)
            </div>
        </div>
        <div class="btn-group" role="group" aria-label="Period">
            @foreach($periods as $value => $label)
                <a href="{{ route('admin.analytics', ['days' => $value]) }}" class="btn btn-sm {{ $days === $value ? 'btn-dark' : 'btn-outline-dark' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- ---------- KPIs ---------- --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['Revenue', $rsd($kpis['revenue']), 'cash-stack', 'success', $change('revenue')],
            ['Orders', $kpis['orders'], 'bag', 'primary', $change('orders')],
            ['Avg. order value', $rsd($kpis['avg_order']), 'receipt', 'info', $change('avg_order')],
            ['Items sold', $kpis['items_sold'], 'box-seam', 'warning', $change('items_sold')],
            ['Buyers', $kpis['buyers'], 'person-check', 'secondary', $change('buyers')],
            ['New customers', $kpis['new_customers'], 'person-plus', 'primary', $change('new_customers')],
            ['Repeat buyers', number_format($kpis['repeat_rate'], 1) . '%', 'arrow-repeat', 'success', null],
            ['Guest orders', number_format($kpis['guest_share'], 1) . '%', 'incognito', 'secondary', null],
        ] as [$label, $value, $icon, $color, $delta])
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-nowrap"><i class="bi bi-{{ $icon }} text-{{ $color }} me-1"></i>{{ $label }}</div>
                        <div class="fs-5 fw-bold text-nowrap">{{ $value }}</div>
                        @if(! is_null($delta))
                            <div class="small {{ $delta >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-arrow-{{ $delta >= 0 ? 'up' : 'down' }}-short"></i>{{ number_format(abs($delta), 1) }}%
                                <span class="text-secondary">vs prev.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ---------- Over time ---------- --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Revenue per {{ $timeline['byMonth'] ? 'month' : 'day' }}</div>
                <div class="card-body"><div class="chart-box"><canvas id="revenueChart" aria-label="Revenue over time"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Sales by status</div>
                <ul class="list-group list-group-flush">
                    @foreach($statuses as $row)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>@include('partials.status-badge', ['status' => $row['status']])</span>
                                <span><strong>{{ $row['orders'] }}</strong> <span class="small text-secondary">· {{ $rsd($row['total']) }}</span></span>
                            </div>
                            <div class="progress share-bar mt-2"><div class="progress-bar bg-dark" data-share="{{ round($row['share'], 1) }}"></div></div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Orders per {{ $timeline['byMonth'] ? 'month' : 'day' }}</div>
                <div class="card-body"><div class="chart-box"><canvas id="ordersChart" aria-label="Orders over time"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Orders by day of week</div>
                <div class="card-body"><div class="chart-box"><canvas id="weekdayChart" aria-label="Orders by day of week"></canvas></div></div>
            </div>
        </div>
    </div>

    {{-- ---------- What sells ---------- --}}
    <div class="row g-4 mb-4">
        @foreach([['Sales by category', $categories], ['Sales by brand', $brands], ['Sales by gender', $genders]] as [$title, $rows])
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">{{ $title }}</div>
                    <ul class="list-group list-group-flush">
                        @forelse($rows as $row)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span>{{ $row['label'] }}</span>
                                    <span><strong>{{ $rsd($row['revenue']) }}</strong> <span class="small text-secondary">· {{ $row['units'] }} pcs</span></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <div class="progress share-bar flex-grow-1"><div class="progress-bar" data-share="{{ round($row['share'], 1) }}"></div></div>
                                    <span class="small text-secondary">{{ number_format($row['share'], 0) }}%</span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-secondary">No sales in this period.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Top products</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>#</th><th>Product</th><th class="text-end">Sold</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                        @forelse($topProducts as $i => $product)
                            <tr>
                                <td class="text-secondary">{{ $i + 1 }}</td>
                                <td><a href="{{ route('admin.products.edit', $product->id) }}" class="text-decoration-none">{{ $product->name }}</a></td>
                                <td class="text-end">{{ (int) $product->units }}</td>
                                <td class="text-end">@money($product->revenue)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary">No sales in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Pieces sold by size</div>
                <div class="card-body"><div class="chart-box"><canvas id="sizeChart" aria-label="Pieces sold by size"></canvas></div></div>
            </div>
        </div>
    </div>

    {{-- ---------- Customers ---------- --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Top customers</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Customer</th><th class="text-end">Orders</th><th class="text-end">Spent</th></tr></thead>
                        <tbody>
                        @forelse($topCustomers as $customer)
                            <tr>
                                <td>{{ $customer->name }} @unless($customer->user_id)<span class="badge bg-light text-dark border">guest</span>@endunless<br>
                                    <small class="text-secondary">{{ $customer->email }}</small></td>
                                <td class="text-end">{{ $customer->orders }}</td>
                                <td class="text-end">@money($customer->spent)</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">No orders in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Top cities</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>City</th><th class="text-end">Orders</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                        @forelse($topCities as $city)
                            <tr>
                                <td>{{ $city->city }}</td>
                                <td class="text-end">{{ $city->orders }}</td>
                                <td class="text-end">@money($city->revenue)</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">No orders in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ---------- Orders that are not sales (unconfirmed, new, cancelled, expired) ---------- --}}
    <h2 class="h5 mb-1" id="otherOrders">Orders outside sales</h2>
    <p class="small text-secondary mb-3">
        Not counted in the numbers above: waiting for the email code, confirmed but not handled yet, cancelled, or never confirmed (expired).
        {{ $other['placed'] }} {{ Str::plural('order', $other['placed']) }} placed in this period in total.
    </p>

    <div class="row g-3 mb-3">
        @foreach($other['statuses'] as $row)
            <div class="col-6 col-xl-3">
                <a href="{{ route('admin.orders.index', ['status' => $row['status']]) }}" class="card border-0 shadow-sm h-100 text-decoration-none text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            @include('partials.status-badge', ['status' => $row['status']])
                            <span class="small text-secondary">{{ number_format($row['share'], 1) }}% of placed</span>
                        </div>
                        <div class="fs-5 fw-bold mt-2">{{ $row['orders'] }} <span class="fs-6 fw-normal text-secondary">· {{ $rsd($row['total']) }}</span></div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Confirmation rate', number_format($other['confirm_rate'], 1) . '%', 'patch-check', 'success', 'placed orders confirmed with the email code'],
            ['Cancellation rate', number_format($other['cancel_rate'], 1) . '%', 'x-circle', 'danger', 'of placed orders'],
            ['Expiry rate', number_format($other['expire_rate'], 1) . '%', 'hourglass-bottom', 'dark', 'of placed orders, never confirmed'],
            ['Lost value', $rsd($other['lost_value']), 'graph-down-arrow', 'danger', 'cancelled + expired'],
            ['Waiting value', $rsd($other['pending_value']), 'hourglass-split', 'primary', 'unconfirmed + new, can still become sales'],
        ] as [$label, $value, $icon, $color, $hint])
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-nowrap"><i class="bi bi-{{ $icon }} text-{{ $color }} me-1"></i>{{ $label }}</div>
                        <div class="fs-5 fw-bold text-nowrap">{{ $value }}</div>
                        <div class="small text-secondary">{{ $hint }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Orders outside sales per {{ $timeline['byMonth'] ? 'month' : 'day' }}</div>
                <div class="card-body"><div class="chart-box"><canvas id="otherOrdersChart" aria-label="Unconfirmed, new, cancelled and expired orders over time"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Most often not bought <span class="small text-secondary fw-normal">(cancelled + expired)</span></div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th class="ps-3">Product</th><th class="text-end">Pcs</th><th class="text-end pe-3">Value</th></tr></thead>
                        <tbody>
                        @forelse($other['lost_products'] as $row)
                            <tr>
                                <td class="ps-3">{{ $row->name }}</td>
                                <td class="text-end">{{ $row->units }}</td>
                                <td class="text-end text-nowrap pe-3">{{ $rsd($row->value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary ps-3">No cancelled or expired orders in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ---------- Visitors ---------- --}}
    <h2 class="h5 mb-1" id="visitors">Visitors</h2>
    <p class="small text-secondary mb-3">
        Pages people opened (bots and admins left out). A visit = one visitor on one day.
        Every single request is on the <a href="{{ route('admin.visits.index') }}" class="link-secondary">Visits</a> page.
    </p>

    <div class="row g-3 mb-4">
        @foreach([
            ['Page views', number_format($visits['views'], 0, ',', '.'), 'eye', 'primary', null],
            ['Visits', number_format($visits['visits'], 0, ',', '.'), 'people', 'success', null],
            ['Pages per visit', number_format($visits['views_per_visit'], 1), 'files', 'info', null],
            ['Visit → order', number_format($visits['conversion'], 2) . '%', 'cart-check', 'warning', 'placed orders per visit'],
            ['Bot requests', number_format($visits['bots'], 0, ',', '.'), 'robot', 'secondary', 'crawlers and scripts'],
        ] as [$label, $value, $icon, $color, $hint])
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-nowrap"><i class="bi bi-{{ $icon }} text-{{ $color }} me-1"></i>{{ $label }}</div>
                        <div class="fs-5 fw-bold text-nowrap">{{ $value }}</div>
                        @if($hint)<div class="small text-secondary">{{ $hint }}</div>@endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Page views and visits per {{ $timeline['byMonth'] ? 'month' : 'day' }}</div>
                <div class="card-body"><div class="chart-box"><canvas id="visitsChart" aria-label="Page views and visits over time"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Devices <span class="small text-secondary fw-normal">(since {{ $visits['raw_from']->format('d.m.Y.') }})</span></div>
                <ul class="list-group list-group-flush">
                    @forelse($visits['devices'] as $row)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between"><span>{{ $row['label'] }}</span><span><strong>{{ $row['views'] }}</strong> <span class="small text-secondary">views</span></span></div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <div class="progress share-bar flex-grow-1"><div class="progress-bar" data-share="{{ round($row['share'], 1) }}"></div></div>
                                <span class="small text-secondary">{{ number_format($row['share'], 0) }}%</span>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary">No visits yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Most viewed products <span class="small text-secondary fw-normal">(viewed a lot but sold little = check price, photos or sizes)</span></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Views</th><th class="text-end">Visits</th><th class="text-end">Sold</th><th class="text-end">Sold per 100 visits</th></tr></thead>
                        <tbody>
                        @forelse($visits['products'] as $product)
                            <tr>
                                <td><a href="{{ route('admin.products.edit', $product->id) }}" class="text-decoration-none">{{ $product->name }}</a></td>
                                <td class="text-end">{{ $product->views }}</td>
                                <td class="text-end">{{ $product->visits }}</td>
                                <td class="text-end">{{ $product->sold }}</td>
                                <td class="text-end">{{ $product->visits ? number_format($product->sold / $product->visits * 100, 1) : '–' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-secondary">No product views in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Where visitors come from <span class="small text-secondary fw-normal">(since {{ $visits['raw_from']->format('d.m.Y.') }})</span></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Site</th><th class="text-end">Views</th><th class="text-end">Visitors</th></tr></thead>
                        <tbody>
                        @forelse($visits['referrers'] as $row)
                            <tr>
                                <td>{{ $row->host }}</td>
                                <td class="text-end">{{ $row->views }}</td>
                                <td class="text-end">{{ $row->visitors }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">No visits from other sites yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ---------- Shop right now ---------- --}}
    <h2 class="h5 mb-3">Shop right now <span class="small text-secondary fw-normal">(not affected by the period)</span></h2>
    <div class="row g-3">
        @foreach([
            ['Products', $inventory['products'], 'box-seam', route('admin.products.index')],
            ['Active', $inventory['active'], 'eye', null],
            ['Hidden', $inventory['inactive'], 'eye-slash', null],
            ['Featured', $inventory['featured'], 'star', null],
            ['On discount', $inventory['discounted'], 'percent', null],
            ['Pieces in stock', $inventory['stock_units'], 'stack', null],
            ['Stock value', $rsd($inventory['stock_value']), 'safe', null],
            ['Sold-out sizes', $inventory['out_of_stock_sizes'], 'exclamation-triangle', null],
            ['Products with no stock', $inventory['no_stock_products'], 'x-octagon', null],
            ['Never sold', $inventory['never_sold'], 'hourglass', null],
            ['Customers', $inventory['customers'], 'people', route('admin.users.index')],
        ] as [$label, $value, $icon, $link])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small"><i class="bi bi-{{ $icon }} me-1"></i>{{ $label }}</div>
                        <div class="fs-5 fw-bold text-nowrap">{{ $value }}</div>
                        @if($link)<a href="{{ $link }}" class="stretched-link"></a>@endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Chart data for public/js/admin-analytics.js --}}
    @php($chartData = ['timeline' => $timeline, 'weekdays' => $weekdays, 'sizes' => $sizes, 'other' => $other['chart'], 'visits' => $visits['chart']])
    <script type="application/json" id="analyticsData">@json($chartData)</script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/admin-analytics.js') }}"></script>
@endpush
