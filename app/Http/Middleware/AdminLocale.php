<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin panel is in English, the shop in Serbian (APP_LOCALE=sr).
 * For /admin requests this switches Laravel to "en": validation messages come from Laravel's own
 * English files, dates ("2 hours ago") are English, and labels that are shared with the shop
 * (order statuses, promotion types...) pick their English text (see Order::statusLabel()).
 */
class AdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('en');
        // Fallback too, otherwise missing English keys (e.g. field names) would come from lang/sr.
        app('translator')->setFallback('en');
        Carbon::setLocale('en');
        // Carbon has its own translator with the shop's fallback ("sr"); without this, weeks came out as "1 nedelju ago".
        Carbon::getTranslator()->setFallbackLocales(['en']);

        return $next($request);
    }
}
