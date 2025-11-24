<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiscordRegisterCommandsCommand extends Command
{
    protected $signature = 'discord:register-commands
                            {--guild-id= : Guild ID for guild commands (optional, leave empty for global commands)}';

    protected $description = 'Register Discord slash commands';

    public function handle(): int
    {
        $token = env('DISCORD_BOT_TOKEN');
        $applicationId = env('DISCORD_APPLICATION_ID');

        if (empty($token)) {
            $this->error('DISCORD_BOT_TOKEN is not set in .env');
            return Command::FAILURE;
        }

        if (empty($applicationId)) {
            $this->error('DISCORD_APPLICATION_ID is not set in .env');
            $this->info('Get it from: https://discord.com/developers/applications → Your App → General → Application ID');
            return Command::FAILURE;
        }

        $guildId = $this->option('guild-id');
        $url = $guildId
            ? "https://discord.com/api/v10/applications/{$applicationId}/guilds/{$guildId}/commands"
            : "https://discord.com/api/v10/applications/{$applicationId}/commands";

        $this->info($guildId ? "Registering guild commands for guild: {$guildId}" : "Registering global commands");

        $commands = $this->getCommands();

        foreach ($commands as $command) {
            $this->info("Registering: /{$command['name']}");

            $response = Http::withHeaders([
                'Authorization' => "Bot {$token}",
                'Content-Type' => 'application/json',
            ])->post($url, $command);

            if ($response->successful()) {
                $this->info("✅ Registered: /{$command['name']}");
            } else {
                $this->error("❌ Failed to register /{$command['name']}: {$response->body()}");
            }
        }

        $this->info("\n✅ Commands registered successfully!");
        $this->info("Note: Global commands may take up to 1 hour to appear. Guild commands appear immediately.");

        return Command::SUCCESS;
    }

    protected function getCommands(): array
    {
        $interactionUrl = env('APP_URL') . '/discord/interactions';

        return [
            [
                'name' => 'vps',
                'description' => 'Execute a terminal command on the VPS',
                'options' => [
                    [
                        'type' => 3, // STRING
                        'name' => 'command',
                        'description' => 'The terminal command to execute (e.g., ls -la)',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'sql',
                'description' => 'Execute a SQL query',
                'options' => [
                    [
                        'type' => 3, // STRING
                        'name' => 'query',
                        'description' => 'The SQL query to execute (e.g., SELECT * FROM users LIMIT 10)',
                        'required' => true,
                    ],
                ],
            ],
            [
                'name' => 'tinker',
                'description' => 'Execute PHP code via Laravel Tinker',
                'options' => [
                    [
                        'type' => 3, // STRING
                        'name' => 'code',
                        'description' => 'The PHP code to execute (e.g., User::count())',
                        'required' => true,
                    ],
                ],
            ],
        ];
    }
}

