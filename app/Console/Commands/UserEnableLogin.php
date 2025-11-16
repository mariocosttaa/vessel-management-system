<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserEnableLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:enable-login
                            {user : User ID or email}
                            {--all : Enable login for all users}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable login access for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $all = $this->option('all');
        $force = $this->option('force');

        if ($all) {
            return $this->enableAllUsers($force);
        }

        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        // Check if already enabled
        if ($user->login_permitted) {
            $this->warn("User {$user->email} already has login access enabled.");
            return Command::SUCCESS;
        }

        // Show user info
        $this->info("User Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Type: {$user->user_type}");
        $this->line("  Status: {$user->status}");
        $this->line("  Current Login Permitted: No");

        if (! $force && ! $this->confirm('Enable login access for this user?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Enable login and clear temporary password
        $user->enableSystemAccess();

        $this->info("✓ Login access enabled for {$user->email}.");
        $this->line("  Temporary password has been cleared.");

        return Command::SUCCESS;
    }

    /**
     * Enable login for all users.
     */
    private function enableAllUsers(bool $force): int
    {
        $count = User::where('login_permitted', false)->count();

        if ($count === 0) {
            $this->info('All users already have login access enabled.');
            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm("Enable login access for {$count} user(s)?", false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $updated = User::where('login_permitted', false)
            ->update([
                'login_permitted' => true,
                'temporary_password' => null,
            ]);

        $this->info("✓ Login access enabled for {$updated} user(s).");

        return Command::SUCCESS;
    }
}

