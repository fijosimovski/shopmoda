<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionTarget extends Model
{
    use SoftDeletes;

    protected $fillable = ['promotion_id', 'targetable_type', 'targetable_id'];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    // The Product, Brand, Category or ProductModel this row points at.
    public function targetable()
    {
        return $this->morphTo()->withTrashed();
    }
}
