<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Saves every request to the site into page_views (admin > Visits).
 * Runs for all requests, also for pages that don't exist, so scanners probing for
 * /wp-login.php or /.env show up too. Admins browsing the site are not saved.
 * The row is written in terminate(), after the response is sent, so visitors don't wait for it.
 */
class TrackPageViews
{
    // Query string keys that are never saved (signed links, passwords...).
    private const SECRET_KEYS = ['signature', 'token', '_token', 'password', 'code'];

    // cart/drawer: loaded in the background when the cart drawer opens, not a page someone visits.
    private const SKIP_PATHS = ['up', 'cart/drawer'];

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|curl|wget|python|scrapy|httpclient|okhttp|go-http|java\/|node-fetch|axios|guzzle|headless|phantom|facebookexternalhit|preview|monitor|uptime|lighthouse/i';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (in_array($request->path(), self::SKIP_PATHS, true) || $request->user()?->hasRole('admin')) {
                return;
            }

            $userAgent = (string) $request->userAgent();
            $product = $request->route()?->parameter('product');

            PageView::create([
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
                'visitor_hash' => hash('sha256', $request->ip() . '|' . $userAgent),
                'method' => $request->method(),
                'path' => Str::limit($request->path(), 250, ''),
                'query' => $this->query($request),
                'route_name' => $request->route()?->getName(),
                'product_id' => $product instanceof Product ? $product->id : null,
                'status' => $response->getStatusCode(),
                'duration_ms' => defined('LARAVEL_START') ? (int) round((microtime(true) - LARAVEL_START) * 1000) : null,
                'referrer' => Str::limit((string) $request->headers->get('referer'), 500, '') ?: null,
                'user_agent' => Str::limit($userAgent, 500, '') ?: null,
                'device' => self::device($userAgent),
            ]);
        } catch (Throwable $e) {
            // Tracking must never break the site (e.g. table missing before migrate).
            report($e);
        }
    }

    // desktop, mobile, tablet or bot, guessed from the browser's user agent.
    public static function device(string $userAgent): string
    {
        return match (true) {
            $userAgent === '' || (bool) preg_match(self::BOT_PATTERN, $userAgent) => 'bot',
            (bool) preg_match('/ipad|tablet|android(?!.*mobile)/i', $userAgent) => 'tablet',
            (bool) preg_match('/mobi|iphone|ipod|android/i', $userAgent) => 'mobile',
            default => 'desktop',
        };
    }

    private function query(Request $request): ?string
    {
        $params = $request->query();
        if (!is_array($params) || !$params) {
            return null;
        }

        return Str::limit(http_build_query(array_diff_key($params, array_flip(self::SECRET_KEYS))), 500, '') ?: null;
    }
}
