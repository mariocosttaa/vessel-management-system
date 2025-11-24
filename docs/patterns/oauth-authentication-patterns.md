# OAuth Authentication Patterns

## Overview

The system supports OAuth authentication via Google and Microsoft, with intelligent handling of existing users during both login and signup flows.

## Key Features

1. **Existing User Detection**: Automatically detects existing users by email
2. **Smart Login Flow**: Allows login for existing users with OAuth accounts
3. **Smart Signup Flow**: Redirects to login if user tries to sign up with existing email
4. **OAuth Linking**: Links OAuth accounts to existing user accounts
5. **Invitation Support**: Users can accept vessel invitations via OAuth

## OAuth Flow

### Login Flow

1. User clicks "Login with Google/Microsoft"
2. System redirects to OAuth provider
3. User authenticates with provider
4. System receives OAuth callback
5. System checks if user exists by provider_id
6. If not found, checks by email
7. If user exists:
   - If they have OAuth account: Allow login
   - If they don't have OAuth account: Link it and allow login
8. If user doesn't exist: Show signup modal

### Signup Flow

1. User clicks "Sign up with Google/Microsoft"
2. System redirects to OAuth provider
3. User authenticates with provider
4. System receives OAuth callback
5. System checks if user exists by email
6. If user exists:
   - Redirect to login with message asking if they want to login instead
7. If user doesn't exist: Create new account

## Implementation

### OAuthController::handleOAuthCallback()

```php
// app/Http/Controllers/Auth/OAuthController.php
private function handleOAuthCallback($socialUser, string $provider): RedirectResponse
{
    $source = session('oauth_source', 'login');
    
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
                // Coming from login page - allow login if they have OAuth account or link it
                if ($existingUser->provider && $existingUser->provider === $provider) {
                    // They have OAuth account, allow login
                    $user = $existingUser;
                } elseif ($existingUser->provider && $existingUser->provider !== $provider) {
                    // They have different OAuth provider - suggest using that
                    return redirect()->route('login')
                        ->with('error', 'An account with this email exists but uses ' . ucfirst($existingUser->provider) . ' for login.');
                } else {
                    // User exists but doesn't have OAuth account - link it and allow login
                    $existingUser->update([
                        'provider'    => $provider,
                        'provider_id' => $socialUser->getId(),
                        'avatar'      => $socialUser->getAvatar(),
                    ]);
                    $user = $existingUser;
                }
            } elseif ($source === 'register') {
                // Coming from signup page - user already exists, ask if they want to login
                return redirect()->route('login')
                    ->with('error', 'An account with this email already exists. Would you like to login instead?')
                    ->with('show_login_modal', true)
                    ->with('oauth_provider', $provider)
                    ->with('oauth_email', $socialUser->getEmail())
                    ->with('oauth_name', $socialUser->getName());
            }
        }
    }

    // Create new user if doesn't exist (only from signup)
    if (! $user) {
        if ($source === 'login') {
            // User tried to login but doesn't have account - show signup modal
            return redirect()->route('login')
                ->with('show_signup_modal', true)
                ->with('oauth_provider', $provider)
                ->with('oauth_email', $socialUser->getEmail())
                ->with('oauth_name', $socialUser->getName());
        }

        // Create new user from signup
        $user = User::create([
            'name'              => $socialUser->getName(),
            'email'             => $socialUser->getEmail(),
            'provider'          => $provider,
            'provider_id'       => $socialUser->getId(),
            'avatar'            => $socialUser->getAvatar(),
            'password'          => bcrypt(uniqid('', true)),
            'email_verified_at' => now(),
        ]);
    } else {
        // Update user info if needed
        $user->update([
            'name'   => $socialUser->getName(),
            'avatar' => $socialUser->getAvatar(),
        ]);
    }

    // Log the user in
    Auth::login($user, true);

    return redirect()->intended(route('panel.index'));
}
```

## Key Scenarios

### Scenario 1: Existing User Logs In with OAuth

**Flow:**
1. User has account with email `user@example.com` (created with password)
2. User clicks "Login with Google"
3. Google account has same email `user@example.com`
4. System detects existing user by email
5. System links Google OAuth to existing account
6. User is logged in

**Result:** ✅ User successfully logs in, OAuth account is linked

### Scenario 2: Existing User Tries to Sign Up with OAuth

**Flow:**
1. User has account with email `user@example.com`
2. User clicks "Sign up with Google"
3. Google account has same email `user@example.com`
4. System detects existing user by email
5. System redirects to login page with message: "An account with this email already exists. Would you like to login instead?"

**Result:** ✅ User is redirected to login, can then login with OAuth

### Scenario 3: New User Signs Up with OAuth

**Flow:**
1. User doesn't have account
2. User clicks "Sign up with Google"
3. System creates new account with OAuth info
4. User is logged in

**Result:** ✅ New account created, user logged in

### Scenario 4: User Tries to Login but Doesn't Have Account

**Flow:**
1. User doesn't have account
2. User clicks "Login with Google"
3. System detects no account exists
4. System redirects to login with signup modal shown

**Result:** ✅ User sees signup modal, can create account

## OAuth Invitation Acceptance

Users can accept vessel invitations via OAuth:

```php
// app/Http/Controllers/Auth/OAuthController.php
private function handleInvitationOAuth($socialUser, string $provider, string $invitationToken): RedirectResponse
{
    $user = User::where('invitation_token', $invitationToken)
        ->whereNull('invitation_accepted_at')
        ->first();

    // Verify email matches
    $userEmail  = strtolower(trim($user->email));
    $oauthEmail = strtolower(trim($socialUser->getEmail()));

    if ($userEmail !== $oauthEmail) {
        return redirect()->route('invitation.accept', ['token' => $invitationToken])
            ->with('error', 'The email address from ' . ucfirst($provider) . ' does not match the invitation email.');
    }

    // Update user with OAuth info and accept invitation
    $user->update([
        'name'                   => $fullName,
        'provider'               => $provider,
        'provider_id'            => $socialUser->getId(),
        'avatar'                 => $socialUser->getAvatar(),
        'password'               => bcrypt(uniqid('', true)),
        'invitation_accepted_at' => now(),
        'invitation_token'       => null,
        'login_permitted'        => true,
        'email_verified_at'      => now(),
    ]);

    // Activate vessel access
    // ... (see member-invitation-patterns.md)

    Auth::login($user, true);
    
    return redirect()->route('panel.index')
        ->with('success', 'Invitation accepted! Welcome to the vessel.');
}
```

## Best Practices

1. ✅ Always check for existing users by email before creating new accounts
2. ✅ Link OAuth accounts to existing accounts when appropriate
3. ✅ Provide clear feedback messages for all scenarios
4. ✅ Verify email matches for invitation acceptance
5. ✅ Handle edge cases gracefully (different OAuth providers, etc.)
6. ✅ Update user info (name, avatar) from OAuth provider
7. ✅ Automatically verify email for OAuth users

## Benefits

- ✅ Seamless login experience for existing users
- ✅ Prevents duplicate accounts
- ✅ Clear user guidance when accounts already exist
- ✅ Easy account creation via OAuth
- ✅ OAuth support for invitation acceptance
- ✅ Automatic email verification for OAuth users

