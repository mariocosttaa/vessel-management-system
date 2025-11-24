# Member Invitation Patterns

## Overview

The member invitation system allows vessel administrators to invite users to join their vessels. The system handles both new users (who need to create accounts) and existing users (who already have accounts in the system).

## Key Features

1. **Existing User Detection**: Automatically detects if a user with the email already exists
2. **Invitation-Only Flow**: For existing users, only sends invitation (no account creation)
3. **Pending Access**: Creates inactive vessel access entries that activate when invitation is accepted
4. **OAuth Support**: Users can accept invitations via Google/Microsoft OAuth
5. **Email Notifications**: Sends invitation emails with acceptance links

## Invitation Flow

### For New Users

1. Administrator adds crew member with email
2. System creates new user account
3. System creates inactive vessel access entries
4. System sends invitation email
5. User clicks invitation link
6. User sets password and accepts invitation
7. System activates vessel access

### For Existing Users

1. Administrator adds crew member with email
2. System detects existing user by email
3. System checks if user already has access to vessel
4. If no access, system creates inactive vessel access entries
5. System sends invitation email
6. User clicks invitation link
7. User accepts invitation (no password needed if already logged in)
8. System activates vessel access

## Implementation

### CrewMemberController::store()

```php
// app/Http/Controllers/CrewMemberController.php
public function store(StoreCrewMemberRequest $request)
{
    $vesselId = (int) $request->attributes->get('vessel_id');
    $vessel   = Vessel::findOrFail($vesselId);

    $email        = $request->email ? strtolower(trim($request->email)) : null;
    $existingUser = $email ? User::where('email', $email)->first() : null;

    if ($existingUser && ! $createWithoutEmail) {
        // User already exists - just send invitation to join the vessel
        $crewMember = $existingUser;

        // Check if user already has access to this vessel
        $hasVesselAccess = VesselUserRole::where('user_id', $crewMember->id)
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->exists();

        if ($hasVesselAccess) {
            return back()
                ->withInput()
                ->with('error', 'User with email :email already has access to this vessel.', [
                    'email' => $email,
                ]);
        }

        // Set invitation token for existing users
        $inviter = $request->user();
        $invitationToken = Str::random(64);
        $crewMember->update([
            'invitation_token'     => $invitationToken,
            'invitation_sent_at'   => now(),
            'invitation_language'  => $inviter?->language ?? 'en',
        ]);

        // Create vessel user role entry (inactive until invitation is accepted)
        $vesselRoleAccessId = null;
        if ($request->position_id) {
            $position = CrewPosition::find($request->position_id);
            if ($position && $position->vessel_role_access_id) {
                $vesselRoleAccessId = $position->vessel_role_access_id;
            }
        }

        // If no role from position, use default "normal" role
        if (! $vesselRoleAccessId) {
            $normalRole = VesselRoleAccess::where('name', 'normal')->where('is_active', true)->first();
            if ($normalRole) {
                $vesselRoleAccessId = $normalRole->id;
            }
        }

        // Create VesselUserRole entry (inactive until invitation is accepted)
        if ($vesselRoleAccessId) {
            VesselUserRole::updateOrCreate(
                [
                    'vessel_id' => $vesselId,
                    'user_id'   => $crewMember->id,
                ],
                [
                    'vessel_role_access_id' => $vesselRoleAccessId,
                    'is_active'             => false, // Will be activated when invitation is accepted
                ]
            );
        }

        // Also create VesselUser entry for backward compatibility
        VesselUser::updateOrCreate(
            [
                'vessel_id' => $vesselId,
                'user_id'   => $crewMember->id,
            ],
            [
                'role'      => 'viewer',
                'is_active' => false, // Will be activated when invitation is accepted
            ]
        );

        // Send invitation email
        Mail::to($crewMember->email)->queue(
            new CrewMemberInvitationMail($crewMember, $vessel, $invitationToken, null, $inviter)
        );

        return redirect()
            ->route('panel.crew-members.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
            ->with('success', "Invitation sent to ':email'. They will receive an email to accept the invitation and join the vessel.", [
                'email' => $crewMember->email,
            ]);
    } else {
        // Create new user (existing logic)
        // ...
    }
}
```

## Invitation Acceptance

### InvitationController::accept()

When a user accepts an invitation, the system activates their vessel access:

```php
// app/Http/Controllers/Auth/InvitationController.php
public function accept(Request $request, string $token)
{
    $user = User::where('invitation_token', $token)
        ->whereNull('invitation_accepted_at')
        ->first();

    // Update user
    $user->update([
        'name'                   => $fullName,
        'password'               => Hash::make($request->password),
        'invitation_accepted_at' => now(),
        'invitation_token'       => null,
        'login_permitted'        => true,
        'email_verified_at'      => now(),
    ]);

    // Ensure vessel access is active
    $vesselId = $user->vessel_id;
    
    // If no vessel_id, check for pending vessel access (existing user invited to join vessel)
    if (! $vesselId) {
        $pendingVesselRole = VesselUserRole::where('user_id', $user->id)
            ->where('is_active', false)
            ->first();
        
        if ($pendingVesselRole) {
            $vesselId = $pendingVesselRole->vessel_id;
        }
    }

    if ($vesselId) {
        // Activate VesselUser entry
        VesselUser::updateOrCreate(
            [
                'vessel_id' => $vesselId,
                'user_id'   => $user->id,
            ],
            [
                'is_active' => true,
                'role'      => 'viewer',
            ]
        );

        // Activate VesselUserRole entry
        $vesselRoleAccessId = null;
        
        // Get role from pending entry or position
        $pendingVesselRole = VesselUserRole::where('user_id', $user->id)
            ->where('vessel_id', $vesselId)
            ->where('is_active', false)
            ->first();
        
        if ($pendingVesselRole) {
            $vesselRoleAccessId = $pendingVesselRole->vessel_role_access_id;
        }

        // Activate the role
        if ($vesselRoleAccessId) {
            VesselUserRole::updateOrCreate(
                [
                    'vessel_id' => $vesselId,
                    'user_id'   => $user->id,
                ],
                [
                    'vessel_role_access_id' => $vesselRoleAccessId,
                    'is_active'             => true, // Activate vessel access
                ]
            );
        }
    }

    // Log the user in automatically
    Auth::login($user);
    
    return redirect()->route('panel.index')
        ->with('success', 'Invitation accepted! Welcome to the vessel.');
}
```

## OAuth Invitation Acceptance

Users can also accept invitations via Google/Microsoft OAuth:

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

    // Activate vessel access (same logic as regular invitation acceptance)
    $vesselId = $user->vessel_id;
    
    if (! $vesselId) {
        $pendingVesselRole = VesselUserRole::where('user_id', $user->id)
            ->where('is_active', false)
            ->first();
        
        if ($pendingVesselRole) {
            $vesselId = $pendingVesselRole->vessel_id;
        }
    }

    if ($vesselId) {
        // Activate vessel access entries
        // ... (same as regular invitation acceptance)
    }

    Auth::login($user, true);
    
    return redirect()->route('panel.index')
        ->with('success', 'Invitation accepted! Welcome to the vessel.');
}
```

## Key Points

1. **Existing User Detection**: System checks for existing users by email before creating new accounts
2. **Pending Access**: Creates inactive vessel access entries that activate on invitation acceptance
3. **No Duplicate Access**: Prevents creating duplicate vessel access for users who already have access
4. **OAuth Support**: Users can accept invitations via Google/Microsoft OAuth
5. **Email Verification**: Automatically verifies email when invitation is accepted
6. **Automatic Login**: Users are automatically logged in after accepting invitation

## Benefits

- ✅ Prevents duplicate user accounts
- ✅ Allows existing users to join multiple vessels
- ✅ Seamless invitation flow for both new and existing users
- ✅ OAuth support for easier account creation
- ✅ Automatic vessel access activation
- ✅ Clear user feedback messages

