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
                            {--process-pending : Restart queue worker to process pending jobs}
                            {--show-logs : Show recent queue worker logs}
                            {--diagnose : Run diagnostics to identify queue worker issues}';

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

        // Process pending jobs if requested
        if ($this->option('process-pending')) {
            $this->processPendingJobs();
        }

        // Run diagnostics if requested
        if ($this->option('diagnose')) {
            $this->runDiagnostics();
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
                $isFatal = str_contains($status, 'FATAL');
                $statusLabel = $isFatal ? '✗ FATAL (CRASHING)' : '✗ NOT RUNNING';
                $this->line("  Status: <fg=red>{$statusLabel}</> (via Supervisor)");
                $this->line("  <fg=gray>{$status}</>");
                
                if ($isFatal) {
                    $this->error('  🚨 Queue worker is in FATAL state - it keeps crashing!');
                    $this->showQueueWorkerErrors();
                } else {
                    $this->warn('  ⚠️  Queue worker is not running! Jobs will not be processed.');
                }
                
                $this->line('  💡 Try: <fg=yellow>supervisorctl start queue-worker</>');
                $this->line('  💡 Check errors: <fg=yellow>php artisan queue:status --show-logs</>');
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
                            $isFatal = str_contains($line, 'FATAL');
                            $statusLabel = $isFatal ? '✗ FATAL (CRASHING)' : '✗ NOT RUNNING';
                            $this->line("  Status: <fg=red>{$statusLabel}</> (via Supervisor)");
                            $this->line("  <fg=gray>{$line}</>");
                            
                            if ($isFatal) {
                                $this->error('  🚨 Queue worker is in FATAL state - it keeps crashing!');
                                $this->showQueueWorkerErrors();
                            } else {
                                $this->warn('  ⚠️  Queue worker is not running! Jobs will not be processed.');
                            }
                            
                            $this->line('  💡 Try: <fg=yellow>supervisorctl start queue-worker</>');
                            $this->line('  💡 Check errors: <fg=yellow>php artisan queue:status --show-logs</>');
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
            
            // Check for FATAL state first
            if ($logContent && str_contains($logContent, 'queue-worker') && str_contains($logContent, 'FATAL')) {
                $this->line("  Status: <fg=red>✗ FATAL (CRASHING)</> (via Supervisor log)");
                $this->error('  🚨 Queue worker is in FATAL state - it keeps crashing!');
                $this->showQueueWorkerErrors();
                $this->line('  💡 Try: <fg=yellow>supervisorctl start queue-worker</>');
                $this->line('  💡 Check errors: <fg=yellow>php artisan queue:status --show-logs</>');
                $this->newLine();
                return true;
            }
            
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
                $this->line('  💡 To force processing: <fg=yellow>php artisan queue:status --process-pending</>');
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
                // Highlight errors
                if (str_contains($line, 'error') || str_contains($line, 'Error') || str_contains($line, 'ERROR') || 
                    str_contains($line, 'exception') || str_contains($line, 'Exception') ||
                    str_contains($line, 'failed') || str_contains($line, 'Failed')) {
                    $this->line("  <fg=red>{$line}</>");
                } else {
                    $this->line("  <fg=gray>{$line}</>");
                }
            }
        }

        $this->newLine();
    }

    /**
     * Show queue worker errors from logs.
     */
    private function showQueueWorkerErrors(): void
    {
        $logPath = storage_path('logs/queue-worker.log');
        $supervisorLog = '/var/log/supervisor/supervisord.log';

        $hasErrors = false;

        // Check queue worker log for errors
        if (File::exists($logPath)) {
            $logContent = @file_get_contents($logPath);
            if ($logContent) {
                $lines = explode("\n", $logContent);
                $errorLines = [];
                
                // Get last 30 lines and look for errors
                $recentLines = array_slice($lines, -30);
                foreach ($recentLines as $line) {
                    if (str_contains(strtolower($line), 'error') || 
                        str_contains(strtolower($line), 'exception') ||
                        str_contains(strtolower($line), 'fatal') ||
                        str_contains(strtolower($line), 'failed')) {
                        $errorLines[] = $line;
                        $hasErrors = true;
                    }
                }
                
                if (!empty($errorLines)) {
                    $this->line('  <fg=yellow>Recent errors from queue-worker.log:</>');
                    foreach (array_slice($errorLines, -5) as $errorLine) {
                        $this->line("    <fg=red>{$errorLine}</>");
                    }
                }
            }
        }

        // Check supervisor log for crash information
        if (File::exists($supervisorLog)) {
            $logContent = @file_get_contents($supervisorLog);
            if ($logContent) {
                $lines = explode("\n", $logContent);
                $crashLines = [];
                
                // Get lines related to queue-worker crashes
                foreach ($lines as $line) {
                    if (str_contains($line, 'queue-worker') && 
                        (str_contains($line, 'exited') || str_contains($line, 'FATAL') || str_contains($line, 'WARN'))) {
                        $crashLines[] = $line;
                        $hasErrors = true;
                    }
                }
                
                if (!empty($crashLines)) {
                    $this->line('  <fg=yellow>Supervisor crash logs:</>');
                    foreach (array_slice($crashLines, -3) as $crashLine) {
                        $this->line("    <fg=red>{$crashLine}</>");
                    }
                }
            }
        }

        if (!$hasErrors) {
            $this->line('  <fg=yellow>No specific errors found in logs. Check application logs:</>');
            $this->line('    <fg=gray>tail -f storage/logs/laravel.log</>');
        }
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

    /**
     * Process pending jobs by restarting queue worker or running queue:work.
     */
    private function processPendingJobs(): void
    {
        $this->info('🔄 Processing Pending Jobs:');

        try {
            $pendingCount = DB::table('jobs')->count();

            if ($pendingCount === 0) {
                $this->line('  No pending jobs to process.');
                $this->newLine();
                return;
            }

            // Get queue breakdown
            $queues = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get();

            $queueNames = $queues->pluck('queue')->filter()->unique()->values()->all();
            $queueList = !empty($queueNames) ? implode(',', $queueNames) : 'default';

            $this->line("  Found {$pendingCount} pending job(s) in queue(s): {$queueList}");

            // Try to restart queue worker via supervisor
            $restarted = $this->restartQueueWorker();

            if ($restarted) {
                $this->info('  ✓ Queue worker restarted via Supervisor.');
                $this->line('  💡 Jobs should be processed shortly. Check status again in a few seconds.');
            } else {
                // Fallback: Run queue:work once to process jobs
                $this->line('  Running queue:work to process pending jobs...');

                if (!$this->confirm("  Process all {$pendingCount} pending job(s) now?", true)) {
                    $this->line('  Cancelled.');
                    $this->newLine();
                    return;
                }

                // Run queue:work with --once flag to process jobs and exit
                $queueArg = !empty($queueNames) ? '--queue=' . implode(',', $queueNames) : '';
                $command = "php artisan queue:work {$queueArg} --once --tries=3 --timeout=300";

                $this->line("  Executing: <fg=gray>{$command}</>");

                // Execute the command
                $output = [];
                $returnVar = 0;
                exec($command . ' 2>&1', $output, $returnVar);

                if ($returnVar === 0) {
                    $this->info('  ✓ Queue worker executed successfully.');
                    if (!empty($output)) {
                        $this->line('  Output:');
                        foreach (array_slice($output, -5) as $line) {
                            $this->line("    <fg=gray>{$line}</>");
                        }
                    }
                } else {
                    $this->warn('  ⚠️  Queue worker execution completed with warnings.');
                    if (!empty($output)) {
                        $this->line('  Output:');
                        foreach (array_slice($output, -10) as $line) {
                            $this->line("    <fg=gray>{$line}</>");
                        }
                    }
                }

                // Check if jobs were processed
                $remainingCount = DB::table('jobs')->count();
                $processedCount = $pendingCount - $remainingCount;

                if ($processedCount > 0) {
                    $this->info("  ✓ Processed {$processedCount} job(s). {$remainingCount} remaining.");
                } else {
                    $this->warn("  ⚠️  No jobs were processed. {$remainingCount} still pending.");
                    $this->line('  💡 Check queue worker logs for errors: <fg=yellow>php artisan queue:status --show-logs</>');
                }
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error processing pending jobs: {$e->getMessage()}");
        }

        $this->newLine();
    }

    /**
     * Attempt to restart queue worker via supervisor.
     */
    private function restartQueueWorker(): bool
    {
        // Try to restart via supervisorctl
        $output = [];
        $returnVar = 0;
        @exec('supervisorctl restart queue-worker 2>&1', $output, $returnVar);

        if ($returnVar === 0) {
            return true;
        }

        // Try without specific name (restart all)
        @exec('supervisorctl restart all 2>&1', $output, $returnVar);

        if ($returnVar === 0) {
            // Check if queue-worker is in the output
            $outputStr = implode("\n", $output);
            if (str_contains($outputStr, 'queue-worker')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run diagnostics to identify queue worker issues.
     */
    private function runDiagnostics(): void
    {
        $this->info('🔍 Running Queue Worker Diagnostics:');
        $this->newLine();

        $issues = [];

        // Check database connection
        $this->line('  Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->line('    <fg=green>✓ Database connection OK</>');
        } catch (\Exception $e) {
            $this->line('    <fg=red>✗ Database connection failed</>');
            $this->line("    <fg=red>Error: {$e->getMessage()}</>");
            $issues[] = 'Database connection issue: ' . $e->getMessage();
        }

        // Check queue configuration
        $this->line('  Checking queue configuration...');
        $connection = config('queue.default');
        $driver = config("queue.connections.{$connection}.driver");
        
        if ($driver === 'database') {
            $table = config("queue.connections.{$connection}.table", 'jobs');
            $this->line("    <fg=green>✓ Queue driver: database (table: {$table})</>");
            
            // Check if table exists
            try {
                DB::table($table)->limit(1)->get();
                $this->line('    <fg=green>✓ Jobs table exists</>');
            } catch (\Exception $e) {
                $this->line('    <fg=red>✗ Jobs table does not exist or is not accessible</>');
                $issues[] = 'Jobs table issue: ' . $e->getMessage();
            }
        } else {
            $this->line("    <fg=yellow>⚠ Queue driver: {$driver}</>");
        }

        // Check environment variables
        $this->line('  Checking environment variables...');
        $requiredEnvVars = ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
        foreach ($requiredEnvVars as $var) {
            if (empty(env($var))) {
                $this->line("    <fg=red>✗ Missing: {$var}</>");
                $issues[] = "Missing environment variable: {$var}";
            } else {
                $this->line("    <fg=green>✓ {$var} is set</>");
            }
        }

        // Check storage permissions
        $this->line('  Checking storage permissions...');
        $storagePath = storage_path('logs');
        if (!is_writable($storagePath)) {
            $this->line('    <fg=red>✗ Storage/logs is not writable</>');
            $issues[] = 'Storage/logs directory is not writable';
        } else {
            $this->line('    <fg=green>✓ Storage/logs is writable</>');
        }

        // Check if we can run queue:work command
        $this->line('  Testing queue:work command...');
        $output = [];
        $returnVar = 0;
        @exec('php artisan queue:work --help 2>&1', $output, $returnVar);
        
        if ($returnVar === 0) {
            $this->line('    <fg=green>✓ queue:work command is available</>');
        } else {
            $this->line('    <fg=red>✗ queue:work command failed</>');
            $this->line('    <fg=red>Output: ' . implode("\n", array_slice($output, -3)) . '</>');
            $issues[] = 'queue:work command is not working properly';
        }

        // Check pending jobs for potential issues
        $this->line('  Checking pending jobs...');
        try {
            $pendingJobs = DB::table('jobs')->count();
            if ($pendingJobs > 0) {
                $this->line("    <fg=yellow>⚠ Found {$pendingJobs} pending job(s)</>");
                
                // Try to peek at the first job to see if it's valid
                $firstJob = DB::table('jobs')->orderBy('id', 'asc')->first();
                if ($firstJob) {
                    try {
                        $payload = json_decode($firstJob->payload, true);
                        if (isset($payload['displayName'])) {
                            $this->line("    <fg=green>✓ First job class: {$payload['displayName']}</>");
                        }
                    } catch (\Exception $e) {
                        $this->line('    <fg=red>✗ First job payload is invalid JSON</>');
                        $issues[] = 'Invalid job payload in queue';
                    }
                }
            } else {
                $this->line('    <fg=green>✓ No pending jobs</>');
            }
        } catch (\Exception $e) {
            $this->line('    <fg=red>✗ Error checking pending jobs</>');
            $issues[] = 'Cannot access jobs table: ' . $e->getMessage();
        }

        // Summary
        $this->newLine();
        if (empty($issues)) {
            $this->info('  ✓ All diagnostics passed!');
            $this->line('  💡 If queue worker is still crashing, check:');
            $this->line('     - Application logs: <fg=yellow>tail -f storage/logs/laravel.log</>');
            $this->line('     - Queue worker logs: <fg=yellow>php artisan queue:status --show-logs</>');
            $this->line('     - Supervisor logs: <fg=yellow>tail -f /var/log/supervisor/supervisord.log</>');
        } else {
            $this->error('  ✗ Found ' . count($issues) . ' issue(s):');
            foreach ($issues as $issue) {
                $this->line("    - <fg=red>{$issue}</>");
            }
            $this->newLine();
            $this->line('  💡 Fix the issues above and try restarting the queue worker:');
            $this->line('     <fg=yellow>supervisorctl restart queue-worker</>');
        }

        $this->newLine();
    }
}

