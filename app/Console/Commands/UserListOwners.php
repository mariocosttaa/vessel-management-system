<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserListOwners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:list-owners
                            {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users who own vessels';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');

        // Get users who own vessels
        $users = User::whereHas('ownedVessels')
            ->withCount('ownedVessels')
            ->orderBy('owned_vessels_count', 'desc')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users own vessels.');
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
                    'created_at' => $user->created_at->toIso8601String(),
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Table format
        $headers = ['ID', 'Name', 'Email', 'Type', 'Status', 'Vessels Owned', 'Created'];
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->name,
                $user->email,
                $user->user_type,
                $user->status,
                $user->owned_vessels_count,
                $user->created_at->format('Y-m-d'),
            ];
        })->toArray();

        $this->table($headers, $rows);

        $totalVessels = $users->sum('owned_vessels_count');
        $this->info("\nTotal: {$users->count()} owner(s) with {$totalVessels} vessel(s)");

        return Command::SUCCESS;
    }
}

