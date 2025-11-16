<?php

namespace App\Console\Commands;

use App\Models\Vessel;
use Illuminate\Console\Command;

class UserRemoveOwner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:remove-owner
                            {vessel : Vessel ID or registration number}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove owner from a vessel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $vesselIdentifier = $this->argument('vessel');
        $force = $this->option('force');

        // Find vessel by ID or registration number
        $vessel = is_numeric($vesselIdentifier)
            ? Vessel::find($vesselIdentifier)
            : Vessel::where('registration_number', $vesselIdentifier)->first();

        if (! $vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return Command::FAILURE;
        }

        // Check if has owner
        if (! $vessel->owner_id) {
            $this->warn("Vessel {$vessel->name} does not have an owner.");
            return Command::SUCCESS;
        }

        // Show current info
        $this->info("Vessel Information:");
        $this->line("  ID: {$vessel->id}");
        $this->line("  Name: {$vessel->name}");
        $this->line("  Registration: {$vessel->registration_number}");
        $this->line("  Current Owner: {$vessel->owner->email} ({$vessel->owner->name})");

        $this->warn("  ⚠ Warning: Removing owner will leave the vessel without an owner.");

        if (! $force && ! $this->confirm("Remove owner from vessel {$vessel->name}?", false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Remove owner
        $vessel->update(['owner_id' => null]);

        $this->info("✓ Owner removed from vessel {$vessel->name}.");

        return Command::SUCCESS;
    }
}

