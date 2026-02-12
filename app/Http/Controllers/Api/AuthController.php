<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login and return JWT token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        // Debug: Check if user exists
        if (!$token = auth('api')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = auth('api')->user();
        if (!$user->is_active) {
            auth('api')->invalidate();
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        return $this->respondWithToken($user, $token);
    }

    /**
     * Register and return JWT token.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($user, $token, 201);
    }

    /**
     * Logout and blacklist current JWT.
     */
    public function logout(Request $request)
    {
        auth('api')->logout();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Refresh JWT and return new token.
     */
    public function refresh(Request $request)
    {
        $token = auth('api')->refresh();

        return $this->respondWithToken(auth('api')->user(), $token);
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('agents'));
    }

    /**
     * Return JSON with user and token.
     */
    protected function respondWithToken(User $user, string $token, int $status = 200)
    {
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token' => $token, // backward compatibility
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60, // seconds
        ], $status);
    }
}
