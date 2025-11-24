<?php

namespace App\Console\Commands\UserManage\Commands\User;

use App\Models\User;
use App\Models\Vessel;
use Illuminate\Console\Command;

class UserSetOwnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-owner
                            {user : User ID or email}
                            {vessel : Vessel ID or registration number}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a user as owner of a vessel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $vesselIdentifier = $this->argument('vessel');
        $force = $this->option('force');

        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        // Find vessel by ID or registration number
        $vessel = is_numeric($vesselIdentifier)
            ? Vessel::find($vesselIdentifier)
            : Vessel::where('registration_number', $vesselIdentifier)->first();

        if (! $vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return Command::FAILURE;
        }

        // Check if user is paid_system
        if ($user->user_type !== 'paid_system') {
            $this->error("User {$user->email} must be 'paid_system' to own vessels.");
            $this->line("Use 'php artisan user:set-paid {$user->email}' first.");
            return Command::FAILURE;
        }

        // Check if already owner
        if ($vessel->owner_id === $user->id) {
            $this->warn("User {$user->email} is already the owner of vessel {$vessel->name}.");
            return Command::SUCCESS;
        }

        // Show current info
        $this->info("Vessel Information:");
        $this->line("  ID: {$vessel->id}");
        $this->line("  Name: {$vessel->name}");
        $this->line("  Registration: {$vessel->registration_number}");
        $this->line("  Current Owner: " . ($vessel->owner ? $vessel->owner->email : 'None'));

        $this->info("\nUser Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Type: {$user->user_type}");

        // Count user's owned vessels
        $ownedVessels = $user->ownedVessels()->count();
        $this->line("  Currently Owns: {$ownedVessels} vessel(s)");

        if (! $force && ! $this->confirm("Set {$user->email} as owner of {$vessel->name}?", true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Update vessel owner
        $vessel->update(['owner_id' => $user->id]);

        $this->info("✓ User {$user->email} is now the owner of vessel {$vessel->name}.");

        return Command::SUCCESS;
    }
}

