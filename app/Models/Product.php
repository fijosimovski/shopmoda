<?php

namespace App\Models;

use App\Services\Promotions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Product extends Model
{
    use SoftDeletes;

    public const GENDERS = ['men', 'women', 'unisex'];

    // Shown in the shop and the admin panel (the keys stay English in the database and URLs: ?gender=men).
    public const GENDER_LABELS = [
        'men' => 'Muškarci',
        'women' => 'Žene',
        'unisex' => 'Unisex',
    ];

    protected $fillable = [
        'brand_id',
        'model_id',
        'category_id',
        'name',
        'slug',
        'description',
        'gender',
        'discount_percent',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ---------- Relationships ----------

    // withTrashed() so a product still shows its brand / category if that one was soft-deleted.
    public function brand()
    {
        return $this->belongsTo(Brand::class)->withTrashed();
    }

    // The parent model, e.g. "Air Jordan 1" for "Air Jordan 1 Chicago".
    public function productModel()
    {
        return $this->belongsTo(ProductModel::class, 'model_id')->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    // All prices, newest first (price history).
    public function prices()
    {
        return $this->hasMany(ProductPrice::class)->latest('id');
    }

    // Only the newest price row = the current price.
    public function currentPrice()
    {
        return $this->hasOne(ProductPrice::class)->latestOfMany();
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class)->orderBy('size');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ---------- Scopes (reusable query parts) ----------
    // Usage: Product::active()->featured()->get();

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    // On sale = its own discount, or covered by a running product-level promotion.
    public function scopeOnSale(Builder $query): void
    {
        $promotions = app(Promotions::class)->catalog();

        $query->where(function ($q) use ($promotions) {
            $q->where('discount_percent', '>', 0);

            foreach ($promotions as $promotion) {
                $q->orWhere(fn ($p) => $promotion->scopeProductsQuery($p));
            }
        });
    }

    // Best sellers = products with the most pieces sold (unconfirmed, cancelled and expired orders don't count).
    public function scopeBestSellers(Builder $query): void
    {
        $query->withSum(['orderItems as sold_count' => function ($q) {
            $q->whereHas('order', fn ($o) => $o->whereNotIn('status', [Order::STATUS_UNCONFIRMED, ...Order::RELEASED_STATUSES]));
        }], 'quantity')->orderByDesc('sold_count');
    }

    // ---------- Helpers ----------

    // "men" => "Muškarci"
    public static function genderLabel(?string $gender): string
    {
        if (app()->isLocale('en')) {
            return ucfirst((string) $gender); // admin panel
        }

        return self::GENDER_LABELS[$gender] ?? ucfirst((string) $gender);
    }

    // Regular price (before discount).
    public function price(): float
    {
        return (float) ($this->currentPrice?->price ?? 0);
    }

    // Price the customer pays: the product's own sale discount and/or a running promotion (see Promotions::price()).
    public function finalPrice(): float
    {
        return app(Promotions::class)->price($this)[0];
    }

    // The product-level promotion that sets the final price, or null.
    public function activePromotion(): ?Promotion
    {
        return app(Promotions::class)->price($this)[1];
    }

    // Cart-level promotions this product takes part in ("2nd pair -20%"), shown as badges.
    public function cartPromotions(): Collection
    {
        return app(Promotions::class)->cartPromotionsFor($this);
    }

    public function hasDiscount(): bool
    {
        return $this->finalPrice() < $this->price();
    }

    // How much cheaper the final price is, in %, for the "-20%" badge.
    public function discountPercentTotal(): int
    {
        $price = $this->price();

        return $price > 0 ? (int) round((1 - $this->finalPrice() / $price) * 100) : 0;
    }

    // Change the price by adding a new row, so the old price stays in history.
    public function setPrice(float $price): void
    {
        if ($this->price() !== $price) {
            $this->prices()->create(['price' => $price]);
        }
    }
}
