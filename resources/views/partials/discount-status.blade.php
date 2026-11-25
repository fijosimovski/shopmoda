{{-- Usage: @include('partials.discount-status', ['status' => $promotion->status()])  (promotions and coupons) --}}
@php
    $colors = [
        'running' => 'success',
        'upcoming' => 'info',
        'scheduled' => 'info',
        'ended' => 'secondary',
        'expired' => 'secondary',
        'used up' => 'dark',
        'off' => 'light',
    ];
@endphp
<span class="badge text-bg-{{ $colors[$status] ?? 'secondary' }} @if($status === 'off') border @endif">{{ ucfirst($status) }}</span>
