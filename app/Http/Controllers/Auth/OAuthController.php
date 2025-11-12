<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        try {
            if (!config('services.google.client_id')) {
                return redirect()->route('login')
                    ->with('error', 'Google OAuth is not configured. Please contact the administrator.');
            }

            // Store the source (login or register) in session
            $source = request()->query('source', 'login');
            session(['oauth_source' => $source]);

            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Failed to initiate Google login. Please try again.');
        }
    }

    /**
     * Redirect the user to the Microsoft authentication page.
     */
    public function redirectToMicrosoft(): RedirectResponse
    {
        // Store the source (login or register) in session
        $source = request()->query('source', 'login');
        session(['oauth_source' => $source]);

        $tenant = config('services.microsoft.tenant', 'common');
        return Socialite::driver('microsoft')
            ->setTenantId($tenant)
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')->user();
            return $this->handleOAuthCallback($socialUser, 'google');
        } catch (\Exception $e) {
            $source = session('oauth_source', 'login');
            session()->forget('oauth_source');
            $route = $source === 'register' ? 'register' : 'login';
            return redirect()->route($route)->with('error', 'Google authentication failed. Please try again.');
        }
    }

    /**
     * Obtain the user information from Microsoft.
     */
    public function handleMicrosoftCallback(): RedirectResponse
    {
        try {
            $tenant = config('services.microsoft.tenant', 'common');
            $socialUser = Socialite::driver('microsoft')
                ->setTenantId($tenant)
                ->user();
            return $this->handleOAuthCallback($socialUser, 'microsoft');
        } catch (\Exception $e) {
            $source = session('oauth_source', 'login');
            session()->forget('oauth_source');
            $route = $source === 'register' ? 'register' : 'login';
            return redirect()->route($route)->with('error', 'Microsoft authentication failed. Please try again.');
        }
    }

    /**
     * Handle OAuth callback for both providers.
     */
    private function handleOAuthCallback($socialUser, string $provider): RedirectResponse
    {
        $source = session('oauth_source', 'login');
        session()->forget('oauth_source');

        // Check if user exists by provider_id (user who signed up with this provider)
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        // If user doesn't exist by provider_id, check by email
        if (!$user) {
            $existingUser = User::where('email', $socialUser->getEmail())->first();

            if ($existingUser) {
                // User exists with this email
                if ($source === 'login') {
                    // Coming from login page - check if they have OAuth account
                    if ($existingUser->provider && $existingUser->provider === $provider) {
                        // They have OAuth account, allow login
                        $user = $existingUser;
                    } else {
                        // User exists but doesn't have OAuth account - prevent login
                        return redirect()->route('login')
                            ->with('error', 'An account with this email already exists. Please use your password to login, or sign up with a different email.');
                    }
                } else {
                    // Coming from signup page - link OAuth to existing account
                    $existingUser->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'avatar' => $socialUser->getAvatar(),
                    ]);
                    $user = $existingUser;
                }
            }
        }

        // Create new user if doesn't exist (only from signup)
        if (!$user) {
            if ($source === 'login') {
                // Should not happen, but just in case
                return redirect()->route('login')
                    ->with('error', 'No account found. Please sign up first.');
            }

            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'password' => bcrypt(uniqid('', true)), // Random password for OAuth users
                'email_verified_at' => now(), // OAuth emails are considered verified
            ]);
        } else {
            // Update user info if needed
            $user->update([
                'name' => $socialUser->getName(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        // Log the user in
        Auth::login($user, true);

        return redirect()->intended(route('panel.index'));
    }
}
