<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     * Rate-limited (see routes/api.php) — throttle:5,1 on both register
     * and login to blunt brute-force / account-enumeration attempts.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'member',
        ]);

        $token = $user->createToken('hub-frontend')->plainTextToken;

        return response()->json([
            'user'  => $this->transform($user),
            'token' => $token,
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     * Deliberately returns the same generic error for "no such user" and
     * "wrong password" — distinguishing them lets an attacker enumerate
     * valid emails.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('hub-frontend')->plainTextToken;

        return response()->json([
            'user'  => $this->transform($user),
            'token' => $token,
        ]);
    }

    /**
     * POST /api/v1/auth/logout (auth:sanctum)
     * Revokes only the token used for this request, not all of the
     * user's sessions/devices.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => 'logged_out']);
    }

    /**
     * GET /api/v1/auth/me (auth:sanctum)
     */
    public function me(Request $request)
    {
        return response()->json($this->transform($request->user()));
    }

    /**
     * POST /api/v1/auth/forgot-password
     * Rate-limited. Always returns the same success message whether or
     * not the email exists — same "don't leak which accounts are real"
     * principle as login(). The actual email only sends if the account
     * is real; the API response can't be used to probe that.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'status' => 'If that email exists, a password reset link has been sent.',
        ]);
    }

    /**
     * POST /api/v1/auth/reset-password
     * On success, revokes every existing Sanctum token for the user —
     * if the account was compromised, this also kills any attacker's
     * active session, not just the legitimate user's old one.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['status' => 'Password has been reset. Please sign in again.']);
    }

    private function transform(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];
    }
}
