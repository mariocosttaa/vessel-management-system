<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselRoleAccess;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Database\Seeder;

class UserVesselRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vessel = Vessel::where('registration_number', 'SYS-ADMIN-001')->first();

        if (!$vessel) {
            $this->command?->warn('UserVesselRoleSeeder: default vessel not found, skipping vessel role assignments.');
            return;
        }

        $roleAccesses = VesselRoleAccess::whereIn('name', ['administrator', 'supervisor', 'normal'])
            ->get()
            ->keyBy('name');

        foreach (['administrator', 'supervisor', 'normal'] as $key) {
            if (!isset($roleAccesses[$key])) {
                $this->command?->warn("UserVesselRoleSeeder: missing vessel role access '{$key}', skipping assignments.");
                return;
            }
        }

        $assignments = [
            [
                'email' => 'admin@example.com',
                'role_access' => 'administrator',
                'legacy_role' => 'owner',
            ],
            [
                'email' => 'manager@example.com',
                'role_access' => 'supervisor',
                'legacy_role' => 'manager',
            ],
            [
                'email' => 'viewer@example.com',
                'role_access' => 'normal',
                'legacy_role' => 'viewer',
            ],
        ];

        foreach ($assignments as $assignment) {
            $user = User::where('email', $assignment['email'])->first();

            if (!$user) {
                $this->command?->warn("UserVesselRoleSeeder: user '{$assignment['email']}' not found, skipping.");
                continue;
            }

            $roleAccess = $roleAccesses[$assignment['role_access']];

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

            VesselUser::updateOrCreate(
                [
                    'vessel_id' => $vessel->id,
                    'user_id' => $user->id,
                ],
                [
                    'is_active' => true,
                    'role' => $assignment['legacy_role'],
                ]
            );
        }
    }
}

