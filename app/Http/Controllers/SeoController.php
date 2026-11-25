<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\Promotion;
use Illuminate\Support\Facades\Cache;

/**
 * /robots.txt and /sitemap.xml for search engines.
 * Both are routes (not files in /public) so the address of the sitemap always uses APP_URL.
 */
class SeoController extends Controller
{
    // How long the sitemap is kept in the cache (it reads every product).
    private const SITEMAP_MINUTES = 60;

    public function robots()
    {
        $lines = ['User-agent: *'];

        // Anything but production (local, staging): keep search engines out completely.
        if (!app()->isProduction()) {
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/admin', '/cart', '/checkout', '/account', '/orders', '/contact', '/login', '/register',
                      '/forgot-password', '/reset-password', '/*?*q=', '/*?*sort=', '/*?*in_stock='] as $path) {
                $lines[] = 'Disallow: ' . $path;
            }
            $lines[] = '';
            $lines[] = 'Sitemap: ' . route('sitemap');
        }

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', now()->addMinutes(self::SITEMAP_MINUTES), function () {
            return view('seo.sitemap', ['urls' => $this->urls()])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Every page worth finding in Google: [loc, lastmod (or null), images[]].
     */
    private function urls(): array
    {
        $latestProduct = Product::active()->max('updated_at');
        $urls = [
            ['loc' => route('home'), 'lastmod' => $latestProduct, 'images' => []],
            ['loc' => route('products.index'), 'lastmod' => $latestProduct, 'images' => []],
            ['loc' => route('products.index', ['gender' => 'men']), 'lastmod' => null, 'images' => []],
            ['loc' => route('products.index', ['gender' => 'women']), 'lastmod' => null, 'images' => []],
            ['loc' => route('products.index', ['sale' => 1]), 'lastmod' => null, 'images' => []],
            ['loc' => route('how-to-order'), 'lastmod' => null, 'images' => []],
        ];

        // Category, brand and model pages: only when they have something to show.
        $withProducts = fn ($q) => $q->whereHas('products', fn ($p) => $p->active());

        foreach (Category::query()->tap($withProducts)->orderBy('name')->get() as $category) {
            $urls[] = ['loc' => route('products.index', ['category' => $category->slug]), 'lastmod' => null, 'images' => []];
        }
        foreach (Brand::query()->tap($withProducts)->orderBy('name')->get() as $brand) {
            $urls[] = ['loc' => route('products.index', ['brand' => $brand->slug]), 'lastmod' => null, 'images' => []];
        }
        foreach (ProductModel::query()->tap($withProducts)->orderBy('name')->get() as $model) {
            $urls[] = ['loc' => route('products.index', ['model' => $model->slug]), 'lastmod' => null, 'images' => []];
        }

        foreach (Promotion::running()->orderBy('ends_at')->get() as $promotion) {
            $urls[] = ['loc' => route('promotions.show', $promotion), 'lastmod' => $promotion->updated_at, 'images' => []];
        }

        foreach (Product::active()->with('images')->latest('updated_at')->get() as $product) {
            $urls[] = [
                'loc' => route('products.show', $product),
                'lastmod' => $product->updated_at,
                'images' => $product->images->map(fn ($image) => $image->url())->take(5)->all(),
            ];
        }

        return $urls;
    }
}
