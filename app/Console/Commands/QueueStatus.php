<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class QueueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:status
                            {--retry-failed : Retry all failed jobs}
                            {--show-logs : Show recent queue worker logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queue worker status, pending jobs, and failed jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 Queue Status Check');
        $this->newLine();

        // Check queue connection
        $this->checkQueueConnection();

        // Check if queue worker is running
        $this->checkQueueWorker();

        // Check pending jobs
        $this->checkPendingJobs();

        // Check failed jobs
        $this->checkFailedJobs();

        // Show logs if requested
        if ($this->option('show-logs')) {
            $this->showQueueLogs();
        }

        // Retry failed jobs if requested
        if ($this->option('retry-failed')) {
            $this->retryFailedJobs();
        }

        return Command::SUCCESS;
    }

    /**
     * Check queue connection configuration.
     */
    private function checkQueueConnection(): void
    {
        $this->info('🔌 Queue Connection:');
        $connection = config('queue.default');
        $driver = config("queue.connections.{$connection}.driver");

        $this->line("  Connection: <fg=cyan>{$connection}</>");
        $this->line("  Driver: <fg=cyan>{$driver}</>");

        if ($driver === 'database') {
            $table = config("queue.connections.{$connection}.table", 'jobs');
            $this->line("  Table: <fg=cyan>{$table}</>");
        }

        $this->newLine();
    }

    /**
     * Check if queue worker process is running.
     */
    private function checkQueueWorker(): void
    {
        $this->info('👷 Queue Worker Status:');

        // Check via supervisorctl if available
        $supervisorStatus = $this->checkSupervisorStatus();

        if ($supervisorStatus) {
            return;
        }

        // Check via supervisor log file
        $logStatus = $this->checkSupervisorLog();

        if ($logStatus) {
            return;
        }

        // Fallback: Check process list (if ps is available)
        $this->checkProcessStatus();

        // Final check: Try to see if jobs are being processed
        $this->checkJobProcessing();
    }

    /**
     * Check supervisor status.
     */
    private function checkSupervisorStatus(): bool
    {
        // Try to check supervisor status
        $output = [];
        $returnVar = 0;
        @exec('supervisorctl status queue-worker 2>&1', $output, $returnVar);

        if ($returnVar === 0 && !empty($output)) {
            $status = implode("\n", $output);
            if (str_contains($status, 'RUNNING')) {
                $this->line("  Status: <fg=green>✓ RUNNING</> (via Supervisor)");
                $this->line("  <fg=gray>{$status}</>");
                $this->newLine();
                return true;
            } elseif (str_contains($status, 'STOPPED') || str_contains($status, 'FATAL')) {
                $this->line("  Status: <fg=red>✗ NOT RUNNING</> (via Supervisor)");
                $this->line("  <fg=gray>{$status}</>");
                $this->warn('  ⚠️  Queue worker is not running! Jobs will not be processed.');
                $this->line('  💡 Try: <fg=yellow>supervisorctl start queue-worker</>');
                $this->newLine();
                return true;
            }
        }

        // Try checking all supervisor processes
        @exec('supervisorctl status 2>&1', $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            $allStatus = implode("\n", $output);
            if (str_contains($allStatus, 'queue-worker')) {
                // Found queue-worker in status, parse it
                foreach ($output as $line) {
                    if (str_contains($line, 'queue-worker')) {
                        if (str_contains($line, 'RUNNING')) {
                            $this->line("  Status: <fg=green>✓ RUNNING</> (via Supervisor)");
                            $this->line("  <fg=gray>{$line}</>");
                            $this->newLine();
                            return true;
                        } elseif (str_contains($line, 'STOPPED') || str_contains($line, 'FATAL')) {
                            $this->line("  Status: <fg=red>✗ NOT RUNNING</> (via Supervisor)");
                            $this->line("  <fg=gray>{$line}</>");
                            $this->warn('  ⚠️  Queue worker is not running! Jobs will not be processed.');
                            $this->line('  💡 Try: <fg=yellow>supervisorctl start queue-worker</>');
                            $this->newLine();
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check supervisor log for queue worker activity.
     */
    private function checkSupervisorLog(): bool
    {
        $supervisorLog = '/var/log/supervisor/supervisord.log';
        $queueWorkerLog = storage_path('logs/queue-worker.log');

        // Check supervisor log
        if (File::exists($supervisorLog)) {
            $logContent = @file_get_contents($supervisorLog);
            if ($logContent && str_contains($logContent, 'queue-worker') && str_contains($logContent, 'RUNNING')) {
                $this->line("  Status: <fg=green>✓ RUNNING</> (via Supervisor log)");
                $this->newLine();
                return true;
            }
        }

        // Check queue worker log for recent activity
        if (File::exists($queueWorkerLog)) {
            $logContent = @file_get_contents($queueWorkerLog);
            if ($logContent) {
                $lines = explode("\n", $logContent);
                $recentLines = array_slice($lines, -10);
                $hasActivity = false;

                foreach ($recentLines as $line) {
                    if (str_contains($line, 'Processing') ||
                        str_contains($line, 'Processed') ||
                        str_contains($line, 'queue:work')) {
                        $hasActivity = true;
                        break;
                    }
                }

                if ($hasActivity) {
                    $this->line("  Status: <fg=green>✓ RUNNING</> (log shows activity)");
                    $this->newLine();
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check process status.
     */
    private function checkProcessStatus(): void
    {
        // Check if queue:work process is running (only if ps is available)
        $output = [];
        $returnVar = 0;
        @exec("ps aux 2>/dev/null | grep 'queue:work' | grep -v grep", $output, $returnVar);

        if ($returnVar === 0 && !empty($output)) {
            $this->line("  Status: <fg=green>✓ RUNNING</> (process found)");
            $processCount = count($output);
            $this->line("  Processes: <fg=cyan>{$processCount}</>");
            $this->newLine();
        } elseif ($returnVar !== 0) {
            // ps command not available, skip this check
            // Don't show error, just continue
        }
    }

    /**
     * Check if jobs are being processed by looking at job timestamps.
     */
    private function checkJobProcessing(): void
    {
        try {
            // Check if there are any jobs that were recently processed
            // This is a heuristic - if jobs table is empty and no failed jobs,
            // the worker might be running
            $pendingCount = DB::table('jobs')->count();
            $failedCount = DB::table('failed_jobs')->count();

            // If we have pending jobs but they're not being processed,
            // that's a sign the worker isn't running
            if ($pendingCount > 0) {
                // Check the oldest pending job
                $oldestJob = DB::table('jobs')
                    ->orderBy('id', 'asc')
                    ->first(['id', 'created_at']);

                if ($oldestJob) {
                    $createdAt = strtotime($oldestJob->created_at);
                    $ageInMinutes = (time() - $createdAt) / 60;

                    if ($ageInMinutes > 5) {
                        $this->line("  Status: <fg=red>✗ NOT RUNNING</> (jobs pending for {$ageInMinutes} minutes)");
                        $this->warn('  ⚠️  Queue worker is not running! Jobs will not be processed.');
                        $this->line('  💡 Check supervisor: <fg=yellow>supervisorctl status</>');
                        $this->line('  💡 Or start manually: <fg=yellow>php artisan queue:work --queue=emails</>');
                        $this->newLine();
                        return;
                    }
                }
            }

            // If we can't determine, show a warning
            $this->line("  Status: <fg=yellow>⚠ UNKNOWN</> (could not verify)");
            $this->line('  💡 Check manually: <fg=yellow>supervisorctl status queue-worker</>');
            $this->line('  💡 Or check logs: <fg=yellow>tail -f storage/logs/queue-worker.log</>');
            $this->newLine();
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Check pending jobs in the queue.
     */
    private function checkPendingJobs(): void
    {
        $this->info('📋 Pending Jobs:');

        try {
            $pendingCount = DB::table('jobs')->count();

            if ($pendingCount > 0) {
                $this->line("  Count: <fg=yellow>{$pendingCount}</>");
                $this->warn("  ⚠️  There are {$pendingCount} pending job(s) waiting to be processed.");

                // Show queue breakdown if possible
                $queues = DB::table('jobs')
                    ->select('queue', DB::raw('count(*) as count'))
                    ->groupBy('queue')
                    ->get();

                if ($queues->isNotEmpty()) {
                    $this->line('  Queue breakdown:');
                    foreach ($queues as $queue) {
                        $queueName = $queue->queue ?: 'default';
                        $this->line("    - <fg=cyan>{$queueName}</>: {$queue->count}");
                    }
                }

                $this->line('  💡 If queue worker is running, these will be processed automatically.');
            } else {
                $this->line("  Count: <fg=green>0</> (no pending jobs)");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error checking pending jobs: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Check failed jobs.
     */
    private function checkFailedJobs(): void
    {
        $this->info('❌ Failed Jobs:');

        try {
            $failedCount = DB::table('failed_jobs')->count();

            if ($failedCount > 0) {
                $this->line("  Count: <fg=red>{$failedCount}</>");
                $this->warn("  ⚠️  There are {$failedCount} failed job(s).");

                // Show recent failures
                $recentFailures = DB::table('failed_jobs')
                    ->orderBy('failed_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'queue', 'failed_at']);

                if ($recentFailures->isNotEmpty()) {
                    $this->line('  Recent failures:');
                    foreach ($recentFailures as $failure) {
                        $queue = $failure->queue ?: 'default';
                        $failedAt = $failure->failed_at ? date('Y-m-d H:i:s', strtotime($failure->failed_at)) : 'N/A';
                        $this->line("    - ID: <fg=cyan>{$failure->id}</> | Queue: <fg=cyan>{$queue}</> | Failed: <fg=gray>{$failedAt}</>");
                    }
                }

                $this->line('  💡 Retry with: <fg=yellow>php artisan queue:status --retry-failed</>');
                $this->line('  💡 Or: <fg=yellow>php artisan queue:retry all</>');
            } else {
                $this->line("  Count: <fg=green>0</> (no failed jobs)");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error checking failed jobs: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Show recent queue worker logs.
     */
    private function showQueueLogs(): void
    {
        $this->info('📄 Recent Queue Worker Logs:');

        $logPath = storage_path('logs/queue-worker.log');

        if (!File::exists($logPath)) {
            $this->warn("  Log file not found: {$logPath}");
            $this->newLine();
            return;
        }

        // Get last 20 lines
        $allLines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($allLines)) {
            $this->line('  No log entries found.');
        } else {
            $lines = array_slice($allLines, -20);
            foreach ($lines as $line) {
                $this->line("  <fg=gray>{$line}</>");
            }
        }

        $this->newLine();
    }

    /**
     * Retry all failed jobs.
     */
    private function retryFailedJobs(): void
    {
        $this->info('🔄 Retrying Failed Jobs:');

        try {
            $failedCount = DB::table('failed_jobs')->count();

            if ($failedCount === 0) {
                $this->line('  No failed jobs to retry.');
                $this->newLine();
                return;
            }

            if (!$this->confirm("  Retry all {$failedCount} failed job(s)?", true)) {
                $this->line('  Cancelled.');
                $this->newLine();
                return;
            }

            $this->call('queue:retry', ['id' => 'all']);

            $this->info("  ✓ Retried {$failedCount} failed job(s).");
            $this->line('  💡 Check queue status again to see if they were processed successfully.');
        } catch (\Exception $e) {
            $this->error("  ✗ Error retrying failed jobs: {$e->getMessage()}");
        }

        $this->newLine();
    }
}

