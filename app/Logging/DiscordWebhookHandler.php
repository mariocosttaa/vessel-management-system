<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as LaravelLog;

class DiscordWebhookHandler extends AbstractProcessingHandler
{
    protected string $webhookUrl;
    protected ?string $username;
    protected ?string $avatarUrl;
    protected bool $includeContext;
    protected int $maxMessageLength;

    public function __construct(
        array $config = [],
        $level = \Monolog\Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);

        // Support both array config (from Laravel) and individual parameters (direct instantiation)
        if (isset($config['webhook_url'])) {
            $this->webhookUrl = $config['webhook_url'];
            $this->username = $config['username'] ?? config('app.name', 'Laravel App');
            $this->avatarUrl = $config['avatar_url'] ?? null;
            $this->includeContext = $config['include_context'] ?? true;
            $this->maxMessageLength = $config['max_message_length'] ?? 2000;
        } else {
            // Fallback for direct instantiation (backward compatibility)
            $this->webhookUrl = $config[0] ?? '';
            $this->username = $config[1] ?? config('app.name', 'Laravel App');
            $this->avatarUrl = $config[2] ?? null;
            $this->includeContext = $config[3] ?? true;
            $this->maxMessageLength = $config[4] ?? 2000;
        }

        // Validate webhook URL
        if (empty($this->webhookUrl)) {
            throw new \InvalidArgumentException('Discord webhook URL is required');
        }
    }

    protected function write(LogRecord $record): void
    {
        try {
            $payload = $this->formatRecord($record);

            $response = Http::timeout(5)->post($this->webhookUrl, $payload);

            if (!$response->successful()) {
                // Log error but don't throw to avoid infinite loops
                error_log('Discord webhook failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the application
            // Optionally log to error_log if needed
            error_log('Discord webhook exception: ' . $e->getMessage());
        }
    }

    protected function formatRecord(LogRecord $record): array
    {
        $level = strtoupper($record->level->getName());
        $message = $record->message;
        $context = $record->context;
        $timestamp = $record->datetime->format('Y-m-d H:i:s');
        $channel = $record->channel;

        // Get color based on log level
        $color = $this->getColorForLevel($record->level->value);

        // Build description
        $description = "**{$level}** - {$message}";

        if ($this->includeContext && !empty($context)) {
            $contextStr = $this->formatContext($context);
            if ($contextStr) {
                $description .= "\n\n**Context:**\n```json\n{$contextStr}\n```";
            }
        }

        // Truncate if too long
        if (strlen($description) > $this->maxMessageLength) {
            $description = substr($description, 0, $this->maxMessageLength - 3) . '...';
        }

        $embed = [
            'title' => "Log from {$this->username}",
            'description' => $description,
            'color' => $color,
            'timestamp' => $record->datetime->format('c'),
            'footer' => [
                'text' => "Channel: {$channel}",
            ],
        ];

        // Add fields for important context data
        if ($this->includeContext && !empty($context)) {
            $fields = $this->extractFields($context);
            if (!empty($fields)) {
                $embed['fields'] = $fields;
            }
        }

        $payload = [
            'embeds' => [$embed],
        ];

        if ($this->username) {
            $payload['username'] = $this->username;
        }

        if ($this->avatarUrl) {
            $payload['avatar_url'] = $this->avatarUrl;
        }

        return $payload;
    }

    protected function getColorForLevel(int $level): int
    {
        return match ($level) {
            \Monolog\Level::Debug->value => 0x808080,      // Gray
            \Monolog\Level::Info->value => 0x3498db,       // Blue
            \Monolog\Level::Notice->value => 0x3498db,     // Blue
            \Monolog\Level::Warning->value => 0xf39c12,    // Orange
            \Monolog\Level::Error->value => 0xe74c3c,      // Red
            \Monolog\Level::Critical->value => 0xe74c3c,   // Red
            \Monolog\Level::Alert->value => 0x9b59b6,      // Purple
            \Monolog\Level::Emergency->value => 0x992d22,  // Dark Red
            default => 0x95a5a6,                           // Gray
        };
    }

    protected function formatContext(array $context): string
    {
        // Remove sensitive data
        $filtered = $this->filterSensitiveData($context);

        $json = json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Discord code blocks have a limit, truncate if needed
        $maxLength = $this->maxMessageLength - 200; // Reserve space for other content
        if (strlen($json) > $maxLength) {
            $json = substr($json, 0, $maxLength - 3) . '...';
        }

        return $json;
    }

    protected function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret', 'authorization'];

        $filtered = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // Skip sensitive keys
            if (in_array($lowerKey, $sensitiveKeys) || str_contains($lowerKey, 'password') || str_contains($lowerKey, 'token')) {
                $filtered[$key] = '***REDACTED***';
                continue;
            }

            if (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    protected function extractFields(array $context): array
    {
        $fields = [];
        $importantKeys = ['user_id', 'user_email', 'vessel_id', 'ip', 'method', 'url', 'status', 'exception', 'error'];

        foreach ($importantKeys as $key) {
            if (isset($context[$key])) {
                $value = is_array($context[$key])
                    ? json_encode($context[$key], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : (string) $context[$key];

                // Truncate long values
                if (strlen($value) > 1024) {
                    $value = substr($value, 0, 1021) . '...';
                }

                $fields[] = [
                    'name' => ucfirst(str_replace('_', ' ', $key)),
                    'value' => $value,
                    'inline' => true,
                ];
            }
        }

        return $fields;
    }
}

