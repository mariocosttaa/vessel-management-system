<?php

namespace App\Console\Commands\UserManage\Commands\Crew;

use App\Models\User;
use App\Models\Vessel;
use Illuminate\Console\Command;

class CrewAssignCommand extends Command
{
    protected $signature = 'crew:assign
                            {user : User ID or email}
                            {vessel : Vessel ID or registration number}
                            {--remove : Remove crew member from vessel instead}';

    protected $description = 'Assign or remove a crew member from a vessel';

    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $vesselIdentifier = $this->argument('vessel');
        $remove = $this->option('remove');

        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (!$user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        $vessel = is_numeric($vesselIdentifier)
            ? Vessel::find($vesselIdentifier)
            : Vessel::where('registration_number', $vesselIdentifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return Command::FAILURE;
        }

        if ($remove) {
            if (!$user->vessel_id || $user->vessel_id !== $vessel->id) {
                $this->warn("User {$user->email} is not assigned to {$vessel->name}.");
                return Command::SUCCESS;
            }

            $user->update(['vessel_id' => null]);
            $this->info("✓ User removed from vessel successfully.");
            return Command::SUCCESS;
        }

        if ($user->vessel_id === $vessel->id) {
            $this->warn("User {$user->email} is already assigned to {$vessel->name}.");
            return Command::SUCCESS;
        }

        $user->update(['vessel_id' => $vessel->id]);
        $this->info("✓ User assigned to vessel successfully.");

        return Command::SUCCESS;
    }
}

