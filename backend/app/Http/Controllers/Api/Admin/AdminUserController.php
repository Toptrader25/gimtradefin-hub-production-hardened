<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * GET /api/v1/admin/users (admin only)
     */
    public function index()
    {
        $users = User::orderBy('name')->get();

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'role'  => $u->role,
            ]),
        ]);
    }

    /**
     * PATCH /api/v1/admin/users/{id}/role (admin only)
     * A user can't demote themselves out of admin here — prevents an
     * admin accidentally locking themselves (and everyone) out with no
     * remaining admin account able to fix it.
     */
    public function updateRole(Request $request, int $id)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['member', 'reviewer', 'admin'])],
        ]);

        $targetUser = User::findOrFail($id);

        if ($targetUser->id === $request->user()->id && $validated['role'] !== 'admin') {
            return response()->json([
                'message' => 'You cannot remove your own admin role.',
            ], 422);
        }

        $oldRole = $targetUser->role;
        $targetUser->role = $validated['role'];
        $targetUser->save();

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'auditable_type' => User::class,
            'auditable_id'   => $targetUser->id,
            'action'         => 'role_changed',
            'changes'        => ['from' => $oldRole, 'to' => $validated['role']],
        ]);

        return response()->json(['status' => 'updated', 'role' => $validated['role']]);
    }
}
