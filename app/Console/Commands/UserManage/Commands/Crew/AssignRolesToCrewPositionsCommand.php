<?php

namespace App\Console\Commands\UserManage\Commands\Crew;

use App\Models\CrewPosition;
use App\Models\VesselRoleAccess;
use Illuminate\Console\Command;

class AssignRolesToCrewPositionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crew-positions:assign-roles
                            {--dry-run : Show what would be changed without making changes}
                            {--force : Force update even if role is already assigned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign default vessel roles to crew positions based on position hierarchy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Get all vessel roles
        $roles = [
            'administrator' => VesselRoleAccess::where('name', 'administrator')->first(),
            'supervisor'   => VesselRoleAccess::where('name', 'supervisor')->first(),
            'moderator'    => VesselRoleAccess::where('name', 'moderator')->first(),
            'normal'       => VesselRoleAccess::where('name', 'normal')->first(),
        ];

        // Check if roles exist
        foreach ($roles as $key => $role) {
            if (!$role) {
                $this->error("Vessel role '{$key}' not found. Please run vessel role seeder first.");
                return Command::FAILURE;
            }
        }

        // Define role assignments based on position hierarchy
        $roleAssignments = [
            // Command positions - Administrator or Supervisor
            'Captain'         => 'supervisor', // Captain should have supervisor level
            'Capitão'         => 'supervisor',
            'First Officer'   => 'moderator',
            'Imediato'        => 'moderator',
            'Chief Mate'      => 'moderator',
            'Second Officer'  => 'moderator',
            'Third Officer'   => 'moderator',

            // Engineering leadership
            'Chief Engineer'  => 'moderator',
            'Second Engineer' => 'moderator',
            'Third Engineer'  => 'normal',
            'Fourth Engineer' => 'normal',

            // Service leadership
            'Chief Cook'      => 'normal',
            'Chief Steward'   => 'normal',

            // Specialized positions
            'Safety Officer'  => 'moderator',
            'Medical Officer' => 'moderator',
            'Purser'          => 'moderator',
            'Radio Officer'   => 'normal',

            // All other positions default to 'normal'
        ];

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $this->info('Assigning roles to crew positions...');
        $this->newLine();

        // Get all crew positions
        $positions = CrewPosition::all();

        foreach ($positions as $position) {
            // Skip if role is already assigned and not forcing
            if (!$force && $position->vessel_role_access_id !== null) {
                $skipped++;
                continue;
            }

            // Determine role based on position name
            $roleName = $roleAssignments[$position->name] ?? 'normal';
            $role = $roles[$roleName];

            if (!$role) {
                $this->warn("  ⚠️  Position '{$position->name}' - Role '{$roleName}' not found, skipping.");
                $errors++;
                continue;
            }

            if ($dryRun) {
                $currentRole = $position->vesselRoleAccess ? $position->vesselRoleAccess->display_name : 'None';
                $this->line("  📝 {$position->name}: {$currentRole} → {$role->display_name}");
            } else {
                $position->update(['vessel_role_access_id' => $role->id]);
                $this->info("  ✅ {$position->name}: Assigned {$role->display_name} role");
            }

            $updated++;
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Updated: {$updated}");
        $this->line("  Skipped: {$skipped}");
        if ($errors > 0) {
            $this->error("  Errors: {$errors}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Use without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }
}

