<?php

namespace App\Console\Commands\UserManage\Commands\Vessel;

use App\Models\User;
use App\Models\Vessel;
use Illuminate\Console\Command;

class VesselUpdateCommand extends Command
{
    protected $signature = 'vessel:update
                            {vessel : Vessel ID or registration number}
                            {--name= : New name}
                            {--registration= : New registration number}
                            {--type= : Vessel type}
                            {--status= : Status}
                            {--capacity= : Capacity}
                            {--year= : Year built}
                            {--notes= : Notes}
                            {--owner= : Owner email (leave empty to remove)}';

    protected $description = 'Update vessel information';

    public function handle(): int
    {
        $identifier = $this->argument('vessel');

        $vessel = is_numeric($identifier)
            ? Vessel::find($identifier)
            : Vessel::where('registration_number', $identifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$identifier}");
            return Command::FAILURE;
        }

        $updates = [];

        if ($this->option('name')) {
            $updates['name'] = $this->option('name');
        }
        if ($this->option('registration')) {
            $updates['registration_number'] = $this->option('registration');
        }
        if ($this->option('type')) {
            $updates['vessel_type'] = $this->option('type');
        }
        if ($this->option('status')) {
            $updates['status'] = $this->option('status');
        }
        if ($this->option('capacity') !== null) {
            $updates['capacity'] = $this->option('capacity') ? (int) $this->option('capacity') : null;
        }
        if ($this->option('year') !== null) {
            $updates['year_built'] = $this->option('year') ? (int) $this->option('year') : null;
        }
        if ($this->option('notes') !== null) {
            $updates['notes'] = $this->option('notes');
        }

        if ($this->option('owner') !== null) {
            $ownerEmail = $this->option('owner');
            if (empty($ownerEmail)) {
                $updates['owner_id'] = null;
            } else {
                $owner = User::where('email', $ownerEmail)->where('user_type', 'paid_system')->first();
                if (!$owner) {
                    $this->error('Owner not found or is not a paid_system user.');
                    return Command::FAILURE;
                }
                $updates['owner_id'] = $owner->id;
            }
        }

        if (empty($updates)) {
            $this->warn('No updates provided. Use --help to see available options.');
            return Command::SUCCESS;
        }

        try {
            $vessel->update($updates);
            $this->info("✓ Vessel updated successfully.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Error updating vessel: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

