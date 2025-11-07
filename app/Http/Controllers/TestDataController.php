<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselUserRole;
use App\Models\VesselRoleAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestDataController extends Controller
{
    /**
     * Display test data overview.
     */
    public function index(Request $request): Response
    {
        // Get all test users
        $testUsers = User::whereIn('email', [
            'paid-admin@test.com',
            'paid-manager@test.com',
            'paid-viewer@test.com',
            'employee-normal@test.com',
            'employee-moderator@test.com',
            'employee-supervisor@test.com',
            'employee-admin@test.com',
            'mixed-admin@test.com',
            'mixed-manager@test.com',
            'multi-vessel@test.com',
            'no-vessels@test.com',
            'inactive-access@test.com',
        ])->with(['roles', 'vesselsThroughRoles.vesselRoleAccess'])->get();

        // Get all test vessels
        $testVessels = Vessel::whereIn('registration_number', [
            'OE-001', 'SB-002', 'DB-003', 'LY-004',
            'TCV-001', 'TPS-002', 'TFB-003', 'TLY-004', 'TRV-005',
            'OV-001', 'OV-002', 'NO-OWNER-001'
        ])->with(['owner', 'usersThroughRoles.user'])->get();

        // Get all role accesses
        $roleAccesses = VesselRoleAccess::where('is_active', true)->get();

        // Get statistics
        $stats = [
            'total_users' => User::count(),
            'test_users' => $testUsers->count(),
            'total_vessels' => Vessel::count(),
            'test_vessels' => $testVessels->count(),
            'total_vessel_users' => VesselUser::count(),
            'active_vessel_users' => VesselUser::where('is_active', true)->count(),
            'inactive_vessel_users' => VesselUser::where('is_active', false)->count(),
            'vessels_with_owners' => Vessel::whereNotNull('owner_id')->count(),
            'vessels_without_owners' => Vessel::whereNull('owner_id')->count(),
        ];

        return Inertia::render('TestData', [
            'testUsers' => $testUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'roles' => $user->roles->pluck('name'),
                    'vessels' => $user->vesselsThroughRoles->map(function ($vesselUser) {
                        return [
                            'vessel_id' => $vesselUser->vessel_id,
                            'vessel_name' => $vesselUser->vessel->name ?? 'Unknown',
                            'role' => $vesselUser->role,
                            'is_active' => $vesselUser->is_active,
                            'permissions' => $vesselUser->vesselRoleAccess->permissions ?? [],
                        ];
                    }),
                ];
            }),
            'testVessels' => $testVessels->map(function ($vessel) {
                return [
                    'id' => $vessel->id,
                    'name' => $vessel->name,
                    'registration_number' => $vessel->registration_number,
                    'type' => $vessel->type,
                    'status' => $vessel->status,
                    'owner' => $vessel->owner ? [
                        'id' => $vessel->owner->id,
                        'name' => $vessel->owner->name,
                        'email' => $vessel->owner->email,
                    ] : null,
                    'users' => $vessel->usersThroughRoles->map(function ($vesselUser) {
                        return [
                            'user_id' => $vesselUser->user_id,
                            'user_name' => $vesselUser->user->name ?? 'Unknown',
                            'user_email' => $vesselUser->user->email ?? 'Unknown',
                            'role' => $vesselUser->role,
                            'is_active' => $vesselUser->is_active,
                        ];
                    }),
                ];
            }),
            'roleAccesses' => $roleAccesses->map(function ($roleAccess) {
                return [
                    'id' => $roleAccess->id,
                    'name' => $roleAccess->name,
                    'display_name' => $roleAccess->display_name,
                    'description' => $roleAccess->description,
                    'permissions' => $roleAccess->permissions,
                    'is_active' => $roleAccess->is_active,
                ];
            }),
            'stats' => $stats,
        ]);
    }

    /**
     * Display permission matrix.
     */
    public function permissions(Request $request): Response
    {
        $roleAccesses = VesselRoleAccess::where('is_active', true)->orderBy('name')->get();

        // Get all unique permissions
        $allPermissions = collect();
        foreach ($roleAccesses as $roleAccess) {
            $allPermissions = $allPermissions->merge($roleAccess->permissions);
        }
        $allPermissions = $allPermissions->unique()->sort()->values();

        // Build permission matrix
        $permissionMatrix = [];
        foreach ($roleAccesses as $roleAccess) {
            $row = [
                'role' => $roleAccess->name,
                'display_name' => $roleAccess->display_name,
                'description' => $roleAccess->description,
                'permissions' => [],
            ];

            foreach ($allPermissions as $permission) {
                $row['permissions'][$permission] = in_array($permission, $roleAccess->permissions);
            }

            $permissionMatrix[] = $row;
        }

        return Inertia::render('TestPermissions', [
            'permissionMatrix' => $permissionMatrix,
            'allPermissions' => $allPermissions,
        ]);
    }
}
