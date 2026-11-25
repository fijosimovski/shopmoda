<?php

namespace App\Http\Middleware;

use App\Models\UserLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HoneypotMiddleware
{
    // Name of the hidden field in resources/views/partials/honeypot.blade.php.
    public const FIELD = 'website';

    /**
     * Stops spam bots. The form has a field people can't see (moved off screen, skipped by Tab and
     * screen readers), so only a bot that fills in every field fills it in.
     * Usage in routes:  ->middleware('honeypot')
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A password manager can autofill the hidden field with the user's email (it guesses it is the username).
        // That is a person, not a bot, so it's let through.
        $trap = trim((string) $request->input(self::FIELD));
        $autofilled = $trap !== '' && strcasecmp($trap, trim((string) $request->input('email'))) === 0;

        if ($trap !== '' && ! $autofilled) {
            // recordFailure() stops saving after a number of failures per IP, so a bot flood can't fill the table.
            UserLog::recordFailure('spam.blocked', 'Bot caught by the hidden form field on ' . $request->method() . ' /' . $request->path());

            // Same vague message for every form, so the bot learns nothing.
            return redirect()->back()->with('error', 'Zahtev nije mogao biti poslat. Pokušajte ponovo.');
        }

        return $next($request);
    }
}
