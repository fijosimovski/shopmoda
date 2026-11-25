<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Defaults for the SEO tags in resources/views/fixed/head.blade.php.
 * A page can still set its own with @section('canonical'), @section('robots'), @section('ogImage').
 */
class Seo
{
    // Pages that are private, personal or have no value in Google: "noindex, follow".
    private const NOINDEX_ROUTES = [
        'cart.*', 'checkout.*', 'orders.*', 'account.*', 'contact',
        'login', 'register', 'password.*', 'admin.*',
    ];

    // Filters of the sneakers list that make a real landing page ("Nike", "Running", "Women", "Sale").
    // Everything else (sort, search, in stock) is a variation of the same page and is left out of the canonical URL.
    private const INDEXABLE_FILTERS = ['category', 'brand', 'model', 'gender', 'sale'];

    // Filters that make the page a duplicate or a search result: not indexed at all.
    private const NOINDEX_FILTERS = ['q', 'sort', 'in_stock'];

    /**
     * The one "official" URL of the current page, so Google doesn't count ?sort=..., ?utm_source=... etc. as more pages.
     *   /products?brand=nike&sort=price_asc&page=2  =>  /products?brand=nike&page=2
     *   /products/nike-air-max-90?ref=instagram     =>  /products/nike-air-max-90
     */
    public static function canonical(?Request $request = null): string
    {
        $request ??= request();
        $url = $request->url(); // without the query string

        $keep = [];
        if ($request->routeIs('products.index')) {
            foreach (self::INDEXABLE_FILTERS as $name) {
                if ($request->filled($name)) {
                    $keep[$name] = $request->query($name);
                }
            }
        }

        // Page 2, 3... of a list is its own page (different products), page 1 is the URL without ?page.
        if ($request->routeIs('products.index', 'promotions.show') && (int) $request->query('page') > 1) {
            $keep['page'] = (int) $request->query('page');
        }

        $keep = array_filter($keep, fn ($value) => is_scalar($value) && $value !== '');

        return $keep ? $url . '?' . http_build_query($keep) : $url;
    }

    public static function robots(?Request $request = null): string
    {
        $request ??= request();

        if ($request->routeIs(...self::NOINDEX_ROUTES)) {
            return 'noindex, follow';
        }

        foreach (self::NOINDEX_FILTERS as $name) {
            if ($request->filled($name)) {
                return 'noindex, follow';
            }
        }

        return 'index, follow';
    }

    // Text for a <meta name="description">: one line, no HTML, max ~155 characters.
    public static function description(?string $text, int $limit = 155): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return \Illuminate\Support\Str::limit($text, $limit, '…');
    }
}
