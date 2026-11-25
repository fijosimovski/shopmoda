@extends('layouts.admin')

@section('pageTitle') Order {{ $order->number }} @endsection

@section('pageContent')
    <a href="{{ route('admin.orders.index') }}" class="small text-decoration-none">&larr; Orders</a>
    <h1 class="h3 mt-2 mb-4">Order {{ $order->number }} @include('partials.status-badge', ['status' => $order->status])</h1>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <table class="table align-middle mb-0">
                    <thead><tr><th></th><th>Product</th><th>Size</th><th>Qty</th><th>Price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td><img src="{{ $item->product->mainImage?->url() }}" width="48" height="48" class="rounded" style="object-fit: cover" alt="{{ $item->product->name }}"></td>
                            <td>
                                {{ $item->product->name }}
                                @if($item->product->trashed())<span class="badge bg-secondary">deleted</span>@endif
                            </td>
                            <td>{{ $item->size }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>@money($item->unit_price)</td>
                            <td class="text-end">@money($item->subtotal())</td>
                        </tr>
                    @endforeach
                    @if($order->discounts->isNotEmpty())
                        <tr>
                            <td colspan="5" class="text-end">Subtotal</td>
                            <td class="text-end">@money($order->subtotal)</td>
                        </tr>
                        @foreach($order->discounts as $discount)
                            <tr class="text-success">
                                <td colspan="5" class="text-end"><i class="bi bi-tag me-1"></i>{{ $discount->name }}</td>
                                <td class="text-end text-nowrap">-@money($discount->amount)</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end">Total (cash on delivery)</td>
                        <td class="text-end">@money($order->total)</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Change status</h2>
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="d-flex gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-select">
                            @foreach(\App\Models\Order::STATUSES as $status)
                                <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-dark">Save</button>
                    </form>
                    <div class="form-text">Cancelled or expired puts the items back in stock and releases the coupon use.</div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body small">
                    <h2 class="h6 fw-bold">Customer</h2>
                    <p class="mb-1"><strong>{{ $order->name }}</strong>
                        @if($order->isGuest())
                            <span class="badge bg-light text-dark border">guest</span>
                        @else
                            <span class="text-secondary">(account: {{ $order->user?->email }})</span>
                        @endif
                    </p>
                    <p class="mb-1"><i class="bi bi-envelope me-1"></i>{{ $order->email }}</p>
                    <p class="mb-1"><i class="bi bi-telephone me-1"></i>{{ $order->phone }}</p>
                    <p class="mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $order->address }}, {{ $order->city }}</p>
                    @if($order->note)<p class="mb-1"><i class="bi bi-chat-left-text me-1"></i>{{ $order->note }}</p>@endif
                    <p class="mb-1"><i class="bi bi-globe me-1"></i>IP: <span class="font-monospace">{{ $order->ip_address ?? 'unknown' }}</span></p>
                    <p class="text-secondary mb-0 mt-2">Placed {{ $order->created_at->format('d.m.Y. H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
