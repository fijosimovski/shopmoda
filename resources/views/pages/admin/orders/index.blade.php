@extends('layouts.admin')

@section('pageTitle') Orders @endsection

@section('pageContent')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <h1 class="h3 mb-0">Orders</h1>

        {{-- Download the orders from a period: the button pressed picks the file type --}}
        <form method="GET" action="{{ route('admin.orders.export') }}" class="d-flex align-items-center gap-2">
            <label for="export_range" class="small text-secondary text-nowrap"><i class="bi bi-download me-1"></i>Export</label>
            <select name="range" id="export_range" class="form-select form-select-sm w-auto">
                @foreach(\App\Services\OrderExport::RANGES as $key => [$label])
                    <option value="{{ $key }}" @selected($key === '24h')>{{ $label }}</option>
                @endforeach
            </select>
            <button name="format" value="xlsx" class="btn btn-sm btn-success text-nowrap"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
            <button name="format" value="txt" class="btn btn-sm btn-outline-dark text-nowrap"><i class="bi bi-file-earmark-text me-1"></i>TXT</button>
        </form>
    </div>

    <form method="GET" class="row g-2 mb-3" style="max-width: 640px">
        <div class="col">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Order number, name, email or IP">
        </div>
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(\App\Models\Order::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-dark btn-match-field">Search</button></div>
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>#</th><th>Customer</th><th>City</th><th>IP address</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($orders as $order)
                <tr @class(['admin-row-new' => $order->created_at?->gt(now()->subDay())]) style="cursor: pointer" onclick="location.href='{{ route('admin.orders.show', $order) }}'">
                    <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                    <td>{{ $order->name }} @if($order->isGuest())<span class="badge bg-light text-dark border">guest</span>@endif<br>
                        <small class="text-secondary">{{ $order->email }}</small></td>
                    <td>{{ $order->city }}</td>
                    <td class="small font-monospace">{{ $order->ip_address ?? '–' }}</td>
                    <td class="small">{{ $order->created_at->format('d.m.Y. H:i') }}</td>
                    <td>{{ $order->items_count }}</td>
                    <td>@money($order->total)</td>
                    <td>@include('partials.status-badge', ['status' => $order->status])</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary p-3">No orders found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
