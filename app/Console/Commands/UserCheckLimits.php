<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserCheckLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-limits
                            {user? : User ID or email (optional, checks all if not provided)}
                            {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user vessel limits and current usage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $format = $this->option('format');

        if ($userIdentifier) {
            return $this->checkSingleUser($userIdentifier, $format);
        }

        return $this->checkAllUsers($format);
    }

    /**
     * Check limits for a single user.
     */
    private function checkSingleUser(string $userIdentifier, string $format): int
    {
        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        $this->displayUserLimits($user, $format);

        return Command::SUCCESS;
    }

    /**
     * Check limits for all paid_system users.
     */
    private function checkAllUsers(string $format): int
    {
        $users = User::where('user_type', 'paid_system')
            ->withCount('ownedVessels')
            ->orderBy('owned_vessels_count', 'desc')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No paid_system users found.');
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'status' => $user->status,
                    'owned_vessels_count' => $user->owned_vessels_count,
                    'vessel_limit' => null, // Will be implemented when column is added
                    'limit_status' => 'unlimited', // Will be calculated when limit column exists
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Table format
        $headers = ['ID', 'Email', 'Type', 'Status', 'Vessels Owned', 'Limit', 'Status'];
        $rows = $users->map(function ($user) {
            $vesselCount = $user->owned_vessels_count;
            $limit = 'Unlimited'; // Will show actual limit when column is added
            $status = 'OK';

            return [
                $user->id,
                $user->email,
                $user->user_type,
                $user->status,
                $vesselCount,
                $limit,
                $status,
            ];
        })->toArray();

        $this->table($headers, $rows);

        $totalVessels = $users->sum('owned_vessels_count');
        $this->info("\nTotal: {$users->count()} user(s) with {$totalVessels} vessel(s)");
        $this->line("\nNote: Vessel limits are not yet implemented in the database.");
        $this->line("Once 'vessel_limit' column is added, this command will show limit status.");

        return Command::SUCCESS;
    }

    /**
     * Display limits for a single user.
     */
    private function displayUserLimits(User $user, string $format): void
    {
        $vesselCount = $user->ownedVessels()->count();

        if ($format === 'json') {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'status' => $user->status,
                'login_permitted' => $user->login_permitted,
                'owned_vessels_count' => $vesselCount,
                'vessel_limit' => null, // Will be implemented when column is added
                'limit_status' => 'unlimited', // Will be calculated when limit column exists
            ];

            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return;
        }

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("User Limits: {$user->email}");
        $this->info("═══════════════════════════════════════════════════════════");

        $this->line("User Information:");
        $this->line("  ID: {$user->id}");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Type: {$user->user_type}");
        $this->line("  Status: {$user->status}");

        $this->line("\nVessel Limits:");
        $this->line("  Current Vessels: {$vesselCount}");
        $this->line("  Vessel Limit: Unlimited (not yet implemented)");

        if ($vesselCount > 0) {
            $this->line("\n  Owned Vessels:");
            foreach ($user->ownedVessels as $vessel) {
                $this->line("    - {$vessel->name} ({$vessel->registration_number}) - {$vessel->status}");
            }
        }

        $this->line("\nNote: Vessel limits are not yet implemented in the database.");
        $this->line("Once 'vessel_limit' column is added, this will show limit status.");

        $this->info("═══════════════════════════════════════════════════════════");
    }
}

