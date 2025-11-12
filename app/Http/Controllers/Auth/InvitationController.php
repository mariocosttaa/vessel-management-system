<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CrewPosition;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselRoleAccess;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Show the invitation acceptance form.
     */
    public function show(string $token): Response | RedirectResponse
    {
        $user = User::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->first();

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired invitation link.');
        }

        // Check if invitation is older than 7 days
        if ($user->invitation_sent_at && $user->invitation_sent_at->copy()->addDays(7)->isPast()) {
            return redirect()->route('login')
                ->with('error', 'This invitation link has expired. Please contact the vessel administrator.');
        }

        $vessel   = $user->vessel;
        $roleName = null;

        if ($vessel) {
            $vesselUserRole = VesselUserRole::where('user_id', $user->id)
                ->where('vessel_id', $vessel->id)
                ->where('is_active', true)
                ->with('vesselRoleAccess')
                ->first();

            if ($vesselUserRole && $vesselUserRole->vesselRoleAccess) {
                $roleName = $vesselUserRole->vesselRoleAccess->display_name;
            }
        }

        // Split name into first name and surname if it contains a space
        $nameParts = explode(' ', $user->name, 2);
        $firstName = $nameParts[0] ?? '';
        $surname   = $nameParts[1] ?? '';

        return Inertia::render('auth/AcceptInvitation', [
            'token'    => $token,
            'user'     => [
                'name'       => $user->name,
                'first_name' => $firstName,
                'surname'    => $surname,
                'email'      => $user->email,
            ],
            'vessel'   => $vessel ? [
                'id'   => $vessel->id,
                'name' => $vessel->name,
            ] : null,
            'roleName' => $roleName,
        ]);
    }

    /**
     * Accept the invitation and set password.
     */
    public function accept(Request $request, string $token)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'surname'    => ['nullable', 'string', 'max:255'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('invitation_token', $token)
            ->whereNull('invitation_accepted_at')
            ->first();

        if (! $user) {
            return back()
                ->withInput()
                ->with('error', 'Invalid or expired invitation link.');
        }

        // Check if invitation is older than 7 days
        if ($user->invitation_sent_at && $user->invitation_sent_at->copy()->addDays(7)->isPast()) {
            return back()
                ->withInput()
                ->with('error', 'This invitation link has expired. Please contact the vessel administrator.');
        }

        // Combine first name and surname into full name (surname is optional)
        $fullName = trim($request->first_name . ($request->surname ? ' ' . $request->surname : ''));

        // Update user
        $user->update([
            'name'                   => $fullName,
            'password'               => Hash::make($request->password),
            'invitation_accepted_at' => now(),
            'invitation_token'       => null,
            'login_permitted'        => true,
            'email_verified_at'      => now(), // Auto-verify email when accepting invitation
        ]);

        // Ensure vessel access is active
        if ($user->vessel_id) {
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
                }
            }

            // If no role from position, use default "normal" role
            if (! $vesselRoleAccessId) {
                $normalRole = VesselRoleAccess::where('name', 'normal')->where('is_active', true)->first();
                if ($normalRole) {
                    $vesselRoleAccessId = $normalRole->id;
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
            }
        }

        // Log the user in automatically
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('panel.index')
            ->with('success', 'Invitation accepted! Welcome to the vessel.');
    }
}
