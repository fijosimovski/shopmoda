@extends('layouts.admin')

@section('pageTitle') Visits @endsection

@section('pageContent')
    @php
        $statusColor = fn ($s) => match (true) { $s >= 500 => 'danger', $s >= 400 => 'warning', $s >= 300 => 'secondary', default => 'success' };
        $deviceIcon = ['desktop' => 'display', 'mobile' => 'phone', 'tablet' => 'tablet', 'bot' => 'robot'];
        $filtered = request()->hasAny(['q', 'ip', 'device', 'status', 'method', 'date', 'user']);
    @endphp

    <div class="mb-4">
        <h1 class="h3 mb-0">Visits</h1>
        <div class="small text-secondary">
            Every request to the shop, also from bots and to pages that don't exist. Your own admin browsing is not saved.
            Rows are deleted after {{ $keepDays }} days; daily totals stay on the <a href="{{ route('admin.analytics') }}" class="link-secondary">Analytics</a> page.
        </div>
    </div>

    {{-- ---------- Last 24 hours ---------- --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['Requests', $last24h['requests'], 'arrow-left-right', 'dark', null],
            ['Page views', $last24h['views'], 'eye', 'primary', null],
            ['Visitors', $last24h['visitors'], 'people', 'success', null],
            ['Bot requests', $last24h['bots'], 'robot', 'secondary', ['device' => 'bot']],
            ['4xx errors', $last24h['client_errors'], 'exclamation-triangle', 'warning', ['status' => '4xx']],
            ['5xx errors', $last24h['server_errors'], 'x-octagon', 'danger', ['status' => '5xx']],
        ] as [$label, $value, $icon, $color, $filter])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-nowrap"><i class="bi bi-{{ $icon }} text-{{ $color }} me-1"></i>{{ $label }}</div>
                        <div class="fs-5 fw-bold">{{ number_format($value, 0, ',', '.') }}</div>
                        <div class="small text-secondary">last 24 hours</div>
                        @if($filter)<a href="{{ route('admin.visits.index', $filter + ['date' => now()->toDateString()]) }}" class="stretched-link" aria-label="Show {{ $label }} from today"></a>@endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ---------- Who to look at ---------- --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    Most active IPs <span class="small text-secondary fw-normal">(last 24 hours · many 4xx or POSTs from one IP = worth a look)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th class="ps-3">IP</th><th class="text-end">Requests</th><th class="text-end">4xx</th><th class="text-end">POSTs</th><th class="text-end">Bot</th><th class="pe-3">Last seen</th></tr></thead>
                        <tbody>
                        @forelse($topIps as $row)
                            <tr>
                                <td class="ps-3"><a href="{{ route('admin.visits.index', ['ip' => $row->ip_address]) }}" class="font-monospace text-decoration-none">{{ $row->ip_address }}</a>
                                    @if($row->user_id)<span class="badge bg-light text-dark border">user</span>@endif</td>
                                <td class="text-end">{{ $row->requests }}</td>
                                <td class="text-end {{ $row->client_errors > 10 ? 'text-danger fw-semibold' : '' }}">{{ (int) $row->client_errors }}</td>
                                <td class="text-end">{{ (int) $row->posts }}</td>
                                <td class="text-end">{{ (int) $row->bot_requests }}</td>
                                <td class="small text-secondary text-nowrap pe-3">{{ \Illuminate\Support\Carbon::parse($row->last_seen)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-secondary ps-3">No requests in the last 24 hours.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    Pages not found <span class="small text-secondary fw-normal">(last 7 days · broken links or scanners)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th class="ps-3">Path</th><th class="text-end">Hits</th><th class="text-end pe-3">IPs</th></tr></thead>
                        <tbody>
                        @forelse($missingPages as $row)
                            <tr>
                                <td class="ps-3"><a href="{{ route('admin.visits.index', ['status' => '404', 'q' => $row->path]) }}" class="d-inline-block text-truncate visit-path font-monospace small text-decoration-none" title="/{{ $row->path }}">/{{ $row->path }}</a></td>
                                <td class="text-end">{{ $row->hits }}</td>
                                <td class="text-end pe-3">{{ $row->ips }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary ps-3">No 404s in the last 7 days.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ---------- All requests ---------- --}}
    <h2 class="h5 mb-3">All requests</h2>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6 col-xl-3"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Path, query or browser..."></div>
        <div class="col-6 col-md-3 col-xl-2"><input type="search" name="ip" value="{{ request('ip') }}" class="form-control form-control-sm font-monospace" placeholder="Exact IP"></div>
        <div class="col-6 col-md-3 col-xl-2"><input type="search" name="user" value="{{ request('user') }}" class="form-control form-control-sm" placeholder="User email"></div>
        <div class="col-6 col-md-3 col-xl-2"><input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm" aria-label="Day"></div>
        <div class="col-4 col-md-3 col-xl-1">
            <select name="status" class="form-select form-select-sm" aria-label="Status">
                <option value="">Status</option>
                @foreach([...$statusGroups, '404'] as $group)
                    <option value="{{ $group }}" @selected(request('status') === $group)>{{ $group }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-md-3 col-xl-1">
            <select name="device" class="form-select form-select-sm" aria-label="Device">
                <option value="">Device</option>
                @foreach(\App\Models\PageView::DEVICES as $device)
                    <option value="{{ $device }}" @selected(request('device') === $device)>{{ ucfirst($device) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4 col-md-3 col-xl-1">
            <select name="method" class="form-select form-select-sm" aria-label="Method">
                <option value="">Method</option>
                @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                    <option value="{{ $method }}" @selected(request('method') === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap align-items-center gap-2">
            <button class="btn btn-sm btn-dark">Filter</button>
            @if($filtered)<a href="{{ route('admin.visits.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>@endif
            <div class="d-flex align-items-center gap-2 ms-auto">
                <label for="per_page" class="small text-secondary text-nowrap">Per page</label>
                <select name="per_page" id="per_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    @foreach($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((int) request('per_page', $perPageOptions[0]) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    {{-- Page info + links above the table too, so you don't have to scroll down to change page --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <span class="small text-secondary">
            @if($views->total())
                Showing {{ number_format($views->firstItem(), 0, ',', '.') }}–{{ number_format($views->lastItem(), 0, ',', '.') }}
                of {{ number_format($views->total(), 0, ',', '.') }} {{ Str::plural('request', $views->total()) }}
            @else
                0 requests
            @endif
        </span>
        @if($views->hasPages())
            {{ $views->links('pagination::simple-bootstrap-5') }}
        @endif
    </div>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
            <tr><th>When</th><th>Visitor</th><th>IP</th><th>Request</th><th>Status</th><th>Device</th><th>From</th><th class="text-end">ms</th></tr>
            </thead>
            <tbody>
            @forelse($views as $view)
                <tr>
                    <td class="small text-nowrap">{{ $view->created_at->format('d.m.Y. H:i:s') }}</td>
                    <td class="small">
                        @if($view->user)
                            <a href="{{ route('admin.visits.index', ['user' => $view->user->email]) }}" class="text-decoration-none">{{ $view->user->email }}</a>
                        @else
                            <span class="text-secondary">guest</span>
                        @endif
                    </td>
                    <td class="small"><a href="{{ route('admin.visits.index', ['ip' => $view->ip_address]) }}" class="font-monospace text-decoration-none">{{ $view->ip_address }}</a></td>
                    <td class="small">
                        <span class="badge {{ $view->method === 'GET' ? 'bg-light text-dark border' : 'bg-dark' }}">{{ $view->method }}</span>
                        <span class="d-inline-block text-truncate visit-path align-bottom font-monospace" title="{{ $view->url() }}">{{ $view->url() }}</span>
                        @if($view->product_id)<i class="bi bi-box-seam text-secondary" title="Product page"></i>@endif
                    </td>
                    <td><span class="badge text-bg-{{ $statusColor($view->status) }}">{{ $view->status }}</span></td>
                    <td><i class="bi bi-{{ $deviceIcon[$view->device] ?? 'question' }}" title="{{ ucfirst($view->device) }}: {{ $view->user_agent }}"></i></td>
                    <td class="small text-secondary"><span class="d-inline-block text-truncate visit-referrer align-bottom" title="{{ $view->referrer }}">{{ $view->referrerHost() }}</span></td>
                    <td class="small text-end text-secondary">{{ $view->duration_ms }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary p-3">No requests{{ $filtered ? ' match these filters' : ' saved yet' }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($views->hasPages())
        <div class="mt-3 d-flex justify-content-center">{{ $views->links() }}</div>
    @endif
@endsection
