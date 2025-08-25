<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\TransientToken;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login and issue Sanctum token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = $request->user();

        $user->tokens()->delete();

        $token = $user->createToken(
            name: 'auth_token',
            abilities: ['*'],
            expiresAt: now()->addDay()
        );

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'user'  => $user,
        ], 'Login successful');
    }

    /**
     * Logout and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // Avoid trying to delete TransientToken
        if ($token && !($token instanceof TransientToken)) {
            $token->delete();
        }

        return $this->successResponse(null, 'Logout successful.');
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse([
            'user' => $request->user(),
        ]);
    }

    /**
     * Register a new user and return token
     */
    public function register(UserRequest $request)
    {
        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $token = $user->createToken(
            name: 'auth_token',
            abilities: ['*'],
            expiresAt: now()->addDay()
        );

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'user' => $user,
        ], 'Registration successful.');
    }
}
