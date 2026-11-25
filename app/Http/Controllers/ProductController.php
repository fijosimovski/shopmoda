<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\ProductPrice;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Shop page with filters: ?q=air&category=running&brand=nike&model=nike-air-max-90&gender=men&sale=1&sort=price_asc
    public function index(Request $request)
    {
        $query = Product::active()->with(['brand', 'currentPrice', 'mainImage']);

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $request->brand));
        }

        // e.g. ?model=nike-air-jordan-1 shows every colorway of that model
        if ($request->filled('model')) {
            $query->whereHas('productModel', fn ($q) => $q->where('slug', $request->model));
        }

        if (in_array($request->gender, Product::GENDERS)) {
            $query->where('gender', $request->gender);
        }

        if ($request->boolean('sale')) {
            $query->onSale(); // own discount or a running product-level promotion
        }

        // Only products that have at least one size in stock
        if ($request->boolean('in_stock')) {
            $query->whereHas('sizes', fn ($q) => $q->where('stock', '>', 0));
        }

        $sort = $request->get('sort', 'newest');

        switch ($sort) {
            case 'price_asc':
            case 'price_desc':
                // Final price (with discount) of the newest price row, calculated in SQL so we can sort by it.
                $query->addSelect(['final_price' => ProductPrice::selectRaw('price * (100 - products.discount_percent) / 100')
                    ->whereColumn('product_prices.product_id', 'products.id')
                    ->latest('id')
                    ->limit(1)])
                    ->orderBy('final_price', $sort === 'price_asc' ? 'asc' : 'desc');
                break;
            case 'best':
                $query->bestSellers();
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(12)->withQueryString(); // keeps the filters in the page links

        return view('pages.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            // Models grouped by brand for the filter; only the chosen brand's models when a brand is picked.
            'modelGroups' => ProductModel::with('brand')
                ->whereHas('products', fn ($q) => $q->active())
                ->when($request->filled('brand'), fn ($q) => $q->whereHas('brand', fn ($b) => $b->where('slug', $request->brand)))
                ->orderBy('name')
                ->get()
                ->sortBy('brand.name')
                ->groupBy('brand.name'),
            'sort' => $sort,
        ]);
    }

    public function show(Product $product)
    {
        // Hidden products can't be opened in the shop.
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'category', 'currentPrice', 'sizes', 'images']);

        $related = Product::active()
            ->with(['brand', 'currentPrice', 'mainImage'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('pages.products.show', compact('product', 'related'));
    }
}
