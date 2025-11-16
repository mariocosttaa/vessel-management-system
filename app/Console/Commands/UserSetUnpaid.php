<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserSetUnpaid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-unpaid
                            {user : User ID or email}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a user as unpaid (employee_of_vessel - cannot create vessels)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $force = $this->option('force');

        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        // Check if already unpaid
        if ($user->user_type === 'employee_of_vessel') {
            $this->warn("User {$user->email} is already set as unpaid (employee_of_vessel).");
            return Command::SUCCESS;
        }

        // Show user info
        $this->info("User Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Current Type: {$user->user_type}");
        $this->line("  Status: {$user->status}");
        $this->line("  Login Permitted: " . ($user->login_permitted ? 'Yes' : 'No'));

        // Count owned vessels
        $ownedVessels = $user->ownedVessels()->count();
        if ($ownedVessels > 0) {
            $this->warn("  ⚠ Warning: This user owns {$ownedVessels} vessel(s).");
            $this->warn("  Changing to unpaid will prevent them from creating new vessels.");
            $this->warn("  Existing vessels will remain, but ownership should be transferred.");

            if (! $force && ! $this->confirm('Continue anyway?', false)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        if (! $force && ! $this->confirm('Set this user as unpaid (employee_of_vessel)?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Update user type
        $user->update(['user_type' => 'employee_of_vessel']);

        $this->info("✓ User {$user->email} has been set as unpaid (employee_of_vessel).");
        $this->line("  This user can no longer create vessels.");

        return Command::SUCCESS;
    }
}

