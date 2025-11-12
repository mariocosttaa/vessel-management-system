<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselRoleAccess;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MultiTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $roleAccesses = VesselRoleAccess::query()
            ->whereIn('name', ['administrator', 'supervisor', 'moderator', 'normal'])
            ->get()
            ->keyBy('name');

        if ($roleAccesses->count() < 4) {
            throw new \RuntimeException('Required vessel role access definitions are missing. Please run VesselRoleAccessSeeder first.');
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => $now,
                'user_type' => 'paid_system',
                'login_permitted' => true,
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('password'),
                'email_verified_at' => $now,
                'user_type' => 'paid_system',
                'login_permitted' => true,
            ]
        );

        $viewer = User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'password' => Hash::make('password'),
                'email_verified_at' => $now,
                'user_type' => 'employee_of_vessel',
                'login_permitted' => true,
            ]
        );

        $vessel1 = Vessel::updateOrCreate(
            ['registration_number' => 'OE-001'],
            [
                'name' => 'Ocean Explorer',
                'vessel_type' => 'cargo',
                'capacity' => 1000,
                'year_built' => 2020,
                'status' => 'active',
                'owner_id' => $admin->id,
            ]
        );

        $vessel2 = Vessel::updateOrCreate(
            ['registration_number' => 'SB-002'],
            [
                'name' => 'Sea Breeze',
                'vessel_type' => 'passenger',
                'capacity' => 200,
                'year_built' => 2018,
                'status' => 'active',
                'owner_id' => $manager->id,
            ]
        );

        if ($vessel1->owner_id !== $admin->id) {
            $vessel1->forceFill(['owner_id' => $admin->id])->save();
        }

        if ($vessel2->owner_id !== $manager->id) {
            $vessel2->forceFill(['owner_id' => $manager->id])->save();
        }

        $this->assignVesselAccess($admin, $vessel1, $roleAccesses['administrator'], 'owner');
        $this->assignVesselAccess($admin, $vessel2, $roleAccesses['supervisor'], 'manager');

        $this->assignVesselAccess($manager, $vessel1, $roleAccesses['moderator'], 'manager');
        $this->assignVesselAccess($manager, $vessel2, $roleAccesses['administrator'], 'owner');

        $this->assignVesselAccess($viewer, $vessel1, $roleAccesses['normal'], 'viewer');
        $this->assignVesselAccess($viewer, $vessel2, $roleAccesses['normal'], 'viewer');
    }

    protected function assignVesselAccess(User $user, Vessel $vessel, VesselRoleAccess $roleAccess, string $legacyRole): void
    {
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
                'role' => $legacyRole,
            ]
        );
    }
}
