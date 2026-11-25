<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * The money part of a cart: subtotal - discounts = total.
 * $discounts are cart-level promotions and the coupon, each: promotion_id, coupon_id, name, amount.
 */
class CartTotals
{
    public function __construct(
        public readonly Collection $items,
        public readonly Collection $discounts,
    ) {
    }

    public function subtotal(): float
    {
        return round((float) $this->items->sum('subtotal'), 2);
    }

    public function discountTotal(): float
    {
        return round(min($this->subtotal(), (float) $this->discounts->sum('amount')), 2);
    }

    public function total(): float
    {
        return round($this->subtotal() - $this->discountTotal(), 2);
    }

    // A copy with one more discount line (e.g. the coupon).
    public function with(array $discount): self
    {
        return new self($this->items, $this->discounts->concat([$discount]));
    }
}
