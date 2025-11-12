<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CrewPosition;
use App\Models\User;
use App\Models\VesselRoleAccess;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
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
            if (! config('services.google.client_id')) {
                return redirect()->route('login')
                    ->with('error', 'Google OAuth is not configured. Please contact the administrator.');
            }

            // Store the source (login, register, or invitation) in session
            $source = request()->query('source', 'login');
            session(['oauth_source' => $source]);

            // Store invitation token if present
            $invitationToken = request()->query('invitation_token');
            if ($invitationToken) {
                session(['oauth_invitation_token' => $invitationToken]);
            }

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
        // Store the source (login, register, or invitation) in session
        $source = request()->query('source', 'login');
        session(['oauth_source' => $source]);

        // Store invitation token if present
        $invitationToken = request()->query('invitation_token');
        if ($invitationToken) {
            session(['oauth_invitation_token' => $invitationToken]);
        }

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
                'trace'     => $e->getTraceAsString(),
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
            $tenant     = config('services.microsoft.tenant', 'common');
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
            $source          = session('oauth_source', 'login');
            $invitationToken = session('oauth_invitation_token');
            session()->forget('oauth_source');
            session()->forget('oauth_invitation_token');

            Log::info('OAuth callback received', [
                'provider'             => $provider,
                'source'               => $source,
                'email'                => $socialUser->getEmail(),
                'id'                   => $socialUser->getId(),
                'has_invitation_token' => ! empty($invitationToken),
            ]);

            // Handle invitation acceptance via OAuth
            if ($source === 'invitation' && $invitationToken) {
                return $this->handleInvitationOAuth($socialUser, $provider, $invitationToken);
            }

            // Check if user exists by provider_id (user who signed up with this provider)
            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            // If user doesn't exist by provider_id, check by email
            if (! $user) {
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
                                'provider'    => $provider,
                                'provider_id' => $socialUser->getId(),
                                'avatar'      => $socialUser->getAvatar(),
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
                            'provider'    => $provider,
                            'provider_id' => $socialUser->getId(),
                            'avatar'      => $socialUser->getAvatar(),
                        ]);
                        $user = $existingUser;
                    }
                } elseif ($source === 'link') {
                    // Linking OAuth to current authenticated user
                    $currentUser = Auth::user();
                    if ($currentUser) {
                        $currentUser->update([
                            'provider'    => $provider,
                            'provider_id' => $socialUser->getId(),
                            'avatar'      => $socialUser->getAvatar(),
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
                        'name'   => $socialUser->getName(),
                        'avatar' => $socialUser->getAvatar(),
                    ]);
                } else {
                    // Different user - error
                    return redirect()->route('panel.profile.edit')
                        ->with('error', 'This ' . $provider . ' account is already linked to another account.');
                }
            }

            // Create new user if doesn't exist (only from signup)
            if (! $user) {
                if ($source === 'login') {
                    // User tried to login but doesn't have account - show signup modal
                    Log::info('User not found, showing signup modal', [
                        'email'    => $socialUser->getEmail(),
                        'provider' => $provider,
                    ]);

                    return redirect()->route('login')
                        ->with('show_signup_modal', true)
                        ->with('oauth_provider', $provider)
                        ->with('oauth_email', $socialUser->getEmail())
                        ->with('oauth_name', $socialUser->getName());
                }

                $user = User::create([
                    'name'              => $socialUser->getName(),
                    'email'             => $socialUser->getEmail(),
                    'provider'          => $provider,
                    'provider_id'       => $socialUser->getId(),
                    'avatar'            => $socialUser->getAvatar(),
                    'password'          => bcrypt(uniqid('', true)), // Random password for OAuth users
                    'email_verified_at' => now(),                    // OAuth emails are considered verified
                ]);
            } else {
                // Update user info if needed
                $user->update([
                    'name'   => $socialUser->getName(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            }

            // Handle different sources
            if ($source === 'link') {
                // Account linking - user is already logged in, just redirect to profile
                Log::info('OAuth account linked', [
                    'user_id'  => $user->id,
                    'email'    => $user->email,
                    'provider' => $provider,
                ]);

                return redirect()->route('panel.profile.edit')
                    ->with('success', ucfirst($provider) . ' account linked successfully.')
                    ->with('active_tab', 'account');
            }

            // Log the user in (for login/signup flows)
            Auth::login($user, true);

            Log::info('User logged in via OAuth', [
                'user_id'  => $user->id,
                'email'    => $user->email,
                'provider' => $provider,
            ]);

            return redirect()->intended(route('panel.index'));
        } catch (\Exception $e) {
            Log::error('OAuth callback processing error: ' . $e->getMessage(), [
                'exception' => $e,
                'provider'  => $provider,
                'trace'     => $e->getTraceAsString(),
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

    /**
     * Handle OAuth callback for invitation acceptance.
     */
    private function handleInvitationOAuth($socialUser, string $provider, string $invitationToken): RedirectResponse
    {
        try {
            // Find user by invitation token
            $user = User::where('invitation_token', $invitationToken)
                ->whereNull('invitation_accepted_at')
                ->first();

            if (! $user) {
                Log::warning('Invitation OAuth: Invalid invitation token', [
                    'token'    => $invitationToken,
                    'provider' => $provider,
                    'email'    => $socialUser->getEmail(),
                ]);
                return redirect()->route('login')
                    ->with('error', 'Invalid or expired invitation link.');
            }

            // Verify email matches
            $userEmail  = strtolower(trim($user->email));
            $oauthEmail = strtolower(trim($socialUser->getEmail()));

            if ($userEmail !== $oauthEmail) {
                Log::warning('Invitation OAuth: Email mismatch', [
                    'invitation_email' => $user->email,
                    'oauth_email'      => $socialUser->getEmail(),
                    'provider'         => $provider,
                    'normalized_user'  => $userEmail,
                    'normalized_oauth' => $oauthEmail,
                ]);
                return redirect()->route('invitation.accept', ['token' => $invitationToken])
                    ->with('error', 'The email address from ' . ucfirst($provider) . ' (' . $socialUser->getEmail() . ') does not match the invitation email (' . $user->email . ').');
            }

            Log::info('Invitation OAuth: Email verified', [
                'user_id'  => $user->id,
                'email'    => $user->email,
                'provider' => $provider,
            ]);

            // Check if invitation is older than 7 days
            if ($user->invitation_sent_at && $user->invitation_sent_at->copy()->addDays(7)->isPast()) {
                return redirect()->route('login')
                    ->with('error', 'This invitation link has expired. Please contact the vessel administrator.');
            }

            // Split OAuth name into first name and surname
            $nameParts = explode(' ', $socialUser->getName(), 2);
            $firstName = $nameParts[0] ?? '';
            $surname   = $nameParts[1] ?? '';
            $fullName  = trim($socialUser->getName());

            Log::info('Invitation OAuth: Updating user', [
                'user_id'  => $user->id,
                'name'     => $fullName,
                'provider' => $provider,
            ]);

            // Update user with OAuth info and accept invitation
            $user->update([
                'name'                   => $fullName,
                'provider'               => $provider,
                'provider_id'            => $socialUser->getId(),
                'avatar'                 => $socialUser->getAvatar(),
                'password'               => bcrypt(uniqid('', true)), // Random password for OAuth users
                'invitation_accepted_at' => now(),
                'invitation_token'       => null,
                'login_permitted'        => true,
                'email_verified_at'      => now(), // OAuth emails are considered verified
            ]);

            Log::info('Invitation OAuth: User updated successfully', [
                'user_id' => $user->id,
            ]);

            // Ensure vessel access is active
            if ($user->vessel_id) {
                Log::info('Invitation OAuth: Setting up vessel access', [
                    'user_id'   => $user->id,
                    'vessel_id' => $user->vessel_id,
                ]);

                // Create/update VesselUser for backward compatibility
                VesselUser::updateOrCreate(
                    [
                        'vessel_id' => $user->vessel_id,
                        'user_id'   => $user->id,
                    ],
                    [
                        'is_active' => true,
                        'role'      => 'viewer', // Default role, actual role is in VesselUserRole
                    ]
                );

                // Create/update VesselUserRole based on position's vessel_role_access_id
                $vesselRoleAccessId = null;

                // Get role from position if user has a position assigned
                if ($user->position_id) {
                    $position = CrewPosition::find($user->position_id);
                    if ($position && $position->vessel_role_access_id) {
                        $vesselRoleAccessId = $position->vessel_role_access_id;
                        Log::info('Invitation OAuth: Found role from position', [
                            'position_id'           => $user->position_id,
                            'vessel_role_access_id' => $vesselRoleAccessId,
                        ]);
                    }
                }

                // If no role from position, use default "normal" role
                if (! $vesselRoleAccessId) {
                    $normalRole = VesselRoleAccess::where('name', 'normal')->where('is_active', true)->first();
                    if ($normalRole) {
                        $vesselRoleAccessId = $normalRole->id;
                        Log::info('Invitation OAuth: Using default normal role', [
                            'vessel_role_access_id' => $vesselRoleAccessId,
                        ]);
                    }
                }

                // Create VesselUserRole if we have a role access ID
                if ($vesselRoleAccessId) {
                    VesselUserRole::updateOrCreate(
                        [
                            'vessel_id' => $user->vessel_id,
                            'user_id'   => $user->id,
                        ],
                        [
                            'vessel_role_access_id' => $vesselRoleAccessId,
                            'is_active'             => true,
                        ]
                    );
                    Log::info('Invitation OAuth: VesselUserRole created', [
                        'vessel_id'             => $user->vessel_id,
                        'vessel_role_access_id' => $vesselRoleAccessId,
                    ]);
                } else {
                    Log::warning('Invitation OAuth: No vessel role access ID found', [
                        'user_id'   => $user->id,
                        'vessel_id' => $user->vessel_id,
                    ]);
                }
            } else {
                Log::warning('Invitation OAuth: User has no vessel_id', [
                    'user_id' => $user->id,
                ]);
            }

            // Log the user in automatically
            Auth::login($user, true);

            // Regenerate session after login
            $session = request()->session();
            $session->regenerate();

            Log::info('Invitation accepted via OAuth', [
                'user_id'  => $user->id,
                'email'    => $user->email,
                'provider' => $provider,
            ]);

            return redirect()->route('panel.index')
                ->with('success', 'Invitation accepted! Welcome to the vessel.');
        } catch (\Exception $e) {
            Log::error('Invitation OAuth error: ' . $e->getMessage(), [
                'exception' => $e,
                'provider'  => $provider,
                'token'     => $invitationToken,
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->route('invitation.accept', ['token' => $invitationToken])
                ->with('error', 'Failed to accept invitation via ' . ucfirst($provider) . '. Please try again.');
        }
    }
}
