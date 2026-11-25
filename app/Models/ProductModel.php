<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// The parent of products, e.g. "Air Jordan 1" with the products
// "Air Jordan 1 Chicago", "Air Jordan 1 Bred"... (colorways).
// Called ProductModel because "Model" is already Eloquent's base class.
class ProductModel extends Model
{
    use HasImage, SoftDeletes;

    protected $table = 'models';

    protected $fillable = ['brand_id', 'name', 'slug', 'image'];

    // withTrashed() so a model still shows its brand if that one was soft-deleted.
    public function brand()
    {
        return $this->belongsTo(Brand::class)->withTrashed();
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'model_id');
    }
}
