<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// One use of a coupon by one order. Soft-deleted (= released) when the order is cancelled or expires.
class CouponRedemption extends Model
{
    use SoftDeletes;

    protected $fillable = ['coupon_id', 'order_id', 'user_id', 'email', 'discount_amount'];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
        ];
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class)->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }
}
