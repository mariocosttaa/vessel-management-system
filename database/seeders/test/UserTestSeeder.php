<?php

namespace Database\Seeders\Test;

use App\Models\User;
use App\Models\Role;
use App\Models\Vessel;
use App\Models\VesselUserRole;
use App\Models\VesselRoleAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test users with all permission combinations...');

        // Get roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator with full access']
        );
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            ['description' => 'Manager with elevated permissions']
        );
        $viewerRole = Role::firstOrCreate(
            ['name' => 'viewer'],
            ['description' => 'Viewer with read-only permissions']
        );

        // Ensure vessel role access definitions exist
        $this->ensureVesselRoleAccesses();

        // Get vessel role accesses
        $normalAccess = VesselRoleAccess::where('name', 'normal')->first();
        $moderatorAccess = VesselRoleAccess::where('name', 'moderator')->first();
        $supervisorAccess = VesselRoleAccess::where('name', 'supervisor')->first();
        $administratorAccess = VesselRoleAccess::where('name', 'administrator')->first();

        // Create test vessels
        $vessels = $this->createTestVessels();

        // 1. PAID SYSTEM USERS (can create vessels)
        $this->createPaidSystemUsers($adminRole, $managerRole, $viewerRole, $vessels);

        // 2. EMPLOYEE OF VESSEL USERS (cannot create vessels)
        $this->createEmployeeUsers($normalAccess, $moderatorAccess, $supervisorAccess, $administratorAccess, $vessels);

        // 3. MIXED PERMISSION USERS (different roles for different vessels)
        $this->createMixedPermissionUsers($adminRole, $managerRole, $viewerRole, $normalAccess, $moderatorAccess, $supervisorAccess, $administratorAccess, $vessels);

        $this->command->info('Test users created successfully!');
        $this->command->info('Total users created: ' . User::count());
    }

    private function ensureVesselRoleAccesses(): void
    {
        $definitions = [
            'normal' => [
                'display_name' => 'Normal User',
                'description' => 'Basic read-only access to vessel information',
                'permissions' => ['view_vessel'],
            ],
            'moderator' => [
                'display_name' => 'Moderator',
                'description' => 'Can view and edit basic vessel information',
                'permissions' => ['view_vessel', 'edit_vessel_basic'],
            ],
            'supervisor' => [
                'display_name' => 'Supervisor',
                'description' => 'Can view, edit vessel information and manage crew',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'edit_vessel_advanced', 'manage_crew'],
            ],
            'administrator' => [
                'display_name' => 'Administrator',
                'description' => 'Full access to vessel including deletion and user management',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'edit_vessel_advanced', 'manage_crew', 'delete_vessel', 'manage_vessel_users'],
            ],
        ];

        foreach ($definitions as $name => $data) {
            VesselRoleAccess::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $data['display_name'],
                    'description' => $data['description'],
                    'permissions' => $data['permissions'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function createTestVessels(): array
    {
        $vessels = [];

        // Create test vessels
        $vesselData = [
            [
                'name' => 'Ocean Explorer',
                'registration_number' => 'OE-001',
                'vessel_type' => 'cargo',
                'status' => 'active',
                'notes' => 'Primary cargo vessel for user testing',
            ],
            [
                'name' => 'Sea Breeze',
                'registration_number' => 'SB-002',
                'vessel_type' => 'passenger',
                'status' => 'active',
                'notes' => 'Passenger vessel for user testing',
            ],
            [
                'name' => 'Deep Blue',
                'registration_number' => 'DB-003',
                'vessel_type' => 'fishing',
                'status' => 'maintenance',
                'notes' => 'Fishing vessel currently under maintenance',
            ],
            [
                'name' => 'Luxury Yacht',
                'registration_number' => 'LY-004',
                'vessel_type' => 'yacht',
                'status' => 'inactive',
                'notes' => 'Inactive luxury yacht for testing',
            ],
        ];

        foreach ($vesselData as $data) {
            $vessel = Vessel::updateOrCreate(
                ['registration_number' => $data['registration_number']],
                $data
            );
            $vessels[] = $vessel;
        }

        return $vessels;
    }

    private function createPaidSystemUsers($adminRole, $managerRole, $viewerRole, $vessels): void
    {
        $paidUsers = [
            [
                'name' => 'Paid Admin User',
                'email' => 'paid-admin@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'paid_system',
                'role' => $adminRole,
                'vessel_role' => 'administrator',
                'vessel_index' => 0, // Ocean Explorer
            ],
            [
                'name' => 'Paid Manager User',
                'email' => 'paid-manager@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'paid_system',
                'role' => $managerRole,
                'vessel_role' => 'administrator',
                'vessel_index' => 1, // Sea Breeze
            ],
            [
                'name' => 'Paid Viewer User',
                'email' => 'paid-viewer@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'paid_system',
                'role' => $viewerRole,
                'vessel_role' => 'administrator',
                'vessel_index' => 2, // Deep Blue
            ],
        ];

        foreach ($paidUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'user_type' => $userData['user_type'],
                    'email_verified_at' => now(),
                ]
            );

            // Assign system role
            $user->roles()->syncWithoutDetaching([$userData['role']->id]);

            // Assign vessel with administrator role
            $vessel = $vessels[$userData['vessel_index']];
            $this->assignVesselRole($user, $vessel, $userData['vessel_role']);

            $this->command->info("Created paid {$userData['role']->name} user: {$user->email}");
        }
    }

    private function createEmployeeUsers($normalAccess, $moderatorAccess, $supervisorAccess, $administratorAccess, $vessels): void
    {
        $employeeUsers = [
            [
                'name' => 'Employee Normal User',
                'email' => 'employee-normal@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'employee_of_vessel',
                'vessel_role' => 'normal',
                'vessel_index' => 0, // Ocean Explorer
            ],
            [
                'name' => 'Employee Moderator User',
                'email' => 'employee-moderator@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'employee_of_vessel',
                'vessel_role' => 'moderator',
                'vessel_index' => 0, // Ocean Explorer
            ],
            [
                'name' => 'Employee Supervisor User',
                'email' => 'employee-supervisor@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'employee_of_vessel',
                'vessel_role' => 'supervisor',
                'vessel_index' => 1, // Sea Breeze
            ],
            [
                'name' => 'Employee Administrator User',
                'email' => 'employee-admin@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'employee_of_vessel',
                'vessel_role' => 'administrator',
                'vessel_index' => 1, // Sea Breeze
            ],
        ];

        foreach ($employeeUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'user_type' => $userData['user_type'],
                    'email_verified_at' => now(),
                ]
            );

            // Assign vessel role
            $vessel = $vessels[$userData['vessel_index']];
            $this->assignVesselRole($user, $vessel, $userData['vessel_role']);

            $this->command->info("Created employee {$userData['vessel_role']} user: {$user->email}");
        }
    }

    private function createMixedPermissionUsers($adminRole, $managerRole, $viewerRole, $normalAccess, $moderatorAccess, $supervisorAccess, $administratorAccess, $vessels): void
    {
        $mixedUsers = [
            [
                'name' => 'Mixed Admin User',
                'email' => 'mixed-admin@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'paid_system',
                'system_role' => $adminRole,
                'vessel_roles' => [
                    ['vessel_index' => 0, 'role' => 'administrator'], // Ocean Explorer
                    ['vessel_index' => 1, 'role' => 'supervisor'],    // Sea Breeze
                    ['vessel_index' => 2, 'role' => 'moderator'],     // Deep Blue
                ],
            ],
            [
                'name' => 'Mixed Manager User',
                'email' => 'mixed-manager@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'paid_system',
                'system_role' => $managerRole,
                'vessel_roles' => [
                    ['vessel_index' => 0, 'role' => 'normal'],         // Ocean Explorer
                    ['vessel_index' => 1, 'role' => 'administrator'],  // Sea Breeze
                    ['vessel_index' => 3, 'role' => 'supervisor'],     // Luxury Yacht
                ],
            ],
            [
                'name' => 'Multi-Vessel Employee',
                'email' => 'multi-vessel@test.com',
                'password' => Hash::make('password'),
                'user_type' => 'employee_of_vessel',
                'system_role' => null,
                'vessel_roles' => [
                    ['vessel_index' => 0, 'role' => 'moderator'],      // Ocean Explorer
                    ['vessel_index' => 2, 'role' => 'supervisor'],      // Deep Blue
                    ['vessel_index' => 3, 'role' => 'normal'],         // Luxury Yacht
                ],
            ],
        ];

        foreach ($mixedUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'user_type' => $userData['user_type'],
                    'email_verified_at' => now(),
                ]
            );

            // Assign system role if exists
            if ($userData['system_role']) {
                $user->roles()->syncWithoutDetaching([$userData['system_role']->id]);
            }

            // Assign multiple vessel roles
            foreach ($userData['vessel_roles'] as $vesselRole) {
                $vessel = $vessels[$vesselRole['vessel_index']];
                $this->assignVesselRole($user, $vessel, $vesselRole['role']);
            }

            $this->command->info("Created mixed permission user: {$user->email}");
        }
    }

    private function assignVesselRole(User $user, Vessel $vessel, string $role): void
    {
        // Get the vessel role access ID
        $roleAccess = VesselRoleAccess::where('name', $role)->first();
        if (!$roleAccess) {
            $this->command->warn("Role access '{$role}' not found for vessel '{$vessel->name}'");
            return;
        }

        VesselUserRole::updateOrCreate(
            [
                'user_id' => $user->id,
                'vessel_id' => $vessel->id,
            ],
            [
                'vessel_role_access_id' => $roleAccess->id,
                'is_active' => true,
            ]
        );

        // Set vessel owner if this is the first administrator
        if ($role === 'administrator' && !$vessel->owner_id) {
            $vessel->update(['owner_id' => $user->id]);
        }
    }
}
