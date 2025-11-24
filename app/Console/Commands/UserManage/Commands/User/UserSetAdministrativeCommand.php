<?php

namespace App\Console\Commands\UserManage\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserSetAdministrativeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-administrative
                            {user : User ID or email}
                            {--remove : Remove administrative privileges}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set or remove administrative privileges for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $remove = $this->option('remove');
        $force = $this->option('force');

        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        $newValue = ! $remove;

        // Check if already has desired value
        if ($user->administrative === $newValue) {
            $status = $newValue ? 'has' : 'does not have';
            $this->warn("User {$user->email} already {$status} administrative privileges.");
            return Command::SUCCESS;
        }

        // Show user info
        $this->info("User Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Type: {$user->user_type}");
        $this->line("  Status: {$user->status}");
        $this->line("  Current Administrative: " . ($user->administrative ? 'Yes' : 'No'));
        $this->line("  New Administrative: " . ($newValue ? 'Yes' : 'No'));

        if (! $force && ! $this->confirm(($remove ? 'Remove' : 'Grant') . ' administrative privileges?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Update administrative flag
        $user->update(['administrative' => $newValue]);

        $action = $remove ? 'removed from' : 'granted to';
        $this->info("✓ Administrative privileges {$action} {$user->email}.");

        return Command::SUCCESS;
    }
}

