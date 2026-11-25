{{-- Usage: @include('partials.status-badge', ['status' => $order->status])  A colored dot + the status name. --}}
<span class="status-dot status-{{ $status }}">{{ \App\Models\Order::statusLabel($status) }}</span>
