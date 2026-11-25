@extends('layouts.admin')

@section('pageTitle') {{ $coupon->exists ? 'Edit coupon' : 'New coupon' }} @endsection

@section('pageContent')
    <a href="{{ route('admin.coupons.index') }}" class="small text-decoration-none">&larr; Coupons</a>
    <h1 class="h3 mt-2 mb-4">{{ $coupon->exists ? 'Edit: ' . $coupon->code : 'New coupon' }}</h1>

    @include('partials.errors')

    <form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
          data-validate data-type-form novalidate>
        @csrf
        @if($coupon->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-xl-7">
                <!-- Code and discount -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">Code and discount</h2>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="code">Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control text-uppercase font-monospace" id="code" name="code" value="{{ old('code', $coupon->code) }}"
                                       placeholder="empty = random code" data-label="Code" data-rules="min:3|max:50|coupon">
                                <button type="button" class="btn btn-outline-secondary" data-generate-code title="Generate a random code"><i class="bi bi-shuffle"></i></button>
                            </div>
                            <div class="form-text">Not case-sensitive for customers.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="type">Type</label>
                            <select class="form-select" id="type" name="type" data-type-select>
                                @foreach(\App\Models\Coupon::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $coupon->type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="value">
                                <span data-show-for="percent">Discount (%)</span>
                                <span data-show-for="fixed">Amount off (RSD)</span>
                            </label>
                            <input type="number" class="form-control" id="value" name="value" step="0.01" value="{{ old('value', $coupon->value !== null ? (float) $coupon->value : '') }}" data-label="Discount" data-rules="required|numeric|min:1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="min_order_total">Min. order (RSD)</label>
                            <input type="number" class="form-control" id="min_order_total" name="min_order_total" step="0.01" value="{{ old('min_order_total', $coupon->min_order_total !== null ? (float) $coupon->min_order_total : '') }}" placeholder="none" data-label="Min. order" data-rules="numeric|min:1">
                        </div>
                        <div class="col-md-4" data-show-for="percent">
                            <label class="form-label" for="max_discount">Max. discount (RSD)</label>
                            <input type="number" class="form-control" id="max_discount" name="max_discount" step="0.01" value="{{ old('max_discount', $coupon->max_discount !== null ? (float) $coupon->max_discount : '') }}" placeholder="no cap" data-label="Max. discount" data-rules="numeric|min:1">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Note for admins (optional)</label>
                            <input type="text" class="form-control" id="description" name="description" value="{{ old('description', $coupon->description) }}" placeholder="e.g. Instagram giveaway" data-rules="max:255">
                        </div>
                    </div>
                </div>

                @include('pages.admin.partials.targets', ['item' => $coupon])

                <!-- Full-price items only -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="exclude_sale_items" name="exclude_sale_items" value="1" @checked(old('exclude_sale_items', $coupon->exclude_sale_items))>
                        <label class="form-check-label" for="exclude_sale_items">Full-price items only</label>
                    </div>
                    <div class="form-text">Items already discounted (own sale or a price promotion) don't count towards the coupon. Stops double discounts on sale items.</div>
                </div>
            </div>

            <div class="col-xl-5">
                <!-- Limits -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">Limits</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="single_use" name="single_use" value="1" data-single-use
                            @checked(old('single_use', $coupon->isSingleUse()))>
                        <label class="form-check-label" for="single_use">Single use (one order only, then it stops working)</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="usage_limit">Uses in total</label>
                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="unlimited" data-label="Uses in total" data-rules="integer|min:1">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="usage_limit_per_customer">Uses per customer</label>
                            <input type="number" class="form-control" id="usage_limit_per_customer" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer', $coupon->usage_limit_per_customer) }}" placeholder="unlimited" data-label="Uses per customer" data-rules="integer|min:1|max:1000">
                        </div>
                    </div>
                    <div class="form-text">Customers are counted by email, so guests count too. Cancelled and expired orders don't count.</div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="first_order_only" name="first_order_only" value="1" @checked(old('first_order_only', $coupon->first_order_only))>
                        <label class="form-check-label" for="first_order_only">First order only</label>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="members_only" name="members_only" value="1" @checked(old('members_only', $coupon->members_only))>
                        <label class="form-check-label" for="members_only">Registered customers only</label>
                    </div>
                    <div class="form-text">On: the customer has to be logged in to use the code. Guests are asked to log in or register.</div>
                </div>

                <!-- When -->
                <div class="card card-body border-0 shadow-sm mb-4">
                    <h2 class="h6 fw-bold mb-3">When</h2>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="starts_at">Starts (optional)</label>
                            <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="ends_at">Ends (optional)</label>
                            <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $coupon->is_active))>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>

                <button class="btn btn-dark w-100 mb-4">{{ $coupon->exists ? 'Save' : 'Create' }}</button>

                @if($redemptions->isNotEmpty())
                    <!-- Last uses -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body pb-0"><h2 class="h6 fw-bold">Last uses</h2></div>
                        <table class="table table-sm align-middle mb-0 small">
                            <tbody>
                            @foreach($redemptions as $redemption)
                                <tr @class(['text-secondary text-decoration-line-through' => $redemption->trashed()])>
                                    <td class="ps-3"><a href="{{ route('admin.orders.show', $redemption->order_id) }}">#{{ $redemption->order_id }}</a></td>
                                    <td>{{ $redemption->email }}</td>
                                    <td class="text-nowrap">-@money($redemption->discount_amount)</td>
                                    <td class="text-nowrap pe-3">{{ $redemption->created_at->format('d.m.Y.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="card-body pt-2 small text-secondary">Crossed out = released (order cancelled or expired).</div>
                    </div>
                @endif
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-discount-form.js') }}"></script>
@endpush
