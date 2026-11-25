<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Snapshot of a cart-level promotion or a coupon applied to an order.
class OrderDiscount extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'promotion_id', 'coupon_id', 'name', 'amount'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class)->withTrashed();
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class)->withTrashed();
    }
}
