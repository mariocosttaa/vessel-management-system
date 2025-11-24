<?php

namespace App\Console\Commands;

use App\Actions\TinkerAction;
use Illuminate\Console\Command;

class TinkerCommand extends Command
{
    protected $signature = 'tinker:discord
                            {code? : The PHP code to execute}
                            {--interactive : Run in interactive mode}';

    protected $description = 'Execute PHP code via Tinker and send results to Discord (production only)';

    public function handle(): int
    {
        // Check if Tinker Discord integration is enabled
        if (!TinkerAction::isEnabled()) {
            $this->error('Tinker Discord integration is only available in production environment.');
            $this->info('Set TINKER_ONLY_ON_PRODUCTION=false in .env to enable in other environments.');
            return Command::FAILURE;
        }

        // Check if webhook is configured
        $webhookUrl = env('TINKER_DISCORD_WEBHOOK_URL');
        if (empty($webhookUrl)) {
            $this->error('Tinker Discord webhook URL is not configured.');
            $this->info('Please set TINKER_DISCORD_WEBHOOK_URL in your .env file.');
            return Command::FAILURE;
        }

        // Interactive mode
        if ($this->option('interactive') || !$this->argument('code')) {
            return $this->interactiveMode();
        }

        // Single code execution mode
        $code = $this->argument('code');
        return $this->executeCode($code);
    }

    /**
     * Interactive mode - allows multiple code executions
     */
    protected function interactiveMode(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Laravel Tinker - Discord Integration');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();
        $this->info('PHP code will be executed and results sent to Discord.');
        $this->info('Type "exit" or "quit" to exit.');
        $this->newLine();

        while (true) {
            $code = $this->ask('Enter PHP code to execute');

            if (empty($code) || in_array(strtolower(trim($code)), ['exit', 'quit', 'q'])) {
                $this->info('Goodbye!');
                break;
            }

            $this->executeCode($code);
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * Execute PHP code
     */
    protected function executeCode(string $code): int
    {
        $this->info("Executing PHP code...");
        $this->newLine();

        // Execute code
        $result = TinkerAction::executeCode($code);

        // Display output
        if (!empty($result['output'])) {
            $this->line($result['output']);
        }

        $this->newLine();
        if ($result['has_error']) {
            $this->error("Status: Error");
        } else {
            $this->info("Status: Success");
        }
        $this->info("Execution Time: {$result['execution_time']}s");

        // Send to Discord
        $this->info('Sending results to Discord...');
        $sent = TinkerAction::sendToDiscord(
            $code,
            $result['output'],
            $result['has_error'],
            $result['execution_time']
        );

        if ($sent) {
            $this->info('✅ Results sent to Discord successfully.');
        } else {
            $this->warn('⚠️  Failed to send results to Discord. Check logs for details.');
        }

        return $result['has_error'] ? Command::FAILURE : Command::SUCCESS;
    }
}

