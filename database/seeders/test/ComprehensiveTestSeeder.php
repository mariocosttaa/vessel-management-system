<?php

namespace Database\Seeders\Test;

use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselUserRole;
use App\Models\VesselRoleAccess;
use Illuminate\Database\Seeder;

class ComprehensiveTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive test data seeding...');
        $this->command->info('=====================================');

        // Run all test seeders
        $this->call([
            PermissionTestSeeder::class,
            VesselTestSeeder::class,
            UserTestSeeder::class,
        ]);

        // Create additional test scenarios
        $this->createEdgeCaseScenarios();

        // Display summary
        $this->displayTestSummary();

        $this->command->info('✅ Comprehensive test data seeding completed!');
    }

    private function createEdgeCaseScenarios(): void
    {
        $this->command->info("\n🔧 Creating edge case scenarios...");

        // 1. User with no vessels
        $noVesselUser = User::updateOrCreate(
            ['email' => 'no-vessels@test.com'],
            [
                'name' => 'No Vessels User',
                'password' => bcrypt('password'),
                'user_type' => 'employee_of_vessel',
                'email_verified_at' => now(),
            ]
        );
        $this->command->info("Created user with no vessels: {$noVesselUser->email}");

        // 2. User with inactive vessel access
        $inactiveUser = User::updateOrCreate(
            ['email' => 'inactive-access@test.com'],
            [
                'name' => 'Inactive Access User',
                'password' => bcrypt('password'),
                'user_type' => 'employee_of_vessel',
                'email_verified_at' => now(),
            ]
        );

        $vessel = Vessel::where('registration_number', 'TCV-001')->first();
        if ($vessel) {
            $roleAccess = VesselRoleAccess::where('name', 'normal')->first();
            if ($roleAccess) {
                VesselUserRole::updateOrCreate(
                    [
                        'user_id' => $inactiveUser->id,
                        'vessel_id' => $vessel->id,
                    ],
                    [
                        'vessel_role_access_id' => $roleAccess->id,
                        'is_active' => false, // Inactive access
                    ]
                );
                $this->command->info("Created user with inactive vessel access: {$inactiveUser->email}");
            }
        }

        // 3. User with multiple roles on same vessel (should not happen, but testing)
        $multiRoleUser = User::updateOrCreate(
            ['email' => 'multi-role@test.com'],
            [
                'name' => 'Multi Role User',
                'password' => bcrypt('password'),
                'user_type' => 'employee_of_vessel',
                'email_verified_at' => now(),
            ]
        );

        $vessel = Vessel::where('registration_number', 'TPS-002')->first();
        if ($vessel) {
            // Create multiple entries (this should be prevented by unique constraint)
            try {
                $roleAccess = VesselRoleAccess::where('name', 'moderator')->first();
                if ($roleAccess) {
                    VesselUserRole::create([
                        'user_id' => $multiRoleUser->id,
                        'vessel_id' => $vessel->id,
                        'vessel_role_access_id' => $roleAccess->id,
                        'is_active' => true,
                    ]);
                    $this->command->info("Created multi-role user: {$multiRoleUser->email}");
                }
            } catch (\Exception $e) {
                $this->command->warn("Multi-role user creation failed (expected): {$e->getMessage()}");
            }
        }

        // 4. Vessel with no owner
        $noOwnerVessel = Vessel::updateOrCreate(
            ['registration_number' => 'NO-OWNER-001'],
            [
                'name' => 'No Owner Vessel',
                'vessel_type' => 'cargo',
                'status' => 'active',
                'notes' => 'Vessel with no owner assigned',
                'owner_id' => null,
            ]
        );
        $this->command->info("Created vessel with no owner: {$noOwnerVessel->name}");
    }

    private function displayTestSummary(): void
    {
        $this->command->info("\n📊 TEST DATA SUMMARY");
        $this->command->info("====================");

        // User summary
        $totalUsers = User::count();
        $paidUsers = User::where('user_type', 'paid_system')->count();
        $employeeUsers = User::where('user_type', 'employee_of_vessel')->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();

        $this->command->info("👥 USERS:");
        $this->command->info("  Total: {$totalUsers}");
        $this->command->info("  Paid System: {$paidUsers}");
        $this->command->info("  Employee of Vessel: {$employeeUsers}");
        $this->command->info("  Email Verified: {$verifiedUsers}");

        // Vessel summary
        $totalVessels = Vessel::count();
        $activeVessels = Vessel::where('status', 'active')->count();
        $vesselsWithOwners = Vessel::whereNotNull('owner_id')->count();
        $vesselsWithoutOwners = Vessel::whereNull('owner_id')->count();

        $this->command->info("\n🚢 VESSELS:");
        $this->command->info("  Total: {$totalVessels}");
        $this->command->info("  Active: {$activeVessels}");
        $this->command->info("  With Owners: {$vesselsWithOwners}");
        $this->command->info("  Without Owners: {$vesselsWithoutOwners}");

        // Vessel-User relationships
        $totalVesselUsers = VesselUserRole::count();
        $activeVesselUsers = VesselUserRole::where('is_active', true)->count();
        $inactiveVesselUsers = VesselUserRole::where('is_active', false)->count();

        $this->command->info("\n🔗 VESSEL-USER RELATIONSHIPS:");
        $this->command->info("  Total: {$totalVesselUsers}");
        $this->command->info("  Active: {$activeVesselUsers}");
        $this->command->info("  Inactive: {$inactiveVesselUsers}");

        // Role access summary
        $totalRoleAccesses = VesselRoleAccess::count();
        $activeRoleAccesses = VesselRoleAccess::where('is_active', true)->count();

        $this->command->info("\n🎭 ROLE ACCESSES:");
        $this->command->info("  Total: {$totalRoleAccesses}");
        $this->command->info("  Active: {$activeRoleAccesses}");

        // Display role distribution
        $roleDistribution = VesselUserRole::selectRaw('vessel_role_access_id, COUNT(*) as count')
            ->with('vesselRoleAccess')
            ->groupBy('vessel_role_access_id')
            ->orderBy('count', 'desc')
            ->get();

        $this->command->info("\n📈 ROLE DISTRIBUTION:");
        foreach ($roleDistribution as $role) {
            $this->command->info("  {$role->vesselRoleAccess->name}: {$role->count} users");
        }

        // Display test credentials
        $this->displayTestCredentials();
    }

    private function displayTestCredentials(): void
    {
        $this->command->info("\n🔑 TEST CREDENTIALS");
        $this->command->info("==================");
        $this->command->info("All test users have password: 'password'");
        $this->command->info("");

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
        ])->get();

        foreach ($testUsers as $user) {
            $vesselCount = VesselUserRole::where('user_id', $user->id)->count();
            $this->command->info("📧 {$user->email} ({$user->user_type}) - {$vesselCount} vessels");
        }

        $this->command->info("\n🧪 TEST SCENARIOS:");
        $this->command->info("• Paid system users can create vessels");
        $this->command->info("• Employee users can only access assigned vessels");
        $this->command->info("• Mixed permission users have different roles per vessel");
        $this->command->info("• Edge cases: no vessels, inactive access, multi-role");
        $this->command->info("• Orphaned vessels with no users");
        $this->command->info("• Vessels with no owners");
    }
}
