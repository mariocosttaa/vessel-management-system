<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselRoleAccess;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'surname'    => ['required', 'string', 'max:255'],
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

        // Combine first name and surname into full name
        $fullName = trim($request->first_name . ' ' . $request->surname);

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
        }

        return redirect()->route('login')
            ->with('success', 'Invitation accepted! You can now log in with your email and password.');
    }
}
