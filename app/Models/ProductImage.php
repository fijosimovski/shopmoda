<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'path', 'is_main', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Use in Blade: <img src="{{ $image->url() }}">
    // Seeded placeholders are full URLs, uploaded images are files in storage.
    public function url(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        return asset('storage/' . $this->path);
    }
}
