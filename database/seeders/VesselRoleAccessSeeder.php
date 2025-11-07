<?php

namespace Database\Seeders;

use App\Models\VesselRoleAccess;
use Illuminate\Database\Seeder;

class VesselRoleAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleAccesses = [
            [
                'name' => 'normal',
                'display_name' => 'Normal User',
                'description' => 'Basic read-only access to vessel information',
                'permissions' => ['view_vessel'],
                'is_active' => true,
            ],
            [
                'name' => 'moderator',
                'display_name' => 'Moderator',
                'description' => 'Can view and edit basic vessel information',
                'permissions' => ['view_vessel', 'edit_vessel_basic'],
                'is_active' => true,
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Supervisor',
                'description' => 'Can view, edit vessel information and manage crew',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'edit_vessel_advanced', 'manage_crew'],
                'is_active' => true,
            ],
            [
                'name' => 'administrator',
                'display_name' => 'Administrator',
                'description' => 'Full access to vessel including deletion and user management',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'edit_vessel_advanced', 'manage_crew', 'delete_vessel', 'manage_vessel_users'],
                'is_active' => true,
            ],
        ];

        foreach ($roleAccesses as $roleAccess) {
            VesselRoleAccess::updateOrCreate(
                ['name' => $roleAccess['name']],
                $roleAccess
            );
        }
    }
}
