<?php

namespace App\Models\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * For models that cover "some products" through a polymorphic targets table (Promotion, Coupon).
 * The model needs an "applies_to_all" column and a targets() relation.
 *
 *   $promotion->covers($product);                         // does it apply to this shoe?
 *   Product::active()->tap(fn ($q) => $promotion->scopeProductsQuery($q));  // all shoes it covers
 */
trait HasTargets
{
    // Cache of targetIds(), so covers() doesn't loop over the targets again for every product.
    private array $targetIdCache = [];

    // What a target can point at => the column on the products table that is compared.
    public static function targetColumns(): array
    {
        return [
            Product::class => 'id',
            Brand::class => 'brand_id',
            Category::class => 'category_id',
            ProductModel::class => 'model_id',
        ];
    }

    // Target type => name of the multi-select in the admin forms (brands[], categories[], models[], products[]).
    public static function targetInputs(): array
    {
        return [
            Brand::class => 'brands',
            Category::class => 'categories',
            ProductModel::class => 'models',
            Product::class => 'products',
        ];
    }

    // The admin form input turned into syncTargets() format. Whole shop = no targets.
    public static function targetsFromInput(array $input): array
    {
        if (!empty($input['applies_to_all'])) {
            return [];
        }

        $ids = [];
        foreach (self::targetInputs() as $class => $key) {
            $ids[$class] = $input[$key] ?? [];
        }

        return $ids;
    }

    // Ids of one target type, e.g. targetIds(Brand::class) = [1, 4].
    public function targetIds(string $class): array
    {
        return $this->targetIdCache[$class] ??= $this->targets
            ->where('targetable_type', $class)
            ->pluck('targetable_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function covers(Product $product): bool
    {
        if ($this->applies_to_all) {
            return true;
        }

        foreach (self::targetColumns() as $class => $column) {
            if (in_array((int) $product->{$column}, $this->targetIds($class), true)) {
                return true;
            }
        }

        return false;
    }

    // Limits a products query to the products this covers (the same rules as covers(), in SQL).
    public function scopeProductsQuery(Builder $query): Builder
    {
        if ($this->applies_to_all) {
            return $query;
        }

        return $query->where(function ($q) {
            foreach (self::targetColumns() as $class => $column) {
                $q->orWhereIn('products.' . $column, $this->targetIds($class) ?: [0]);
            }
        });
    }

    // Replaces all targets. $ids = [Brand::class => [1, 2], Product::class => [7], ...]
    public function syncTargets(array $ids): void
    {
        $this->targets()->forceDelete();

        foreach (self::targetColumns() as $class => $column) {
            foreach (array_unique($ids[$class] ?? []) as $id) {
                $this->targets()->create(['targetable_type' => $class, 'targetable_id' => (int) $id]);
            }
        }

        $this->unsetRelation('targets');
        $this->targetIdCache = [];
    }

    // Short text for the admin lists, e.g. "Whole shop" or "2 brands, 1 category".
    public function targetSummary(): string
    {
        if ($this->applies_to_all) {
            return 'Whole shop';
        }

        $labels = [Product::class => 'product', Brand::class => 'brand', Category::class => 'category', ProductModel::class => 'model'];
        $parts = [];

        foreach ($labels as $class => $label) {
            $count = count($this->targetIds($class));
            if ($count) {
                $parts[] = $count . ' ' . \Illuminate\Support\Str::plural($label, $count);
            }
        }

        return $parts ? implode(', ', $parts) : 'Nothing selected';
    }
}
