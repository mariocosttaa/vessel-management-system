<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpsAction
{
    /**
     * Send VPS command execution result to Discord webhook
     *
     * @param string $command The command that was executed
     * @param string $output The command output
     * @param int $exitCode The exit code (0 = success, non-zero = error)
     * @param float $executionTime Execution time in seconds
     * @return bool Success status
     */
    public static function sendToDiscord(
        string $command,
        string $output,
        int $exitCode = 0,
        float $executionTime = 0.0
    ): bool {
        $webhookUrl = env('VPS_DISCORD_WEBHOOK_URL', '');

        if (empty($webhookUrl)) {
            Log::warning('VPS Discord webhook URL not configured');
            return false;
        }

        try {
            $status = $exitCode === 0 ? '✅ Success' : '❌ Error';
            $color = $exitCode === 0 ? 0x00ff00 : 0xff0000; // Green for success, red for error

            // Truncate output if too long (Discord limit is 2000 chars per field)
            $maxOutputLength = 1800;
            $truncatedOutput = strlen($output) > $maxOutputLength
                ? substr($output, 0, $maxOutputLength) . "\n... (truncated)"
                : $output;

            // Format command for display - ensure it's always visible
            $commandDisplay = !empty(trim($command)) 
                ? self::escapeMarkdown($command) 
                : '(no command specified)';

            // Format output for code block
            $outputDisplay = !empty(trim($output)) ? $truncatedOutput : '(no output)';

            $embed = [
                'title' => 'VPS Command Execution',
                'description' => "**Status:** {$status}\n**Exit Code:** `{$exitCode}`\n**Execution Time:** `{$executionTime}s`",
                'color' => $color,
                'timestamp' => now()->toIso8601String(),
                'fields' => [
                    [
                        'name' => 'Command',
                        'value' => "```bash\n{$commandDisplay}\n```",
                        'inline' => false,
                    ],
                    [
                        'name' => 'Output',
                        'value' => "```\n{$outputDisplay}\n```",
                        'inline' => false,
                    ],
                ],
                'footer' => [
                    'text' => env('APP_NAME', 'Laravel App') . ' - VPS Manager',
                ],
            ];

            $payload = [
                'embeds' => [$embed],
            ];

            $username = env('VPS_DISCORD_WEBHOOK_USERNAME', 'vps-manager');
            if ($username) {
                $payload['username'] = $username;
            }

            $avatarUrl = env('VPS_DISCORD_WEBHOOK_AVATAR_URL', '');
            if ($avatarUrl) {
                $payload['avatar_url'] = $avatarUrl;
            }

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error('VPS Discord webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('VPS Discord webhook exception', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Escape markdown characters for Discord
     */
    protected static function escapeMarkdown(string $text): string
    {
        // Escape markdown special characters
        $chars = ['*', '_', '`', '|', '~', '>'];
        foreach ($chars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
    }

    /**
     * Check if VPS management is enabled (production only)
     */
    public static function isEnabled(): bool
    {
        // Get the value from env - it will be a string 'true' or 'false' or boolean
        $onlyProductionEnv = env('VPS_ONLY_ON_PRODUCTION', 'true');

        // Convert string 'false' to boolean false, string 'true' to boolean true
        // Also handle boolean values directly
        $onlyProduction = filter_var($onlyProductionEnv, FILTER_VALIDATE_BOOLEAN, [
            'flags' => FILTER_NULL_ON_FAILURE
        ]);

        // If filter_var returns null, default to true (safe default)
        if ($onlyProduction === null) {
            $onlyProduction = true;
        }

        $isProduction = env('APP_ENV', 'local') === 'production';

        // If onlyProduction is true, only allow in production
        // If onlyProduction is false, allow in all environments
        if ($onlyProduction && !$isProduction) {
            return false;
        }

        return true;
    }

    /**
     * Execute a terminal command safely
     *
     * @param string $command The command to execute
     * @param int $timeout Timeout in seconds (default: 60)
     * @return array ['output' => string, 'exit_code' => int, 'execution_time' => float]
     */
    public static function executeCommand(string $command, int $timeout = 60): array
    {
        $startTime = microtime(true);

        // Security: Only allow safe commands (whitelist approach)
        $allowedCommands = self::getAllowedCommands();
        $commandBase = explode(' ', trim($command))[0];

        if (!in_array($commandBase, $allowedCommands)) {
            return [
                'output' => "Error: Command '{$commandBase}' is not allowed for security reasons.",
                'exit_code' => 1,
                'execution_time' => 0.0,
            ];
        }

        // Execute command
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            return [
                'output' => 'Error: Failed to execute command.',
                'exit_code' => 1,
                'execution_time' => 0.0,
            ];
        }

        // Set timeout
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $error = '';
        $startTime = microtime(true);

        while (true) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            $changed = stream_select($read, $write, $except, 0, 200000);

            if ($changed === false) {
                break;
            }

            if ($changed > 0) {
                foreach ($read as $stream) {
                    if ($stream === $pipes[1]) {
                        $output .= stream_get_contents($stream);
                    } elseif ($stream === $pipes[2]) {
                        $error .= stream_get_contents($stream);
                    }
                }
            }

            $status = proc_get_status($process);
            if ($status['running'] === false) {
                break;
            }

            $elapsed = microtime(true) - $startTime;
            if ($elapsed > $timeout) {
                proc_terminate($process);
                return [
                    'output' => $output . "\n" . $error . "\nError: Command timed out after {$timeout} seconds.",
                    'exit_code' => 124, // Timeout exit code
                    'execution_time' => $elapsed,
                ];
            }
        }

        // Get remaining output
        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);

        // Close pipes
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        // Get exit code
        $status = proc_get_status($process);
        $exitCode = $status['exitcode'] ?? -1;

        proc_close($process);

        $executionTime = microtime(true) - $startTime;

        // Combine output and error
        $fullOutput = trim($output);
        if (!empty($error)) {
            $fullOutput .= (!empty($fullOutput) ? "\n" : '') . trim($error);
        }

        return [
            'output' => $fullOutput ?: '(no output)',
            'exit_code' => $exitCode,
            'execution_time' => round($executionTime, 2),
        ];
    }

    /**
     * Get list of allowed commands (whitelist for security)
     */
    protected static function getAllowedCommands(): array
    {
        // Default safe commands - can be extended via config
        $defaultCommands = [
            'ls', 'pwd', 'whoami', 'date', 'uptime',
            'df', 'free', 'ps', 'top', 'htop',
            'systemctl', 'service', 'journalctl',
            'docker', 'docker-compose',
            'git', 'composer', 'php', 'artisan',
            'npm', 'node', 'yarn',
            'cat', 'tail', 'head', 'grep', 'find',
            'du', 'stat', 'uname', 'hostname',
        ];

        // Allow custom commands from config
        $customCommands = env('VPS_ALLOWED_COMMANDS', '') ?: '';
        if (!empty($customCommands)) {
            $customCommands = array_map('trim', explode(',', $customCommands));
            $defaultCommands = array_merge($defaultCommands, $customCommands);
        }

        return array_unique($defaultCommands);
    }
}

