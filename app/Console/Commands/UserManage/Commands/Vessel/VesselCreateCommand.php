<?php

namespace App\Console\Commands\UserManage\Commands\Vessel;

use App\Models\User;
use App\Models\Vessel;
use Illuminate\Console\Command;

class VesselCreateCommand extends Command
{
    protected $signature = 'vessel:create
                            {--name= : Vessel name}
                            {--registration= : Registration number}
                            {--type=fishing : Vessel type (cargo, passenger, fishing, fish, yacht)}
                            {--status=active : Status (active, suspended, maintenance, inactive)}
                            {--owner= : Owner email (must be paid_system user)}
                            {--capacity= : Capacity}
                            {--year= : Year built}
                            {--notes= : Notes}';

    protected $description = 'Create a new vessel';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Vessel name');
        if (empty($name)) {
            $this->error('Vessel name is required.');
            return Command::FAILURE;
        }

        $registrationNumber = $this->option('registration') ?: $this->ask('Registration number');
        if (empty($registrationNumber)) {
            $this->error('Registration number is required.');
            return Command::FAILURE;
        }

        if (Vessel::where('registration_number', $registrationNumber)->exists()) {
            $this->error('A vessel with this registration number already exists.');
            return Command::FAILURE;
        }

        $vesselType = $this->option('type') ?: $this->choice('Vessel type', ['cargo', 'passenger', 'fishing', 'fish', 'yacht'], 'fishing');
        $status = $this->option('status') ?: $this->choice('Status', ['active', 'suspended', 'maintenance', 'inactive'], 'active');
        $capacity = $this->option('capacity') ?: $this->ask('Capacity (optional)', null);
        $yearBuilt = $this->option('year') ?: $this->ask('Year built (optional)', null);
        $notes = $this->option('notes') ?: $this->ask('Notes (optional)', null);

        $ownerId = null;
        $ownerEmail = $this->option('owner') ?: $this->ask('Owner email (optional - must be paid_system user)');
        if ($ownerEmail) {
            $owner = User::where('email', $ownerEmail)->where('user_type', 'paid_system')->first();
            if (!$owner) {
                $this->error('Owner not found or is not a paid_system user.');
                return Command::FAILURE;
            }
            $ownerId = $owner->id;
        }

        try {
            $vessel = Vessel::create([
                'name' => $name,
                'registration_number' => $registrationNumber,
                'vessel_type' => $vesselType,
                'status' => $status,
                'capacity' => $capacity ? (int) $capacity : null,
                'year_built' => $yearBuilt ? (int) $yearBuilt : null,
                'notes' => $notes,
                'owner_id' => $ownerId,
            ]);

            $this->info("✓ Vessel {$vessel->name} created successfully (ID: {$vessel->id}).");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Error creating vessel: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

