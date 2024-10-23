<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request  $request): JsonResponse
    {
        $credentials = $request->validate([
            "email" => ["required", "string", "email"],
            "password" => ["required", "string"],
        ]);

        if (Auth::attempt($request->only("email", "password"))) {

            $user = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                "access_token" => $token,
                "token_type" => "Bearer",
                "user" => $user,
                "status" => "Login successful",
            ]);
        }

        return response()->json(
            ["message" => "Invalid login credentials"],
            401
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request
            ->user()
            ->currentAccessToken()
            ->delete();
        return response()->json(["message" => "Logout successful"]);
    }
}
