<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Discord Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Restrict Discord logging to production only.
    | If true, Discord logs only work in production environment.
    | If false or not set, Discord logs work in all environments (default).
    |
    */

    'discord_logs_only_on_production' => env('DISCORD_LOGS_ONLY_ON_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => array_filter(
                array_map('trim', explode(',', env('LOG_STACK', 'single'))),
                function ($channel) {
                    // Filter out Discord channels only if restricted to production and not in production
                    if (in_array($channel, ['discord', 'discord-errors', 'discord-critical'])) {
                        $onlyProduction = env('DISCORD_LOGS_ONLY_ON_PRODUCTION', false);
                        $isProduction = env('APP_ENV') === 'production';
                        // If onlyProduction is true and we're not in production, filter out
                        // Otherwise, keep the channel (default behavior - always send)
                        return !($onlyProduction && !$isProduction);
                    }
                    return true;
                }
            ),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'discord' => [
            'driver' => 'discord',
            'handler_with' => [
                'webhook_url' => env('DISCORD_WEBHOOK_URL'),
                'username' => env('DISCORD_WEBHOOK_USERNAME', 'log-general-manager'),
                'avatar_url' => env('DISCORD_WEBHOOK_AVATAR_URL'),
                'include_context' => env('DISCORD_INCLUDE_CONTEXT', true),
                'max_message_length' => env('DISCORD_MAX_MESSAGE_LENGTH', 2000),
            ],
            'level' => env('DISCORD_LOG_LEVEL', 'info'),
        ],

        'discord-errors' => [
            'driver' => 'discord-errors',
            'handler_with' => [
                'webhook_url' => env('DISCORD_ERRORS_WEBHOOK_URL', env('DISCORD_WEBHOOK_URL')),
                'username' => env('DISCORD_ERRORS_WEBHOOK_USERNAME', 'log-error-manager'),
                'avatar_url' => env('DISCORD_ERRORS_WEBHOOK_AVATAR_URL'),
                'include_context' => env('DISCORD_INCLUDE_CONTEXT', true),
                'max_message_length' => env('DISCORD_MAX_MESSAGE_LENGTH', 2000),
            ],
            'level' => env('DISCORD_ERRORS_LOG_LEVEL', 'error'),
        ],

        'discord-critical' => [
            'driver' => 'discord-critical',
            'handler_with' => [
                'webhook_url' => env('DISCORD_CRITICAL_WEBHOOK_URL', env('DISCORD_WEBHOOK_URL')),
                'username' => env('DISCORD_CRITICAL_WEBHOOK_USERNAME', 'log-critical-manager'),
                'avatar_url' => env('DISCORD_CRITICAL_WEBHOOK_AVATAR_URL'),
                'include_context' => env('DISCORD_INCLUDE_CONTEXT', true),
                'max_message_length' => env('DISCORD_MAX_MESSAGE_LENGTH', 2000),
            ],
            'level' => env('DISCORD_CRITICAL_LOG_LEVEL', 'critical'),
        ],

    ],

];
