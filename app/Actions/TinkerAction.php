<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TinkerAction
{
    /**
     * Execute PHP code using eval() with proper error handling (similar to Tinker)
     *
     * @param string $code The PHP code to execute
     * @return array ['output' => string, 'has_error' => bool, 'execution_time' => float]
     */
    public static function executeCode(string $code): array
    {
        $startTime = microtime(true);
        $hasError = false;
        $output = '';

        try {
            // Capture output and errors
            ob_start();

            // Set error handler to catch errors
            set_error_handler(function ($severity, $message, $file, $line) use (&$hasError, &$output) {
                $hasError = true;
                $output .= "Error: {$message} in {$file} on line {$line}\n";
                return true; // Suppress default error handler
            });

            // Execute the code as-is
            // Note: eval() returns the result of the last expression or null
            $result = eval($code);

            // Restore error handler
            restore_error_handler();

            // Get output
            $output .= ob_get_clean();

            // Format result if not null
            if ($result !== null) {
                $formatted = self::formatResult($result);
                if (!empty($formatted)) {
                    $output .= (!empty($output) ? "\n" : '') . $formatted;
                }
            }

            $executionTime = microtime(true) - $startTime;

            // Clean up output
            $output = trim($output);
            if (empty($output)) {
                $output = '(no output)';
            }

            return [
                'output' => $output,
                'has_error' => $hasError,
                'execution_time' => round($executionTime, 2),
            ];
        } catch (\Throwable $e) {
            restore_error_handler();
            ob_end_clean();

            $executionTime = microtime(true) - $startTime;

            return [
                'output' => 'Exception: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine(),
                'has_error' => true,
                'execution_time' => round($executionTime, 2),
            ];
        }
    }

    /**
     * Format execution result for display
     */
    protected static function formatResult($result): string
    {
        if ($result === null) {
            return '';
        }

        // Use var_export for simple types, or get_class for objects
        if (is_scalar($result)) {
            return var_export($result, true);
        } elseif (is_array($result)) {
            return print_r($result, true);
        } elseif (is_object($result)) {
            $class = get_class($result);
            if (method_exists($result, '__toString')) {
                try {
                    return $class . ': ' . (string) $result;
                } catch (\Exception $e) {
                    return $class . ' Object';
                }
            }
            return $class . ' Object';
        }

        return gettype($result);
    }

    /**
     * Send tinker execution result to Discord webhook
     *
     * @param string $code The PHP code that was executed
     * @param string $output The execution output
     * @param bool $hasError Whether there was an error
     * @param float $executionTime Execution time in seconds
     * @return bool Success status
     */
    public static function sendToDiscord(
        string $code,
        string $output,
        bool $hasError = false,
        float $executionTime = 0.0
    ): bool {
        $webhookUrl = env('TINKER_DISCORD_WEBHOOK_URL', '');

        if (empty($webhookUrl)) {
            Log::warning('Tinker Discord webhook URL not configured');
            return false;
        }

        try {
            $status = $hasError ? '❌ Error' : '✅ Success';
            $color = $hasError ? 0xff0000 : 0x00ff00; // Red for error, green for success

            // Truncate output if too long (Discord limit is 2000 chars per field)
            $maxOutputLength = 1800;
            $truncatedOutput = strlen($output) > $maxOutputLength
                ? substr($output, 0, $maxOutputLength) . "\n... (truncated)"
                : $output;

            // Format code for display - ensure it's always visible
            $codeDisplay = !empty(trim($code))
                ? self::escapeMarkdown($code)
                : '(no code specified)';

            // Format output for code block
            $outputDisplay = !empty(trim($output)) ? $truncatedOutput : '(no output)';

            $embed = [
                'title' => 'Laravel Tinker Execution',
                'description' => "**Status:** {$status}\n**Execution Time:** `{$executionTime}s`",
                'color' => $color,
                'timestamp' => now()->toIso8601String(),
                'fields' => [
                    [
                        'name' => 'Code',
                        'value' => "```php\n{$codeDisplay}\n```",
                        'inline' => false,
                    ],
                    [
                        'name' => 'Output',
                        'value' => "```\n{$outputDisplay}\n```",
                        'inline' => false,
                    ],
                ],
                'footer' => [
                    'text' => env('APP_NAME', 'Laravel App') . ' - Tinker',
                ],
            ];

            $payload = [
                'embeds' => [$embed],
            ];

            $username = env('TINKER_DISCORD_WEBHOOK_USERNAME', 'tinker-manager');
            if ($username) {
                $payload['username'] = $username;
            }

            $avatarUrl = env('TINKER_DISCORD_WEBHOOK_AVATAR_URL', '');
            if ($avatarUrl) {
                $payload['avatar_url'] = $avatarUrl;
            }

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error('Tinker Discord webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Tinker Discord webhook exception', [
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
     * Check if Tinker Discord integration is enabled (production only)
     */
    public static function isEnabled(): bool
    {
        $onlyProduction = env('TINKER_ONLY_ON_PRODUCTION', 'true') === 'true' || env('TINKER_ONLY_ON_PRODUCTION', true) === true;
        $isProduction = env('APP_ENV', 'local') === 'production';

        // If onlyProduction is true, only allow in production
        // If onlyProduction is false, allow in all environments
        if ($onlyProduction && !$isProduction) {
            return false;
        }

        return true;
    }
}

