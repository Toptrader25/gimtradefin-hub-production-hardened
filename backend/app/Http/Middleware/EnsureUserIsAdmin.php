<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stricter than EnsureUserIsReviewer — role='admin' only, not 'reviewer'.
 * User/role management is more sensitive than content review: a
 * reviewer approving trade leads shouldn't also be able to grant
 * themselves or anyone else admin rights.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin role required.'], 403);
        }

        return $next($request);
    }
}
