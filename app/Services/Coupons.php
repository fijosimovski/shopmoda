<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\UserLog;
use App\Support\Money;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Coupons are entered at checkout (not stored in the cart / session):
 *   1. check()  - "Apply" button at checkout and again when the order is placed
 *   2. redeem() - inside the checkout DB transaction, with the coupon row locked (lockForUpdate),
 *                 so two orders at the same moment can't go over the usage limit
 *   3. release() / restore() - when the order is cancelled / expires, or is reopened by the admin
 */
class Coupons
{
    // Wrong codes allowed per IP before the customer has to wait, so codes can't be guessed.
    public const MAX_ATTEMPTS = 5;
    public const DECAY_SECONDS = 600;

    // The coupon field at checkout is only shown when at least one coupon can be used.
    public function anyAvailable(): bool
    {
        return Coupon::available()->exists();
    }

    /**
     * Finds the coupon and calculates its discount for these totals. Throws CouponException with a message
     * for the customer. Every failed attempt counts towards the limit for this IP.
     *
     * @return array{0: Coupon, 1: float}
     */
    public function check(string $code, CartTotals $totals, ?string $email, ?int $userId, string $ip): array
    {
        $key = 'coupon-attempts:' . $ip;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            UserLog::recordFailure('coupon.blocked', 'Too many wrong coupon codes, last tried: ' . Str::limit($code, 50));
            throw new CouponException('Previše pogrešnih kupon kodova. Pokušajte ponovo za ' . \App\Support\Sr::plural($minutes, 'minut', 'minuta', 'minuta') . '.');
        }

        $coupon = Coupon::findByCode($code);

        // Only unknown / not running codes count as a wrong guess. A real code that fails a condition
        // (e.g. minimum order) doesn't, so a customer changing their cart isn't locked out.
        if (!$coupon || !$this->isLive($coupon)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            throw new CouponException('Ovaj kupon kod nije ispravan.');
        }

        return [$coupon, $this->discountFor($coupon, $totals, $email, $userId)];
    }

    // Switched on and inside its time window.
    public function isLive(Coupon $coupon): bool
    {
        return $coupon->is_active
            && (!$coupon->starts_at || !$coupon->starts_at->isFuture())
            && (!$coupon->ends_at || !$coupon->ends_at->isPast());
    }

    /**
     * The discount this coupon gives on these totals (after cart-level promotions), or a CouponException.
     * Called again inside the checkout transaction with the coupon locked, so the counts are exact.
     */
    public function discountFor(Coupon $coupon, CartTotals $totals, ?string $email, ?int $userId): float
    {
        $email = $email ? Str::lower(trim($email)) : null;

        if (!$this->isLive($coupon)) {
            throw new CouponException('Ovaj kupon kod nije ispravan.');
        }

        // Members-only: the customer has to be logged in (guest checkout can't use it).
        if ($coupon->members_only && !$userId) {
            throw new CouponException('Ovaj kupon važi samo za registrovane kupce. Prijavite se ili napravite nalog da biste ga iskoristili.');
        }

        if ($coupon->usage_limit !== null && $coupon->redemptions()->count() >= $coupon->usage_limit) {
            throw new CouponException('Ovaj kupon je već iskorišćen.');
        }

        // Limits per customer need the email; at the "Apply" step it may not be typed in yet,
        // then they are checked when the order is placed.
        if ($email || $userId) {
            if ($coupon->usage_limit_per_customer !== null
                && $this->customerRedemptions($coupon, $email, $userId) >= $coupon->usage_limit_per_customer) {
                throw new CouponException('Već ste iskoristili ovaj kupon.');
            }

            if ($coupon->first_order_only && $this->hasOrdered($email, $userId)) {
                throw new CouponException('Ovaj kupon važi samo za prvu porudžbinu.');
            }
        }

        if ($coupon->min_order_total !== null && $totals->total() < (float) $coupon->min_order_total) {
            throw new CouponException('Za ovaj kupon porudžbina mora biti najmanje ' . Money::format((float) $coupon->min_order_total) . '.');
        }

        // Items the coupon counts: inside its scope, and full price only if it excludes sale items.
        $base = $totals->items
            ->filter(fn ($item) => $coupon->covers($item['product']))
            ->filter(fn ($item) => !$coupon->exclude_sale_items || $item['unit_price'] >= $item['product']->price())
            ->sum('subtotal');

        if ($base <= 0) {
            throw new CouponException($coupon->exclude_sale_items
                ? 'Ovaj kupon važi samo za proizvode po punoj ceni iz njegove ponude, a takvih nema u vašoj korpi.'
                : 'Ovaj kupon ne važi za proizvode u vašoj korpi.');
        }

        $amount = $coupon->type === Coupon::TYPE_PERCENT
            ? $base * (float) $coupon->value / 100
            : (float) $coupon->value;

        if ($coupon->max_discount !== null) {
            $amount = min($amount, (float) $coupon->max_discount);
        }

        // Never more than the eligible items or the order is worth.
        return round(min($amount, $base, $totals->total()), 2);
    }

    // The discount line added to the totals.
    public function line(Coupon $coupon, float $amount): array
    {
        return [
            'promotion_id' => null,
            'coupon_id' => $coupon->id,
            'name' => 'Kupon ' . $coupon->code,
            'amount' => $amount,
        ];
    }

    // Call inside the checkout transaction, after discountFor() on the locked coupon.
    public function redeem(Coupon $coupon, Order $order, float $amount): void
    {
        $coupon->redemptions()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'email' => Str::lower(trim($order->email)),
            'discount_amount' => $amount,
        ]);
    }

    // The order was cancelled or expired: the use doesn't count any more.
    public function release(Order $order): void
    {
        CouponRedemption::where('order_id', $order->id)->delete();
    }

    // The admin reopened a cancelled order: the use counts again (the admin's call, limits aren't checked).
    public function restore(Order $order): void
    {
        CouponRedemption::onlyTrashed()->where('order_id', $order->id)->restore();
    }

    private function customerRedemptions(Coupon $coupon, ?string $email, ?int $userId): int
    {
        return $coupon->redemptions()
            ->where(function ($q) use ($email, $userId) {
                $q->when($email, fn ($q) => $q->orWhere('email', $email))
                    ->when($userId, fn ($q) => $q->orWhere('user_id', $userId));
            })
            ->count();
    }

    // Any earlier order that wasn't cancelled.
    private function hasOrdered(?string $email, ?int $userId): bool
    {
        return Order::whereNotIn('status', Order::RELEASED_STATUSES)
            ->where(function ($q) use ($email, $userId) {
                $q->when($email, fn ($q) => $q->orWhere('email', $email))
                    ->when($userId, fn ($q) => $q->orWhere('user_id', $userId));
            })
            ->exists();
    }
}
