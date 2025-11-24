<?php

namespace App\Console\Commands;

use App\Actions\VpsAction;
use Illuminate\Console\Command;

class VpsManageCommand extends Command
{
    protected $signature = 'vps:manage
                            {command?* : The terminal command to execute (can include multiple words)}
                            {--i|interactive : Run in interactive mode}';

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
        $commandArgs = $this->argument('command');
        if ($this->option('interactive') || empty($commandArgs)) {
            return $this->interactiveMode();
        }

        // Single command mode - join all command arguments to handle commands with spaces
        $command = is_array($commandArgs) ? implode(' ', $commandArgs) : $commandArgs;
        
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
        // Ensure command is not empty
        $command = trim($command);
        if (empty($command)) {
            $this->error('Command cannot be empty.');
            return Command::FAILURE;
        }

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

        // Send to Discord - always include the command (input)
        $this->info('Sending results to Discord...');
        $sent = VpsAction::sendToDiscord(
            $command, // This is the input/command that was executed
            $result['output'] ?? '',
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

