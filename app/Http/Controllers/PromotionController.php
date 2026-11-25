<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Promotion;
use App\Services\Promotions;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    // /promo/black-friday: banner, countdown, how it works, and every product the promotion covers.
    public function show(Request $request, Promotion $promotion, Promotions $promotions)
    {
        // Switched off in the admin panel = the page doesn't exist.
        abort_unless($promotion->is_active, 404);

        $promotion->load('targets');

        $products = null;
        if (!$promotion->hasEnded()) {
            $sort = $request->get('sort', 'newest');

            $products = $promotion->scopeProductsQuery(Product::active())
                ->with(['brand', 'currentPrice', 'mainImage'])
                ->when($sort === 'best', fn ($q) => $q->bestSellers(), fn ($q) => $q->latest())
                ->paginate(12)
                ->withQueryString();
        }

        // Shown when this one has ended: other promotions that are still on.
        $otherPromotions = $promotions->running()->where('id', '!=', $promotion->id)->values();

        return view('pages.promotions.show', [
            'promotion' => $promotion,
            'products' => $products,
            'otherPromotions' => $otherPromotions,
            'sort' => $sort ?? 'newest',
        ]);
    }
}
