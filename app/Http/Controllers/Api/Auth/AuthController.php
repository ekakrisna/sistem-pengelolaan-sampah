<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

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
}
