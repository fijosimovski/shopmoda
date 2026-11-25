<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModel;
use App\Services\Promotions;

class HomeController extends Controller
{
    public function index(Promotions $promotions)
    {
        // Running promotions replace the standard hero with a cover slideshow.
        $covers = $promotions->running();

        // Relations every product card needs, loaded in one go (avoids N+1 queries).
        $with = ['brand', 'currentPrice', 'mainImage'];

        $featured = Product::active()->featured()->with($with)->latest()->take(8)->get();

        $newest = Product::active()->with($with)->latest()->take(8)->get();

        $bestSellers = Product::active()->bestSellers()->with($with)->take(8)->get();

        $categories = Category::withCount(['products' => fn ($q) => $q->active()])->get();

        // Brands slider: only brands that have something to buy.
        $brands = Brand::whereHas('products', fn ($q) => $q->active())
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get();

        // Parent models (e.g. "Air Jordan 1") instead of every colorway.
        $models = ProductModel::whereHas('products', fn ($q) => $q->active())
            ->with('brand')
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get();

        return view('pages.main.home', compact('covers', 'featured', 'newest', 'bestSellers', 'categories', 'brands', 'models'));
    }
}
