<?php

namespace App\Console\Commands\UserManage\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserSetPaidCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-paid
                            {user : User ID or email}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a user as paid_system (allows vessel creation)';

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

        // Check if already paid
        if ($user->user_type === 'paid_system') {
            $this->warn("User {$user->email} is already set as paid_system.");
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
        $this->line("  Owned Vessels: {$ownedVessels}");

        if (! $force && ! $this->confirm('Set this user as paid_system?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Update user type
        $user->update(['user_type' => 'paid_system']);

        $this->info("✓ User {$user->email} has been set as paid_system.");
        $this->line("  This user can now create and manage vessels.");

        return Command::SUCCESS;
    }
}

