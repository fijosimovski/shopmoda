@extends('layouts.admin')

@section('pageTitle') Coupons @endsection

@section('pageContent')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Coupons</h1>
            <p class="small text-secondary mb-0">Codes customers enter at checkout. The coupon field is only shown while at least one coupon is running.</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-dark"><i class="bi bi-plus-lg"></i> New coupon</a>
    </div>

    <div class="bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Code</th><th>Discount</th><th>Conditions</th><th>Covers</th><th>Used</th><th>Given</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($coupons as $coupon)
                <tr @class(['admin-row-new' => $coupon->created_at?->gt(now()->subDay())])>
                    <td>
                        <span class="fw-semibold font-monospace">{{ $coupon->code }}</span>
                        @if($coupon->isSingleUse())<span class="badge text-bg-dark">single use</span>@endif
                        @if($coupon->description)<div class="small text-secondary">{{ $coupon->description }}</div>@endif
                    </td>
                    <td class="text-nowrap">
                        {{ $coupon->valueLabel() }}
                        @if($coupon->max_discount)<div class="small text-secondary">max @money($coupon->max_discount)</div>@endif
                    </td>
                    <td class="small">
                        @if($coupon->min_order_total)<div>min. @money($coupon->min_order_total)</div>@endif
                        @if($coupon->first_order_only)<div>first order only</div>@endif
                        @if($coupon->members_only)<div>registered customers only</div>@endif
                        @if($coupon->exclude_sale_items)<div>full-price items only</div>@endif
                        @if($coupon->usage_limit_per_customer)<div>{{ $coupon->usage_limit_per_customer }}&times; per customer</div>@endif
                        @if($coupon->ends_at)<div class="text-secondary">until {{ $coupon->ends_at->format('d.m.Y. H:i') }}</div>@endif
                    </td>
                    <td class="small">{{ $coupon->targetSummary() }}</td>
                    <td class="text-nowrap">{{ $coupon->redemptions_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}</td>
                    <td class="text-nowrap">@money($coupon->redemptions_sum_discount_amount ?? 0)</td>
                    <td>@include('partials.discount-status', ['status' => $coupon->status($coupon->redemptions_count)])</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="d-inline"
                              onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary p-3">Nothing here yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
