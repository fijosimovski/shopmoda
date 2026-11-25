<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * The shopping cart. It is stored in the session, so guests can use it without logging in.
 *
 * In the session it looks like this:
 *   'cart' => [
 *       '5-42.5' => ['product_id' => 5, 'size' => 42.5, 'quantity' => 1],
 *       '8-40'   => ['product_id' => 8, 'size' => 40,   'quantity' => 2],
 *   ]
 * The key is "productId-size", so the same sneaker in two sizes is two rows.
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    // The raw rows from the session.
    public function raw(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public static function key(int $productId, float $size): string
    {
        return $productId . '-' . (float) $size; // 42.0 -> "5-42", 42.5 -> "5-42.5"
    }

    public function quantityOf(int $productId, float $size): int
    {
        return $this->raw()[self::key($productId, $size)]['quantity'] ?? 0;
    }

    public function add(int $productId, float $size, int $quantity): void
    {
        $cart = $this->raw();
        $key = self::key($productId, $size);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = ['product_id' => $productId, 'size' => (float) $size, 'quantity' => $quantity];
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function update(string $key, int $quantity): void
    {
        $cart = $this->raw();

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $quantity;
            session([self::SESSION_KEY => $cart]);
        }
    }

    public function remove(string $key): void
    {
        $cart = $this->raw();
        unset($cart[$key]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    // Number of pieces in the cart (shown next to the cart icon).
    public function count(): int
    {
        return array_sum(array_column($this->raw(), 'quantity'));
    }

    public function isEmpty(): bool
    {
        return empty($this->raw());
    }

    /**
     * The cart rows with their products loaded from the database.
     * Each item is an array: key, product, size, quantity, stock, unit_price, subtotal.
     * Rows whose product or size no longer exists are removed automatically.
     */
    public function items(): Collection
    {
        $rows = $this->raw();

        if (empty($rows)) {
            return collect();
        }

        $products = Product::active()
            ->with(['brand', 'currentPrice', 'mainImage', 'sizes'])
            ->whereIn('id', array_column($rows, 'product_id'))
            ->get()
            ->keyBy('id');

        $items = collect();

        foreach ($rows as $key => $row) {
            $product = $products->get($row['product_id']);
            $size = $product?->sizes->firstWhere('size', (float) $row['size']);

            if (!$product || !$size) {
                $this->remove($key); // product deleted / hidden, or size removed
                continue;
            }

            $unitPrice = $product->finalPrice();

            $items->push([
                'key' => $key,
                'product' => $product,
                'size' => (float) $row['size'],
                'quantity' => $row['quantity'],
                'stock' => $size->stock,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $row['quantity'],
            ]);
        }

        return $items;
    }

    // Subtotal, cart-level promotion discounts and total (no coupon, that one is added at checkout).
    public function totals(?Collection $items = null): CartTotals
    {
        $items ??= $this->items();

        return new CartTotals($items, app(Promotions::class)->cartDiscounts($items));
    }
}
