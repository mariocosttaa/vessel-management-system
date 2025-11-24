<?php

namespace App\Console\Commands;

use App\Actions\SqlAction;
use Illuminate\Console\Command;

class SqlManageCommand extends Command
{
    protected $signature = 'sql:manage
                            {query? : The SQL query to execute}
                            {--interactive : Run in interactive mode}
                            {--no-discord : Skip sending results to Discord}';

    protected $description = 'Execute SQL queries and send results to Discord (production only)';

    public function handle(): int
    {
        // Check if Discord is enabled (production only by default)
        $discordEnabled = SqlAction::isDiscordEnabled();
        $sendToDiscord = !$this->option('no-discord') && $discordEnabled;

        if ($sendToDiscord) {
            $webhookUrl = env('SQL_DISCORD_WEBHOOK_URL');
            if (empty($webhookUrl)) {
                $this->warn('SQL Discord webhook URL is not configured. Results will not be sent to Discord.');
                $this->info('Please set SQL_DISCORD_WEBHOOK_URL in your .env file.');
                $sendToDiscord = false;
            }
        } elseif (!$discordEnabled) {
            $this->info('SQL Discord logging is only available in production environment.');
            $this->info('Set SQL_DISCORD_ONLY_ON_PRODUCTION=false in .env to enable in other environments.');
        }

        // Interactive mode
        if ($this->option('interactive') || !$this->argument('query')) {
            return $this->interactiveMode($sendToDiscord);
        }

        // Single query mode
        $query = $this->argument('query');
        return $this->executeQuery($query, $sendToDiscord);
    }

    /**
     * Interactive mode - allows multiple queries
     */
    protected function interactiveMode(bool $sendToDiscord): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           SQL Management System');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        if ($sendToDiscord) {
            $this->info('Queries will be executed and results sent to Discord.');
        } else {
            $this->info('Queries will be executed locally (Discord logging disabled).');
        }

        $this->info('Type "exit" or "quit" to exit.');
        $this->info('Type "help" for available commands.');
        $this->newLine();

        while (true) {
            $query = $this->ask('Enter SQL query');

            if (empty($query) || in_array(strtolower(trim($query)), ['exit', 'quit', 'q'])) {
                $this->info('Goodbye!');
                break;
            }

            if (strtolower(trim($query)) === 'help') {
                $this->showHelp();
                $this->newLine();
                continue;
            }

            $this->executeQuery($query, $sendToDiscord);
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * Execute a single SQL query
     */
    protected function executeQuery(string $query, bool $sendToDiscord): int
    {
        $this->info("Executing SQL query...");
        $this->newLine();

        // Execute query
        $result = SqlAction::executeQuery($query);

        // Display results
        if ($result['success']) {
            if ($result['rows'] > 0) {
                // Format as table
                $formatted = SqlAction::formatAsTable($result['data']);
                $this->table($formatted['headers'], $formatted['rows']);
                $this->newLine();
                $this->info("✅ Query executed successfully");
                $this->info("Rows returned: {$result['rows']}");
            } else {
                $this->info("✅ Query executed successfully (0 rows returned)");
            }
            $this->info("Execution time: {$result['execution_time']}s");
        } else {
            $this->error("❌ Query failed: {$result['error']}");
            $this->newLine();
            $this->warn("Query: {$query}");
        }

        // Send to Discord if enabled
        if ($sendToDiscord) {
            $this->newLine();
            $this->info('Sending results to Discord...');
            $sent = SqlAction::sendToDiscord($query, $result);

            if ($sent) {
                $this->info('✅ Results sent to Discord successfully.');
            } else {
                $this->warn('⚠️  Failed to send results to Discord. Check logs for details.');
            }
        }

        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Show help information
     */
    protected function showHelp(): void
    {
        $this->info('Available Commands:');
        $this->line('  exit, quit, q  - Exit the SQL management system');
        $this->line('  help           - Show this help message');
        $this->newLine();
        $this->info('SQL Query Examples:');
        $this->line('  SELECT * FROM users LIMIT 10;');
        $this->line('  SELECT COUNT(*) as total FROM vessels;');
        $this->line('  SHOW TABLES;');
        $this->line('  DESCRIBE users;');
        $this->newLine();
        $this->warn('Note: Only SELECT, SHOW, DESCRIBE, and EXPLAIN queries are allowed by default.');
        $this->warn('Set SQL_ALLOW_NON_SELECT=true in .env to allow other query types.');
    }
}

