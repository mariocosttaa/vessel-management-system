<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserSetStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-status
                            {user : User ID or email}
                            {status : Status (active, inactive, on_leave)}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set user status (active, inactive, on_leave)';

    /**
     * Valid status values.
     *
     * @var array<string>
     */
    private array $validStatuses = ['active', 'inactive', 'on_leave'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $status = strtolower($this->argument('status'));
        $force = $this->option('force');

        // Validate status
        if (! in_array($status, $this->validStatuses)) {
            $this->error("Invalid status: {$status}");
            $this->line("Valid statuses: " . implode(', ', $this->validStatuses));
            return Command::FAILURE;
        }

        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        // Check if already has this status
        if ($user->status === $status) {
            $this->warn("User {$user->email} already has status: {$status}");
            return Command::SUCCESS;
        }

        // Show user info
        $this->info("User Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Type: {$user->user_type}");
        $this->line("  Current Status: {$user->status}");
        $this->line("  New Status: {$status}");

        // Warn if setting to inactive
        if ($status === 'inactive') {
            $this->warn("  ⚠ Setting status to 'inactive' may affect user access.");
        }

        if (! $force && ! $this->confirm("Change user status to '{$status}'?", true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Update status
        $user->update(['status' => $status]);

        $this->info("✓ User {$user->email} status changed to: {$status}");

        return Command::SUCCESS;
    }
}

