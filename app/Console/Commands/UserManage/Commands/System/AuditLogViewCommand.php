<?php

namespace App\Console\Commands\UserManage\Commands\System;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class AuditLogViewCommand extends Command
{
    protected $signature = 'audit:view
                            {--limit=20 : Number of logs to show}
                            {--format=table : Output format (table, json)}';

    protected $description = 'View audit logs';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $format = $this->option('format');

        $logs = AuditLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No audit logs found.');
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user ? $log->user->email : 'System',
                    'action' => $log->action,
                    'model_type' => $log->model_type,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $headers = ['ID', 'User', 'Action', 'Model', 'Created'];
        $rows = $logs->map(function ($log) {
            return [
                $log->id,
                $log->user ? $log->user->email : 'System',
                $log->action,
                $log->model_type ?? 'N/A',
                $log->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Showing {$logs->count()} log(s)");

        return Command::SUCCESS;
    }
}

