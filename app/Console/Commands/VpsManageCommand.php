<?php

namespace App\Console\Commands;

use App\Actions\VpsAction;
use Illuminate\Console\Command;

class VpsManageCommand extends Command
{
    protected $signature = 'vps:manage {--i|interactive}';

    protected $description = 'Execute terminal commands on VPS and send results to Discord (production only)';

    public function handle(): int
    {
        // Check if VPS management is enabled
        if (!VpsAction::isEnabled()) {
            $this->error('VPS management is only available in production environment.');
            $this->info('Set VPS_ONLY_ON_PRODUCTION=false in .env to enable in other environments.');
            return Command::FAILURE;
        }

        // Check if webhook is configured
        $webhookUrl = env('VPS_DISCORD_WEBHOOK_URL', '');
        if (empty($webhookUrl)) {
            $this->error('VPS Discord webhook URL is not configured.');
            $this->info('Please set VPS_DISCORD_WEBHOOK_URL in your .env file.');
            return Command::FAILURE;
        }

        // Interactive mode
        if ($this->option('interactive')) {
            return $this->interactiveMode();
        }

        // Single command mode - get command from input
        $command = $this->ask('Enter command to execute (or press Enter for interactive mode)');

        if (empty($command)) {
            return $this->interactiveMode();
        }

        return $this->executeCommand($command);
    }

    /**
     * Interactive mode - allows multiple commands
     */
    protected function interactiveMode(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           VPS Management System');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();
        $this->info('Commands will be executed and results sent to Discord.');
        $this->info('Type "exit" or "quit" to exit.');
        $this->newLine();

        while (true) {
            $command = $this->ask('Enter command to execute');

            if (empty($command) || in_array(strtolower(trim($command)), ['exit', 'quit', 'q'])) {
                $this->info('Goodbye!');
                break;
            }

            $this->executeCommand($command);
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * Execute a single command
     */
    protected function executeCommand(string $command): int
    {
        $this->info("Executing: {$command}");
        $this->newLine();

        // Execute command
        $result = VpsAction::executeCommand($command);

        // Display output
        if (!empty($result['output'])) {
            $this->line($result['output']);
        }

        $this->newLine();
        $this->info("Exit Code: {$result['exit_code']}");
        $this->info("Execution Time: {$result['execution_time']}s");

        // Send to Discord
        $this->info('Sending results to Discord...');
        $sent = VpsAction::sendToDiscord(
            $command,
            $result['output'],
            $result['exit_code'],
            $result['execution_time']
        );

        if ($sent) {
            $this->info('✅ Results sent to Discord successfully.');
        } else {
            $this->warn('⚠️  Failed to send results to Discord. Check logs for details.');
        }

        return $result['exit_code'] === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}

