<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponException;
use App\Exceptions\OutOfStockException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductSize;
use App\Models\UserLog;
use App\Services\Cart;
use App\Services\Coupons;
use App\Services\OrderConfirmation;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class CheckoutController extends Controller
{
    // Max 3 placed orders per minute, per user (guests: per IP address).
    public const MAX_ORDERS = 3;
    public const ORDER_DECAY_SECONDS = 60;

    public function __construct(private Cart $cart, private OrderConfirmation $confirmation, private Coupons $coupons)
    {
    }

    public function create()
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Vaša korpa je prazna.');
        }

        $totals = $this->cart->totals($items);

        // After a failed submit the typed code comes back in old(): show its discount again.
        $couponError = null;
        if (old('coupon_code')) {
            try {
                [$coupon, $amount] = $this->coupons->check(old('coupon_code'), $totals, old('email'), auth()->id(), request()->ip());
                $totals = $totals->with($this->coupons->line($coupon, $amount));
            } catch (CouponException $e) {
                $couponError = $e->getMessage();
            }
        }

        return view('pages.checkout.create', [
            'totals' => $totals,
            'showCoupon' => $this->coupons->anyAvailable() || old('coupon_code'),
            'couponError' => $couponError,
            'user' => auth()->user(), // null for guests; used to pre-fill the form
        ]);
    }

    // "Apply" button next to the coupon field (fetch from public/js/checkout-coupon.js). Nothing is stored,
    // it only shows the discount; the code is checked again when the order is placed.
    public function coupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string|max:50',
            'email' => 'nullable|string|max:100',
        ]);

        $totals = $this->cart->totals();

        try {
            [$coupon, $amount] = $this->coupons->check($request->coupon_code, $totals, $request->email, auth()->id(), $request->ip());
        } catch (CouponException $e) {
            return response()->json(['valid' => false, 'message' => $e->getMessage()], 422);
        }

        $totals = $totals->with($this->coupons->line($coupon, $amount));

        return response()->json([
            'valid' => true,
            'message' => 'Kupon ' . $coupon->code . ' je primenjen: -' . Money::format($amount) . '.',
            'code' => $coupon->code,
            'discount' => '-' . Money::format($amount),
            'total' => Money::format($totals->total()),
        ]);
    }

    public function store(CheckoutRequest $request)
    {
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Vaša korpa je prazna.');
        }

        $totals = $this->cart->totals($items);
        $code = $request->validated('coupon_code');

        // First check outside the transaction (with the attempts limit), so a wrong code returns quickly.
        $coupon = null;
        if ($code) {
            try {
                [$coupon] = $this->coupons->check($code, $totals, $request->email, auth()->id(), $request->ip());
            } catch (CouponException $e) {
                return back()->withInput()->withErrors(['coupon_code' => $e->getMessage()]);
            }
        }

        // Order limit. Counted only here, after validation and the coupon check, so fixing a typo in
        // the form isn't blocked. hit() counts and checks in one step, so two submits at the same
        // moment can't both get through. If the order then fails (out of stock...), the count is removed.
        $limitKey = 'place-order:' . (auth()->id() ?? $request->ip());
        if (RateLimiter::hit($limitKey, self::ORDER_DECAY_SECONDS) > self::MAX_ORDERS) {
            $seconds = RateLimiter::availableIn($limitKey);
            UserLog::recordFailure('order.failed', 'Order limit reached: more than ' . self::MAX_ORDERS . ' orders in a minute (' . $request->email . ')');

            return back()->withInput()->with('error', 'Poslali ste nekoliko porudžbina u poslednjem minutu. Sačekajte ' . $seconds . ' s pre sledeće porudžbine.');
        }

        try {
            // Everything inside the transaction is saved together, or nothing is saved.
            $order = DB::transaction(function () use ($request, $items, $totals, $coupon) {
                // lockForUpdate on the coupon: two orders at the same moment can't go over its usage limit.
                // Checked again here, with exact counts.
                if ($coupon) {
                    $coupon = Coupon::with('targets')->lockForUpdate()->findOrFail($coupon->id);
                    $amount = $this->coupons->discountFor($coupon, $totals, $request->email, auth()->id());
                    $totals = $totals->with($this->coupons->line($coupon, $amount));
                }

                $order = Order::create($request->safe()->except('coupon_code') + [
                    'user_id' => auth()->id(), // null for guests
                    'subtotal' => $totals->subtotal(),
                    'discount_total' => $totals->discountTotal(),
                    'coupon_code' => $coupon?->code, // copied, so editing / deleting the coupon doesn't change this order
                    'total' => $totals->total(),
                    'status' => Order::STATUS_UNCONFIRMED, // becomes "new" after the code from the email is entered
                    'ip_address' => $request->ip(),
                ]);

                foreach ($items as $item) {
                    // lockForUpdate: two customers can't buy the last pair at the same moment.
                    $size = ProductSize::where('product_id', $item['product']->id)
                        ->where('size', $item['size'])
                        ->lockForUpdate()
                        ->first();

                    if (!$size || $size->stock < $item['quantity']) {
                        $left = $size?->stock ?? 0;
                        throw new OutOfStockException('Nažalost, od modela ' . $item['product']->name . ' u veličini ' . $item['size'] . ' na stanju je još samo ' . $left . '. Ažurirajte korpu.');
                    }

                    $size->decrement('stock', $item['quantity']);

                    $order->items()->create([
                        'product_id' => $item['product']->id,
                        'size' => $item['size'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'], // copied (with product-level promotions), so later price changes don't affect this order
                    ]);
                }

                // Snapshot of the cart-level promotions and the coupon.
                foreach ($totals->discounts as $discount) {
                    $order->discounts()->create($discount);
                }

                if ($coupon) {
                    $this->coupons->redeem($coupon, $order, $amount);
                }

                return $order;
            });
        } catch (OutOfStockException $e) {
            RateLimiter::clear($limitKey); // nothing was ordered
            UserLog::recordFailure('order.failed', 'Out of stock at checkout: ' . $e->getMessage());
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        } catch (CouponException $e) {
            RateLimiter::clear($limitKey);
            return back()->withInput()->withErrors(['coupon_code' => $e->getMessage()]);
        }

        $this->cart->clear();

        UserLog::record('order.created', 'Order ' . $order->number . ' placed' . ($order->coupon_code ? ' with coupon ' . $order->coupon_code : ''), $order);

        // Mails the code + link to confirm the order. The order confirmation (customer) and
        // the order mail (shop) are sent only after a successful confirmation, see OrderConfirmation::confirm().
        $this->confirmation->sendCode($order);

        // Remember the order so the thank-you page can show it (also works for guests).
        session(['last_order_id' => $order->id]);

        return redirect()->route('checkout.success');
    }

    public function success()
    {
        $order = Order::with(['items.product', 'discounts'])->find(session('last_order_id'));

        if (!$order) {
            return redirect()->route('home');
        }

        return view('pages.checkout.success', compact('order'));
    }
}
