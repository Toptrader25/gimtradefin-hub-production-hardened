<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks anything but admin/reviewer roles. This is the real boundary —
 * the frontend also hides the "Admin" nav link from other users, but
 * that's cosmetic. This middleware is what actually stops a signed-in
 * "member" from calling the verification endpoints directly.
 */
class EnsureUserIsReviewer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['admin', 'reviewer'], true)) {
            return response()->json([
                'message' => 'Forbidden. Reviewer or admin role required.',
            ], 403);
        }

        return $next($request);
    }
}
