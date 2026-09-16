<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MeController extends Controller
{
    /**
     * GET /api/v1/me/opportunities (auth:sanctum)
     *
     * The signed-in user's OWN submissions, at any status — including
     * ones not yet published. This is deliberately different from the
     * public /opportunities endpoint (published-only): a person needs
     * to see their own pending submission's status, but nobody else
     * should be able to see it before it's published. Scoped strictly
     * to $request->user()->id, not by email — so this can never leak
     * another user's submissions even if they share an email typo.
     */
    public function opportunities(Request $request)
    {
        $opportunities = $request->user()
            ->opportunities()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $opportunities->map(fn ($o) => [
                'id'           => $o->id,
                'title'        => $o->title,
                'category'     => $o->category,
                'status'       => $o->status,
                'country'      => $o->country,
                'overall_score'=> $o->overall_score,
                'created_at'   => $o->created_at->toIso8601String(),
                'published_at' => $o->published_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * PATCH /api/v1/me/password (auth:sanctum)
     * Requires the current password, not just a fresh session — someone
     * with a stolen but still-valid token shouldn't be able to lock the
     * real owner out just by having an active session. Revokes every
     * OTHER token on success (keeps the current one alive so the user
     * making the change isn't immediately logged out).
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        $currentTokenId = $request->user()->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json(['status' => 'Password updated.']);
    }

    /**
     * GET /api/v1/me/enquiries (auth:sanctum)
     * The signed-in user's own "Request Introduction" submissions.
     */
    public function enquiries(Request $request)
    {
        $enquiries = $request->user()
            ->enquiries()
            ->with('opportunity:id,title,category')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $enquiries->map(fn ($e) => [
                'id'          => $e->id,
                'type'        => $e->type,
                'status'      => $e->status,
                'message'     => $e->message,
                'opportunity' => $e->opportunity ? [
                    'id'       => $e->opportunity->id,
                    'title'    => $e->opportunity->title,
                    'category' => $e->opportunity->category,
                ] : null,
                'created_at'  => $e->created_at->toIso8601String(),
            ]),
        ]);
    }
}
