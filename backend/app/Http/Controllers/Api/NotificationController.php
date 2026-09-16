<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/v1/me/notifications (auth:sanctum)
     * Scoped strictly to the signed-in user — same principle as
     * MeController: never queryable by anything client-supplied.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->orderByDesc('created_at')->limit(30)->get();

        return response()->json([
            'data'   => $notifications->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'payload'    => $n->payload,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unread_count' => $request->user()->notifications()->whereNull('read_at')->count(),
        ]);
    }

    /**
     * PATCH /api/v1/me/notifications/{id}/read (auth:sanctum)
     */
    public function markRead(Request $request, int $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['status' => 'read']);
    }

    /**
     * PATCH /api/v1/me/notifications/read-all (auth:sanctum)
     */
    public function markAllRead(Request $request)
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['status' => 'all_read']);
    }
}
