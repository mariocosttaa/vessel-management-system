<?php

namespace App\Console\Commands\UserManage\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserListPaidCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:list-paid
                            {--status= : Filter by status (active, inactive, on_leave)}
                            {--with-vessels : Show owned vessels count}
                            {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all paid_system users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $status = $this->option('status');
        $withVessels = $this->option('with-vessels');
        $format = $this->option('format');

        // Build query
        $query = User::where('user_type', 'paid_system');

        if ($status) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        if ($users->isEmpty()) {
            $this->info('No paid_system users found.');
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $users->map(function ($user) use ($withVessels) {
                $result = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'login_permitted' => $user->login_permitted,
                    'administrative' => $user->administrative,
                    'created_at' => $user->created_at->toIso8601String(),
                ];

                if ($withVessels) {
                    $result['owned_vessels_count'] = $user->ownedVessels()->count();
                }

                return $result;
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Table format
        $headers = ['ID', 'Name', 'Email', 'Status', 'Login', 'Admin', 'Created'];
        if ($withVessels) {
            $headers[] = 'Vessels';
        }

        $rows = $users->map(function ($user) use ($withVessels) {
            $row = [
                $user->id,
                $user->name,
                $user->email,
                $user->status,
                $user->login_permitted ? 'Yes' : 'No',
                $user->administrative ? 'Yes' : 'No',
                $user->created_at->format('Y-m-d'),
            ];

            if ($withVessels) {
                $row[] = $user->ownedVessels()->count();
            }

            return $row;
        })->toArray();

        $this->table($headers, $rows);

        $this->info("\nTotal: {$users->count()} paid_system user(s)");

        return Command::SUCCESS;
    }
}

