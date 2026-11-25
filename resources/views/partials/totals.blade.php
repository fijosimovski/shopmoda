{{--
    Subtotal, discount lines and total. Works for a cart (CartTotals) and a saved order:
    @include('partials.totals', ['subtotal' => $totals->subtotal(), 'discounts' => $totals->discounts, 'total' => $totals->total()])
    @include('partials.totals', ['subtotal' => $order->subtotal, 'discounts' => $order->discounts, 'total' => $order->total, 'totalLabel' => 'Ukupno (plaćanje pouzećem)'])
    Each discount has a name and an amount (array or OrderDiscount model).
    public/js/checkout-coupon.js adds/removes the coupon line, so keep the data- attributes and the totals-line classes.
--}}
<div class="totals" data-totals>
    @if(($showSubtotal ?? false) || count($discounts))
        <div class="totals-line">
            <span>Međuzbir</span>
            <span>@money($subtotal)</span>
        </div>
    @endif
    @foreach($discounts as $discount)
        <div class="totals-line totals-discount" @if(data_get($discount, 'coupon_id')) data-coupon-line @endif>
            <span>{{ data_get($discount, 'name') }}</span>
            <span class="text-nowrap ms-2">-@money(data_get($discount, 'amount'))</span>
        </div>
    @endforeach
    <div class="totals-total">
        <span class="label-caps text-body">{{ $totalLabel ?? 'Ukupno' }}</span>
        <span data-total>@money($total)</span>
    </div>
</div>
