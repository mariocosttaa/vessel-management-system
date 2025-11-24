<?php

namespace App\Console\Commands\UserManage\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserDisableLoginCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:disable-login
                            {user : User ID or email}
                            {--all : Disable login for all users}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable login access for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $all = $this->option('all');
        $force = $this->option('force');

        if ($all) {
            return $this->disableAllUsers($force);
        }

        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        // Check if already disabled
        if (! $user->login_permitted) {
            $this->warn("User {$user->email} already has login access disabled.");
            return Command::SUCCESS;
        }

        // Show user info
        $this->info("User Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Type: {$user->user_type}");
        $this->line("  Status: {$user->status}");
        $this->line("  Current Login Permitted: Yes");

        // Warn if paid user
        if ($user->user_type === 'paid_system') {
            $ownedVessels = $user->ownedVessels()->count();
            if ($ownedVessels > 0) {
                $this->warn("  ⚠ Warning: This user owns {$ownedVessels} vessel(s).");
            }
        }

        if (! $force && ! $this->confirm('Disable login access for this user?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Disable login and generate temporary password
        $user->disableSystemAccess();

        $this->info("✓ Login access disabled for {$user->email}.");
        $this->line("  Temporary password generated: {$user->temporary_password}");

        return Command::SUCCESS;
    }

    /**
     * Disable login for all users.
     */
    private function disableAllUsers(bool $force): int
    {
        $count = User::where('login_permitted', true)->count();

        if ($count === 0) {
            $this->info('All users already have login access disabled.');
            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm("⚠ WARNING: This will disable login for {$count} user(s). Continue?", false)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $users = User::where('login_permitted', true)->get();
        $updated = 0;

        foreach ($users as $user) {
            $user->disableSystemAccess();
            $updated++;
        }

        $this->info("✓ Login access disabled for {$updated} user(s).");

        return Command::SUCCESS;
    }
}

