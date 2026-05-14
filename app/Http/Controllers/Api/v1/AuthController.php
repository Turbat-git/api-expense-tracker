<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login
     *
     * Authenticate a user and return an access token.
     *
     * @group Authentication
     * @unauthenticated
     *
     * @bodyParam email string required User email address. Example: john@example.com
     * @bodyParam password string required User password. Example: password123
     * @bodyParam device_name string Optional device name for token identification. Example: iPhone 15
     *
     * @response 200 {
     *   "access_token": "1|laravel-sanctum-token",
     *   "token_type": "Bearer"
     * }
     *
     * @response 401 {
     *   "message": "Incorrect Password"
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // check password
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect Password'], 401);
        }

        // return token
        $token = $user->createToken($request->given_name ?? 'unknown-name')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout
     *
     * Revoke the current access token.
     *
     * @group Authentication
     *
     * @response 200 {
     *   "message": "Logout Successful"
     * }
     */
    public function logout(Request $request)
    {
        // delete token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout Successful'], 200);
    }

    /**
     * Register
     *
     * Create a new user account and return an access token.
     *
     * @group Authentication
     * @unauthenticated
     *
     * @bodyParam given_name string required First name. Example: John
     * @bodyParam family_name string required Last name. Example: Doe
     * @bodyParam email string required User email address. Example: john@example.com
     * @bodyParam password string required Password (min 8 chars). Example: password123
     * @bodyParam password_confirmation string required Must match password. Example: password123
     *
     * @response 201 {
     *   "access_token": "1|laravel-sanctum-token",
     *   "token_type": "Bearer",
     *   "user": {
     *     "id": 1,
     *     "given_name": "John",
     *     "family_name": "Doe",
     *     "email": "john@example.com"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid."
     * }
     */
    public function register(Request $request)
    {

        $validated = $request->validate([
            'given_name' => 'required|string|max:255',
            'family_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'given_name' => $validated['given_name'],
            'family_name' => $validated['family_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // return token
        $token = $user->createToken($request->given_name ?? 'unknown-name')->plainTextToken;

        $user->assignRole('client');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }
}
