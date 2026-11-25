<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'product_id', 'size', 'quantity', 'unit_price'];

    protected function casts(): array
    {
        return [
            'size' => 'float',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // withTrashed() so soft-deleted products still show in old orders.
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function subtotal(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }
}
