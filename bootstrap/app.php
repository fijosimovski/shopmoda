<?php

use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\HoneypotMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrackPageViews;
use App\Models\UserLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Short names used in routes: ->middleware('role:admin'), ->middleware('honeypot')
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'honeypot' => HoneypotMiddleware::class,
            'admin.locale' => AdminLocale::class,
        ]);

        // Every request is saved for admin > Visits. Global (not only "web"), so requests to pages
        // that don't exist (404s from scanners) are saved too.
        $middleware->append(TrackPageViews::class);

        // 'auth' sends guests to the login page, 'guest' sends logged-in users to the homepage.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Saved in User logs as failures. Returning nothing keeps Laravel's normal error page.
        // A route "throttle" limit was hit (e.g. guessing coupon or order confirmation codes).
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            UserLog::recordFailure('request.throttled', 'Too many requests to ' . $request->method() . ' /' . $request->path());
        });
        // A "signed" link (order confirmation email) that was changed or has expired.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            UserLog::recordFailure('link.invalid', 'Changed or expired link: /' . $request->path());
        });
    })->create();
