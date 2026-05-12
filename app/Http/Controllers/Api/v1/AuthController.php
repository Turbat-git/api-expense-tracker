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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }
}
