<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Seeder;

class DefaultVesselSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        if (!$admin) {
            $this->command?->warn('DefaultVesselSeeder: admin user not found, skipping vessel seeding.');
            return;
        }

        $vessel = Vessel::updateOrCreate(
            ['registration_number' => 'SYS-ADMIN-001'],
            [
                'name' => 'System Demo Vessel',
                'vessel_type' => 'cargo',
                'status' => 'active',
                'owner_id' => $admin->id,
            ]
        );

        if ($vessel->owner_id !== $admin->id) {
            $vessel->forceFill(['owner_id' => $admin->id])->save();
        }
    }
}

