<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class VesselSelectorController extends Controller
{
    /**
     * Display the vessel selector page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $vessels = $user->vesselsThroughRoles()
            ->with(['owner', 'crewMembers', 'transactions'])
            ->get()
            ->map(function ($vessel) use ($user) {
                // Get vessel-specific permissions
                $canEdit = $user->canEditVessel($vessel->id);
                $canDelete = $user->canDeleteVessel($vessel->id);
                $canManageUsers = $user->canManageVesselUsers($vessel->id);

                // Get role access info
                $roleAccess = $user->getVesselRoleAccess($vessel->id);

                return [
                    'id' => $vessel->id,
                    'name' => $vessel->name,
                    'registration_number' => $vessel->registration_number,
                    'vessel_type' => $vessel->vessel_type,
                    'status' => $vessel->status,
                    'status_label' => $vessel->status_label,
                    'user_role' => $user->getRoleForVessel($vessel->id),
                    'role_access' => $roleAccess ? [
                        'name' => $roleAccess->name,
                        'display_name' => $roleAccess->display_name,
                    ] : null,
                    'permissions' => [
                        'can_edit' => $canEdit,
                        'can_delete' => $canDelete,
                        'can_manage_users' => $canManageUsers,
                    ],
                    'crew_count' => $vessel->crewMembers()->count(),
                    'transaction_count' => $vessel->transactions()->count(),
                ];
            });

        return Inertia::render('Index', [
            'vessels' => $vessels,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'permissions' => [
                'can_create_vessels' => $user->canCreateVessels(),
            ],
        ]);
    }

    /**
     * Select a vessel and redirect to dashboard.
     */
    public function select(Request $request)
    {
        $request->validate([
            'vessel_id' => ['required', 'integer', 'exists:vessels,id'],
        ]);

        $user = $request->user();
        $vesselId = $request->vessel_id;

        // Verify user has access to this vessel
        if (!$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        return redirect()->route('panel.dashboard', ['vessel' => $vesselId]);
    }
}
