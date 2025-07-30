<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Request;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login the user and return an access token
     */
    public function login(LoginRequest $request)
    {
        $request->authenticate();

        // $validator = Validator::make($request->all(), [
        //     'email'    => ['required', 'email'],
        //     'password' => ['required'],
        // ]);

        // if ($validator->fails()) {
        //     return $this->errorResponse(
        //         'Error::RequestError::InvalidParameter',
        //         $validator->errors()->first(),
        //         422
        //     );
        // }

        // if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        //     return $this->errorResponse(
        //         'Error::Auth::InvalidCredentials',
        //         'Email atau password salah.',
        //         401
        //     );
        // }

        $user = $request->user();

        // Optional: delete existing tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addDay());

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'user'  => $user,
        ], '');
    }

    /**
     * Logout and revoke token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logout berhasil.');
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        return $this->successResponse([
            'user' => $request->user()
        ]);
    }
}
