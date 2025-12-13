<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create a new API token for the user.
     */
    public function createToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'token_name' => 'string|max:255',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if API is enabled for this user
        if (!$user->api_enabled) {
            return response()->json([
                'message' => 'API access is not enabled for this account. Please enable API access in your settings.',
            ], 403);
        }

        // Create token
        $tokenName = $request->token_name ?? 'api-token-' . now()->timestamp;
        $token = $user->createToken($tokenName);

        // Update last used timestamp
        $user->update(['api_last_used_at' => now()]);

        Log::info('API token created', [
            'user_id' => $user->id,
            'token_name' => $tokenName,
        ]);

        return response()->json([
            'message' => 'Token created successfully',
            'token' => $token->plainTextToken,
            'token_name' => $tokenName,
            'abilities' => $token->accessToken->abilities,
        ], 201);
    }

    /**
     * Revoke the current API token.
     */
    public function revokeToken(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        Log::info('API token revoked', [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }
}
