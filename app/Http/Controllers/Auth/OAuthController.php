<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            Log::error('Google OAuth callback error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            $source = session('oauth_source', 'login');
            session()->forget('oauth_source');
            $route = $source === 'register' ? 'register' : 'login';

            $errorMessage = 'Google authentication failed. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }

            return redirect()->route($route)->with('error', $errorMessage);
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
        try {
            $source = session('oauth_source', 'login');
            session()->forget('oauth_source');

            Log::info('OAuth callback received', [
                'provider' => $provider,
                'source' => $source,
                'email' => $socialUser->getEmail(),
                'id' => $socialUser->getId()
            ]);

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
                    } elseif ($source === 'link') {
                        // Coming from profile page - link OAuth to current user's account
                        $currentUser = Auth::user();
                        if ($currentUser && $currentUser->id === $existingUser->id) {
                            // Same user - link the OAuth account
                            $existingUser->update([
                                'provider' => $provider,
                                'provider_id' => $socialUser->getId(),
                                'avatar' => $socialUser->getAvatar(),
                            ]);
                            $user = $existingUser;
                        } else {
                            // Different user - error
                            return redirect()->route('panel.profile.edit')
                                ->with('error', 'This ' . $provider . ' account is already linked to another account.');
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
                } elseif ($source === 'link') {
                    // Linking OAuth to current authenticated user
                    $currentUser = Auth::user();
                    if ($currentUser) {
                        $currentUser->update([
                            'provider' => $provider,
                            'provider_id' => $socialUser->getId(),
                            'avatar' => $socialUser->getAvatar(),
                        ]);
                        $user = $currentUser;
                    } else {
                        return redirect()->route('panel.profile.edit')
                            ->with('error', 'You must be logged in to link an account.');
                    }
                }
            } elseif ($source === 'link') {
                // User already has this OAuth account linked
                $currentUser = Auth::user();
                if ($currentUser && $currentUser->id === $user->id) {
                    // Same user - already linked, just update info
                    $user->update([
                        'name' => $socialUser->getName(),
                        'avatar' => $socialUser->getAvatar(),
                    ]);
                } else {
                    // Different user - error
                    return redirect()->route('panel.profile.edit')
                        ->with('error', 'This ' . $provider . ' account is already linked to another account.');
                }
            }

            // Create new user if doesn't exist (only from signup)
            if (!$user) {
                if ($source === 'login') {
                    // User tried to login but doesn't have account - show signup modal
                    Log::info('User not found, showing signup modal', [
                        'email' => $socialUser->getEmail(),
                        'provider' => $provider
                    ]);

                    return redirect()->route('login')
                        ->with('show_signup_modal', true)
                        ->with('oauth_provider', $provider)
                        ->with('oauth_email', $socialUser->getEmail())
                        ->with('oauth_name', $socialUser->getName());
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

            // Handle different sources
            if ($source === 'link') {
                // Account linking - user is already logged in, just redirect to profile
                Log::info('OAuth account linked', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'provider' => $provider
                ]);

                return redirect()->route('panel.profile.edit')
                    ->with('success', ucfirst($provider) . ' account linked successfully.')
                    ->with('active_tab', 'account');
            }

            // Log the user in (for login/signup flows)
            Auth::login($user, true);

            Log::info('User logged in via OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
                'provider' => $provider
            ]);

            return redirect()->intended(route('panel.index'));
        } catch (\Exception $e) {
            Log::error('OAuth callback processing error: ' . $e->getMessage(), [
                'exception' => $e,
                'provider' => $provider,
                'trace' => $e->getTraceAsString()
            ]);

            $source = session('oauth_source', 'login');
            session()->forget('oauth_source');
            $route = $source === 'register' ? 'register' : 'login';

            $errorMessage = 'Authentication failed. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }

            return redirect()->route($route)->with('error', $errorMessage);
        }
    }
}
