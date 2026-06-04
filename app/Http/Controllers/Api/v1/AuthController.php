<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Knuckles\Scribe\Attributes\Group;

#[Group("Authentication", "APIs for Authentication")]
class AuthController extends Controller
{
    /**
     * Login
     *
     * Authenticate a user and return an access token.
     *
     * @unauthenticated
     *
     * @bodyParam email string required User email address. Example: john@example.com
     * @bodyParam password string required User password. Example: password123
     * @bodyParam device_name string nullable Optional device name for token identification. Example: iPhone 15
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful.",
     *   "data": {
     *     "access_token": "1|laravel-sanctum-token",
     *     "token_type": "Bearer"
     *   },
     *   "response_code": 200
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid Credentials",
     *   "errors": {
     *     "detail": "Please enter a valid email and password."
     *   },
     *   "response_code": 401
     * }
     *
     * Reference for API Practice; https://restfulapi.net/rest-api-best-practices/
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
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid Credentials',
                    'errors' => [
                        'detail' => 'Please enter a valid email and password.',
                    ],
                    'response_code'=>401
                ], 401);
        }

        // return token
        $token = $user->createToken($request->device_name ?? 'unknown-name')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' =>
                [
                'access_token' => $token,
                'token_type' => 'Bearer',
                ],
            'response_code'=>200,
        ], 200);
    }

    /**
     * Logout
     *
     * Revoke the current access token.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Logout Successful",
     *   "data": null,
     *   "response_code": 200
     * }
     */
    public function logout(Request $request)
    {
        // delete token
        $request->user()->currentAccessToken()->delete();

        return response()->json(
            [
                'success'=>true,
                'message' => 'Logout Successful',
                'data' => null,
                'response_code'=>200,
            ],200);
    }

    /**
     * Register
     *
     * Create a new user account and return an access token.
     *
     * @unauthenticated
     *
     * @bodyParam given_name string required First name. Example: John
     * @bodyParam family_name string required Last name. Example: Doe
     * @bodyParam email string required User email address. Example: john@example.com
     * @bodyParam password string required Password (min 8 chars). Example: password123
     * @bodyParam password_confirmation string required Must match password. Example: password123
     * @bodyParam device_name string nullable Optional device name for token identification. Example: iPhone 15
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Registration Successful",
     *   "data": {
     *     "access_token": "1|laravel-sanctum-token",
     *     "token_type": "Bearer",
     *     "user": {
     *       "id": 1,
     *       "given_name": "John",
     *       "family_name": "Doe",
     *       "email": "john@example.com"
     *     }
     *   },
     *   "response_code": 201
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "The given data was invalid.",
     *   "data": null,
     *   "response_code": 422
     * }
     */
    public function register(Request $request)
    {

        $validated = $request->validate([
            'given_name' => 'required|string|max:64',
            'family_name' => 'required|string|max:64',
            'email' => 'required|string|email|max:64|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'given_name' => $validated['given_name'],
            'family_name' => $validated['family_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // return token
        $token = $user->createToken($request->device_name ?? 'unknown-name')->plainTextToken;

        $user->assignRole('client');

        $user_return = [
            'id' => $user->id,
            'given_name' => $user->given_name,
            'family_name' => $user->family_name,
            'email' => $user->email,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Registration Successful',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user_return,
            ],
            'response_code'=>201,
        ], 201);
    }
}
