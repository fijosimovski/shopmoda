<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponTarget extends Model
{
    use SoftDeletes;

    protected $fillable = ['coupon_id', 'targetable_type', 'targetable_id'];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    // The Product, Brand, Category or ProductModel this row points at.
    public function targetable()
    {
        return $this->morphTo()->withTrashed();
    }
}
