<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Google_Client;

class SocialAuthController extends Controller
{
    /**
     * Handle Google Sign-In from mobile app
     * Mobile app sends Google ID token, we verify and create/login user
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            // Verify Google ID token
            $client = new Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Google token',
                ], 401);
            }

            // Extract user data from token
            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? 'User';
            $avatar = $payload['picture'] ?? null;

            // Find or create user
            $user = User::where('provider', 'google')
                ->where('provider_id', $googleId)
                ->first();

            if (!$user) {
                // Check if user with this email already exists
                $existingUser = User::where('email', $email)->first();

                if ($existingUser) {
                    // Link Google account to existing user
                    $existingUser->update([
                        'provider' => 'google',
                        'provider_id' => $googleId,
                        'avatar' => $avatar,
                        'email_verified_at' => now(),
                    ]);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'provider' => 'google',
                        'provider_id' => $googleId,
                        'avatar' => $avatar,
                        'password' => Hash::make(Str::random(24)),
                        'email_verified_at' => now(),
                        'api_enabled' => true, // Enable API for mobile users
                    ]);

                    Log::info('New user registered via Google (mobile)', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }
            } else {
                // Update avatar if changed
                if ($avatar && $user->avatar !== $avatar) {
                    $user->update(['avatar' => $avatar]);
                }

                // Ensure API is enabled
                if (!$user->api_enabled) {
                    $user->update(['api_enabled' => true]);
                }
            }

            // Create API token
            $tokenName = 'mobile-app-google-' . now()->timestamp;
            $token = $user->createToken($tokenName);

            // Update last used timestamp
            $user->update(['api_last_used_at' => now()]);

            Log::info('User logged in via Google (mobile)', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Google login error (mobile)', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to login with Google. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle Facebook Login from mobile app
     * Similar to Google but for Facebook
     */
    public function facebookLogin(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Verify Facebook access token with Facebook Graph API
            $response = \Http::get('https://graph.facebook.com/me', [
                'fields' => 'id,name,email,picture.type(large)',
                'access_token' => $request->access_token,
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Facebook token',
                ], 401);
            }

            $facebookData = $response->json();
            $facebookId = $facebookData['id'];
            $email = $facebookData['email'] ?? 'facebook_' . $facebookId . '@kinvoice.ng';
            $name = $facebookData['name'] ?? 'User';
            $avatar = $facebookData['picture']['data']['url'] ?? null;

            // Find or create user
            $user = User::where('provider', 'facebook')
                ->where('provider_id', $facebookId)
                ->first();

            if (!$user) {
                // Check if user with this email already exists
                $existingUser = User::where('email', $email)->first();

                if ($existingUser) {
                    // Link Facebook account to existing user
                    $existingUser->update([
                        'provider' => 'facebook',
                        'provider_id' => $facebookId,
                        'avatar' => $avatar,
                        'email_verified_at' => now(),
                    ]);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'provider' => 'facebook',
                        'provider_id' => $facebookId,
                        'avatar' => $avatar,
                        'password' => Hash::make(Str::random(24)),
                        'email_verified_at' => now(),
                        'api_enabled' => true,
                    ]);

                    Log::info('New user registered via Facebook (mobile)', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }
            } else {
                // Ensure API is enabled
                if (!$user->api_enabled) {
                    $user->update(['api_enabled' => true]);
                }
            }

            // Create API token
            $tokenName = 'mobile-app-facebook-' . now()->timestamp;
            $token = $user->createToken($tokenName);

            // Update last used timestamp
            $user->update(['api_last_used_at' => now()]);

            Log::info('User logged in via Facebook (mobile)', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token->plainTextToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Facebook login error (mobile)', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to login with Facebook. Please try again.',
            ], 500);
        }
    }
}
