<?php

namespace Database\Seeders\Test;

use App\Models\VesselRoleAccess;
use Illuminate\Database\Seeder;

class PermissionTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive permission test scenarios...');

        // Create additional test role accesses for edge cases
        $testRoleAccesses = [
            [
                'name' => 'guest',
                'display_name' => 'Guest User',
                'description' => 'Guest user with minimal permissions',
                'permissions' => [],
                'is_active' => true,
            ],
            [
                'name' => 'readonly',
                'display_name' => 'Read Only User',
                'description' => 'User with read-only access to all vessel data',
                'permissions' => ['view_vessel', 'view_crew', 'view_suppliers', 'view_bank_accounts', 'view_transactions'],
                'is_active' => true,
            ],
            [
                'name' => 'crew_manager',
                'display_name' => 'Crew Manager',
                'description' => 'User who can manage crew but not vessel settings',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'manage_crew', 'view_crew'],
                'is_active' => true,
            ],
            [
                'name' => 'financial_manager',
                'display_name' => 'Financial Manager',
                'description' => 'User who can manage financial aspects but not vessel settings',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'manage_transactions', 'manage_suppliers', 'manage_bank_accounts'],
                'is_active' => true,
            ],
            [
                'name' => 'maintenance_manager',
                'display_name' => 'Maintenance Manager',
                'description' => 'User who can manage maintenance but limited other permissions',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'manage_maintenance', 'view_crew'],
                'is_active' => true,
            ],
            [
                'name' => 'emergency_admin',
                'display_name' => 'Emergency Administrator',
                'description' => 'Emergency administrator with all permissions except user management',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'edit_vessel_advanced', 'manage_crew', 'delete_vessel', 'manage_transactions', 'manage_suppliers', 'manage_bank_accounts'],
                'is_active' => true,
            ],
        ];

        foreach ($testRoleAccesses as $roleAccess) {
            VesselRoleAccess::updateOrCreate(
                ['name' => $roleAccess['name']],
                $roleAccess
            );

            $this->command->info("Created test role access: {$roleAccess['display_name']}");
        }

        // Display permission matrix
        $this->displayPermissionMatrix();

        $this->command->info('Permission test scenarios created successfully!');
    }

    private function displayPermissionMatrix(): void
    {
        $this->command->info("\n=== PERMISSION MATRIX ===");

        $roleAccesses = VesselRoleAccess::where('is_active', true)->orderBy('name')->get();

        $allPermissions = collect();
        foreach ($roleAccesses as $roleAccess) {
            $allPermissions = $allPermissions->merge($roleAccess->permissions);
        }
        $allPermissions = $allPermissions->unique()->sort()->values();

        // Header
        $header = str_pad('Role', 20);
        foreach ($allPermissions as $permission) {
            $header .= str_pad($permission, 15);
        }
        $this->command->info($header);
        $this->command->info(str_repeat('-', strlen($header)));

        // Rows
        foreach ($roleAccesses as $roleAccess) {
            $row = str_pad($roleAccess->name, 20);
            foreach ($allPermissions as $permission) {
                $hasPermission = in_array($permission, $roleAccess->permissions);
                $row .= str_pad($hasPermission ? '✅' : '❌', 15);
            }
            $this->command->info($row);
        }

        $this->command->info("\n=== PERMISSION DESCRIPTIONS ===");
        foreach ($roleAccesses as $roleAccess) {
            $this->command->info("• {$roleAccess->display_name} ({$roleAccess->name}): {$roleAccess->description}");
            $this->command->info("  Permissions: " . implode(', ', $roleAccess->permissions));
            $this->command->info("");
        }
    }
}

