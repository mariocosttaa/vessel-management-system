<?php

namespace Database\Seeders\Test;

use App\Models\Vessel;
use App\Models\VesselUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class VesselTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test vessels with different scenarios...');

        // Create vessels with different statuses and types
        $vessels = [
            [
                'name' => 'Test Cargo Vessel',
                'registration_number' => 'TCV-001',
                'vessel_type' => 'cargo',
                'status' => 'active',
                'notes' => 'Active cargo vessel for testing',
            ],
            [
                'name' => 'Test Passenger Ship',
                'registration_number' => 'TPS-002',
                'vessel_type' => 'passenger',
                'status' => 'active',
                'notes' => 'Active passenger ship for testing',
            ],
            [
                'name' => 'Test Fishing Boat',
                'registration_number' => 'TFB-003',
                'vessel_type' => 'fishing',
                'status' => 'maintenance',
                'notes' => 'Fishing boat under maintenance',
            ],
            [
                'name' => 'Test Luxury Yacht',
                'registration_number' => 'TLY-004',
                'vessel_type' => 'yacht',
                'status' => 'inactive',
                'notes' => 'Inactive luxury yacht',
            ],
            [
                'name' => 'Test Research Vessel',
                'registration_number' => 'TRV-005',
                'vessel_type' => 'cargo',
                'status' => 'active',
                'notes' => 'Research vessel for scientific work',
            ],
        ];

        foreach ($vessels as $vesselData) {
            $vessel = Vessel::updateOrCreate(
                ['registration_number' => $vesselData['registration_number']],
                $vesselData
            );

            $this->command->info("Created test vessel: {$vessel->name} ({$vessel->status})");
        }

        // Create vessels with no users (orphaned vessels)
        $orphanedVessels = [
            [
                'name' => 'Orphaned Vessel 1',
                'registration_number' => 'OV-001',
                'vessel_type' => 'cargo',
                'status' => 'active',
                'notes' => 'Vessel with no assigned users',
            ],
            [
                'name' => 'Orphaned Vessel 2',
                'registration_number' => 'OV-002',
                'vessel_type' => 'passenger',
                'status' => 'inactive',
                'notes' => 'Inactive vessel with no users',
            ],
        ];

        foreach ($orphanedVessels as $vesselData) {
            $vessel = Vessel::updateOrCreate(
                ['registration_number' => $vesselData['registration_number']],
                $vesselData
            );

            $this->command->info("Created orphaned vessel: {$vessel->name}");
        }

        $this->command->info('Test vessels created successfully!');
        $this->command->info('Total vessels: ' . Vessel::count());
    }
}

