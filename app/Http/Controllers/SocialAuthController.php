<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect to OAuth provider
     */
    public function redirect($provider)
    {
        // Validate provider
        if (!in_array($provider, ['facebook', 'google'])) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth callback
     */
    public function callback($provider)
    {
        // Validate provider
        if (!in_array($provider, ['facebook', 'google'])) {
            abort(404);
        }

        try {
            // Get user info from provider
            $socialUser = Socialite::driver($provider)->user();

            // Find or create user
            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if (!$user) {
                // Check if user with this email already exists
                $existingUser = User::where('email', $socialUser->getEmail())->first();

                if ($existingUser) {
                    // Link social account to existing user
                    $existingUser->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'avatar' => $socialUser->getAvatar(),
                    ]);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'avatar' => $socialUser->getAvatar(),
                        'password' => Hash::make(Str::random(24)), // Random password
                        'email_verified_at' => now(), // Social accounts are pre-verified
                    ]);
                }
            }

            // Log the user in
            Auth::login($user, true);

            // Redirect to home page
            return redirect()->intended('/');

        } catch (\Exception $e) {
            // Handle error
            return redirect('/app/login')->with('error', 'Unable to login using ' . ucfirst($provider) . '. Please try again.');
        }
    }
}
