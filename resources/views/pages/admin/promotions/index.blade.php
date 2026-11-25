@extends('layouts.admin')

@section('pageTitle') Promotions @endsection

@section('pageContent')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Promotions</h1>
            <p class="small text-secondary mb-0">Applied automatically. Running ones replace the homepage hero with a cover slideshow.</p>
        </div>
        <a href="{{ route('admin.promotions.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> New promotion</a>
    </div>

    <div class="bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th></th><th>Name</th><th>Discount</th><th>Covers</th><th>When</th><th>Priority</th><th>Status</th><th>Orders</th><th></th></tr></thead>
            <tbody>
            @forelse($promotions as $promotion)
                <tr @class(['admin-row-new' => $promotion->created_at?->gt(now()->subDay())])>
                    <td>
                        @if($promotion->imageUrl())
                            <img src="{{ $promotion->imageUrl() }}" class="rounded admin-thumb-wide" alt="{{ $promotion->name }}">
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $promotion->name }}</div>
                        <a href="{{ route('promotions.show', $promotion) }}" class="small text-secondary" target="_blank">/promo/{{ $promotion->slug }} <i class="bi bi-box-arrow-up-right"></i></a>
                    </td>
                    <td>
                        <span class="badge text-bg-warning">{{ $promotion->badge() }}</span>
                        <div class="small text-secondary">{{ $promotion->isCatalog() ? 'price' : 'cart' }}{{ $promotion->is_stackable ? ', stackable' : '' }}{{ $promotion->members_only ? ', registered only' : '' }}</div>
                    </td>
                    <td class="small">{{ $promotion->targetSummary() }}</td>
                    <td class="small text-nowrap">{{ $promotion->starts_at->format('d.m.Y. H:i') }}<br>{{ $promotion->ends_at->format('d.m.Y. H:i') }}</td>
                    <td>{{ $promotion->priority }}</td>
                    <td>@include('partials.discount-status', ['status' => $promotion->status()])</td>
                    {{-- Cart-level promotions are saved on orders (order_discounts); price promotions are inside the item prices. --}}
                    <td>{{ $promotion->isCartLevel() ? $promotion->order_discounts_count : '-' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.promotions.edit', $promotion) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($promotion->name) }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-secondary p-3">Nothing here yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
