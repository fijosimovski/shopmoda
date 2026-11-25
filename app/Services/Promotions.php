<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Collection;

/**
 * Applies promotions. Registered as a singleton, so the running promotions are loaded once per request.
 *
 * Two kinds:
 *  - catalog (percent / fixed): change the product's price -> price()
 *  - cart-level (nth_item / cart_threshold): depend on the whole cart -> cartDiscounts()
 */
class Promotions
{
    private ?Collection $running = null;

    // Running promotions with their targets, the one with the highest priority first.
    // Includes members-only ones: banners and promo pages show them to guests too, as a reason to register.
    public function running(): Collection
    {
        return $this->running ??= Promotion::running()
            ->with('targets')
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();
    }

    // The running promotions the current visitor actually gets: members-only ones only when logged in.
    // Not cached, so logging in / out during the request is respected.
    public function forCurrentCustomer(): Collection
    {
        $loggedIn = auth()->check();

        return $this->running()->filter(fn (Promotion $p) => $loggedIn || !$p->members_only)->values();
    }

    public function catalog(): Collection
    {
        return $this->forCurrentCustomer()->filter->isCatalog()->values();
    }

    public function cartLevel(): Collection
    {
        return $this->forCurrentCustomer()->filter->isCartLevel()->values();
    }

    /**
     * The price the customer pays for a product and the promotion that made it (null = none).
     *
     * Only one catalog promotion counts per product: the one with the highest priority that covers it.
     *  - stackable:     it is applied on top of the product's own sale price
     *  - not stackable: the customer gets the better of the two (sale price or promotion price)
     *
     * @return array{0: float, 1: ?Promotion}
     */
    public function price(Product $product): array
    {
        $base = $product->price();
        $sale = round($base * (100 - $product->discount_percent) / 100, 2);

        $promotion = $this->catalog()->first(fn (Promotion $p) => $p->covers($product));

        if (!$promotion) {
            return [$sale, null];
        }

        if ($promotion->is_stackable) {
            return [$promotion->applyTo($sale), $promotion];
        }

        $promoPrice = $promotion->applyTo($base);

        return $promoPrice < $sale ? [$promoPrice, $promotion] : [$sale, null];
    }

    // Cart-level promotions a product takes part in, for badges like "2nd pair -20%".
    public function cartPromotionsFor(Product $product): Collection
    {
        return $this->cartLevel()->filter(fn (Promotion $p) => $p->covers($product))->values();
    }

    /**
     * Cart-level discounts for the cart rows (each row: product, quantity, unit_price, subtotal).
     * A non-stackable promotion doesn't combine with other cart-level ones: the highest priority one wins.
     *
     * @return Collection<array{promotion_id: int, coupon_id: null, name: string, amount: float}>
     */
    public function cartDiscounts(Collection $items): Collection
    {
        $applied = collect();
        $left = (float) $items->sum('subtotal'); // the discounts can't be more than the cart is worth

        foreach ($this->cartLevel() as $promotion) {
            $amount = min($left, $this->cartAmount($promotion, $items));

            if ($amount <= 0) {
                continue;
            }

            $combines = $applied->isEmpty()
                || ($promotion->is_stackable && $applied->every(fn ($row) => $row['promotion']->is_stackable));

            if (!$combines) {
                continue;
            }

            $applied->push(['promotion' => $promotion, 'amount' => $amount]);
            $left -= $amount;
        }

        return $applied->map(fn ($row) => [
            'promotion_id' => $row['promotion']->id,
            'coupon_id' => null,
            'name' => $row['promotion']->name . ' (' . $row['promotion']->badge() . ')',
            'amount' => $row['amount'],
        ]);
    }

    private function cartAmount(Promotion $promotion, Collection $items): float
    {
        $qualifying = $items->filter(fn ($item) => $promotion->covers($item['product']));

        if ($qualifying->isEmpty()) {
            return 0;
        }

        return match ($promotion->type) {
            Promotion::TYPE_NTH_ITEM => $this->nthItemAmount($promotion, $qualifying),
            Promotion::TYPE_CART_THRESHOLD => $qualifying->sum('subtotal') >= (float) $promotion->min_cart_total
                ? round(min((float) $promotion->value, $qualifying->sum('subtotal')), 2)
                : 0,
            default => 0,
        };
    }

    /**
     * "Every n-th pair X% off": every qualifying pair is listed by price, most expensive first,
     * and every n-th one (the cheaper one) gets the discount.
     *   120  100  90  80  with n = 2 and 20%  =>  -20 (100) and -16 (80) = -36
     * Discounting the cheaper pair is the standard, otherwise adding a cheap pair would discount an expensive one.
     */
    private function nthItemAmount(Promotion $promotion, Collection $qualifying): float
    {
        $nth = max(2, (int) $promotion->nth);

        $unitPrices = $qualifying
            ->flatMap(fn ($item) => array_fill(0, $item['quantity'], (float) $item['unit_price']))
            ->sortDesc()
            ->values();

        $amount = 0;

        foreach ($unitPrices as $index => $price) {
            if (($index + 1) % $nth === 0) {
                $amount += $price * (float) $promotion->value / 100;
            }
        }

        return round($amount, 2);
    }
}
