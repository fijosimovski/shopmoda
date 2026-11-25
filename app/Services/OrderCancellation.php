<?php

namespace App\Services;

use App\Exceptions\OutOfStockException;
use App\Models\Order;
use App\Models\ProductSize;

/**
 * What happens to stock and coupons when an order is cancelled or reopened.
 * Call these inside a DB transaction (Admin > Orders status change, orders:expire command).
 */
class OrderCancellation
{
    public function __construct(private Coupons $coupons)
    {
    }

    // Sneakers go back in stock and the coupon use is released.
    public function cancel(Order $order): void
    {
        $this->changeStock($order, +1);
        $this->coupons->release($order);
    }

    // Takes the sneakers out of stock again (if there is enough) and counts the coupon use again.
    public function reopen(Order $order): void
    {
        $this->changeStock($order, -1);
        $this->coupons->restore($order);
    }

    // $direction +1 = return to stock, -1 = take from stock
    private function changeStock(Order $order, int $direction): void
    {
        foreach ($order->items as $item) {
            $size = ProductSize::withTrashed()
                ->where('product_id', $item->product_id)
                ->where('size', $item->size)
                ->lockForUpdate()
                ->first();

            if (!$size) {
                continue; // size no longer exists at all
            }

            if ($direction < 0 && $size->stock < $item->quantity) {
                throw new OutOfStockException('Nema dovoljno na stanju da bi se porudžbina ponovo otvorila (veličina ' . $item->size . ').');
            }

            $size->increment('stock', $item->quantity * $direction);
        }
    }
}
