<?php
namespace Database\Seeders;

use App\Models\CrewPosition;
use App\Models\VesselRoleAccess;
use Illuminate\Database\Seeder;

class CrewPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates global crew positions (vessel_id = null) that can be used across all vessels.
     * These positions follow maritime industry standards and align with the vessel
     * role-based access control system.
     */
    public function run(): void
    {
        $positions = [
            // Deck Department - Command & Navigation
            [
                'name'                  => 'Captain',
                'description'           => null,
                'vessel_id'             => null, // Global position
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'First Officer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Second Officer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Third Officer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Chief Mate',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Bosun',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Able Seaman',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Ordinary Seaman',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Deckhand',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],

            // Engine Department
            [
                'name'                  => 'Chief Engineer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Second Engineer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Third Engineer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Fourth Engineer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Engineer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Mechanic',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Electrician',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Oiler',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Wiper',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],

            // Galley & Service Department
            [
                'name'                  => 'Cook',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Chief Cook',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Steward',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Chief Steward',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],

            // Specialized Positions
            [
                'name'                  => 'Radio Officer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Purser',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Carpenter',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Welder',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Safety Officer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Medical Officer',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Crane Operator',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Helmsman',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Lookout',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Trainee',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Cadet',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Supercargo',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name'                  => 'Pilot',
                'description'           => null,
                'vessel_id'             => null,
                'vessel_role_access_id' => null,
            ],
        ];

        foreach ($positions as $position) {
            CrewPosition::updateOrCreate(
                [
                    'name'      => $position['name'],
                    'vessel_id' => $position['vessel_id'],
                ],
                $position
            );
        }

        // Add administrative roles
        $this->seedAdministrativeRoles();
    }

    /**
     * Seed administrative roles with proper vessel role access mappings.
     */
    private function seedAdministrativeRoles(): void
    {
        // Get vessel role access IDs
        $administratorRole = VesselRoleAccess::where('name', 'administrator')->first();
        $supervisorRole = VesselRoleAccess::where('name', 'supervisor')->first();
        $normalRole = VesselRoleAccess::where('name', 'normal')->first();

        $administrativePositions = [
            // Administrative roles with administrator access
            [
                'name'                  => 'CEO',
                'description'           => 'Chief Executive Officer - Full administrative access',
                'vessel_id'             => null, // Global position
                'vessel_role_access_id' => $administratorRole?->id,
                'is_administrative'    => true,
            ],
            [
                'name'                  => 'Director',
                'description'           => 'Director - Full administrative access',
                'vessel_id'             => null,
                'vessel_role_access_id' => $administratorRole?->id,
                'is_administrative'    => true,
            ],
            [
                'name'                  => 'Administrador',
                'description'           => 'Administrator - Full administrative access',
                'vessel_id'             => null,
                'vessel_role_access_id' => $administratorRole?->id,
                'is_administrative'    => true,
            ],
            // Administrative roles with supervisor access
            [
                'name'                  => 'Finance Manager',
                'description'           => 'Finance Manager - Supervisor level access',
                'vessel_id'             => null,
                'vessel_role_access_id' => $supervisorRole?->id,
                'is_administrative'    => true,
            ],
            [
                'name'                  => 'Manager',
                'description'           => 'Manager - Supervisor level access',
                'vessel_id'             => null,
                'vessel_role_access_id' => $supervisorRole?->id,
                'is_administrative'    => true,
            ],
            // Administrative role with normal access
            [
                'name'                  => 'Visitant',
                'description'           => 'Visitor - View-only access',
                'vessel_id'             => null,
                'vessel_role_access_id' => $normalRole?->id,
                'is_administrative'    => true,
            ],
        ];

        foreach ($administrativePositions as $position) {
            CrewPosition::updateOrCreate(
                [
                    'name'      => $position['name'],
                    'vessel_id' => $position['vessel_id'],
                ],
                $position
            );
        }
    }
}
