<?php
namespace Database\Seeders;

use App\Models\CrewPosition;
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

        if (! $vessel) {
            $this->command?->warn('UserVesselRoleSeeder: default vessel not found, skipping vessel role assignments.');
            return;
        }

        $roleAccesses = VesselRoleAccess::whereIn('name', ['administrator', 'supervisor', 'normal'])
            ->get()
            ->keyBy('name');

        foreach (['administrator', 'supervisor', 'normal'] as $key) {
            if (! isset($roleAccesses[$key])) {
                $this->command?->warn("UserVesselRoleSeeder: missing vessel role access '{$key}', skipping assignments.");
                return;
            }
        }

        // Get or create crew positions (use English names, translations handled by frontend)
        $captainPosition   = CrewPosition::where('name', 'Captain')->first();
        $firstMatePosition = CrewPosition::where('name', 'First Officer')->first();
        $crewPosition      = CrewPosition::where('name', 'Able Seaman')->first();

        // Create positions if they don't exist (should not happen if CrewPositionSeeder runs first)
        if (! $captainPosition) {
            $captainPosition = CrewPosition::create([
                'name'        => 'Captain',
                'description' => null,
            ]);
        }
        if (! $firstMatePosition) {
            $firstMatePosition = CrewPosition::create([
                'name'        => 'First Officer',
                'description' => null,
            ]);
        }
        if (! $crewPosition) {
            $crewPosition = CrewPosition::create([
                'name'        => 'Able Seaman',
                'description' => null,
            ]);
        }

        $assignments = [
            [
                'email'       => 'admin@example.com',
                'role_access' => 'administrator',
                'legacy_role' => 'owner',
                'position'    => $captainPosition, // Captain position for vessel owner
            ],
            [
                'email'       => 'manager@example.com',
                'role_access' => 'supervisor',
                'legacy_role' => 'manager',
                'position'    => $firstMatePosition, // First mate position for supervisor
            ],
            [
                'email'       => 'viewer@example.com',
                'role_access' => 'normal',
                'legacy_role' => 'viewer',
                'position'    => $crewPosition, // Crew position for viewer
            ],
        ];

        foreach ($assignments as $assignment) {
            $user = User::where('email', $assignment['email'])->first();

            if (! $user) {
                $this->command?->warn("UserVesselRoleSeeder: user '{$assignment['email']}' not found, skipping.");
                continue;
            }

            $roleAccess = $roleAccesses[$assignment['role_access']];

            // Assign vessel role access
            VesselUserRole::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'vessel_id' => $vessel->id,
                ],
                [
                    'vessel_role_access_id' => $roleAccess->id,
                    'is_active'             => true,
                ]
            );

            // Assign legacy vessel user role
            VesselUser::updateOrCreate(
                [
                    'vessel_id' => $vessel->id,
                    'user_id'   => $user->id,
                ],
                [
                    'is_active' => true,
                    'role'      => $assignment['legacy_role'],
                ]
            );

            // Assign vessel_id and position_id to user so they appear in crew members list
            $user->update([
                'vessel_id'     => $vessel->id,
                'position_id'   => $assignment['position']->id,
                'status'        => 'active',
                'hire_date'     => now(),
                'administrative' => true, // Set all seeded users as administrative members
            ]);
        }
    }
}
