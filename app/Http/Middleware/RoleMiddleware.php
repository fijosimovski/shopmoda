<?php

namespace App\Http\Middleware;

use App\Models\UserLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Lets the request through only if the logged-in user has one of the given roles.
     * Usage in routes:  ->middleware('role:admin')  or  ->middleware('role:admin,editor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasRole(...$roles)) {
            UserLog::recordFailure('access.denied', 'Tried to open ' . $request->method() . ' /' . $request->path() . ' (needs ' . implode(' or ', $roles) . ')');
            abort(403, 'Nemate pristup ovoj stranici.');
        }

        return $next($request);
    }
}
