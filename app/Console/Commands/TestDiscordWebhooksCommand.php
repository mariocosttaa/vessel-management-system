<?php

namespace App\Console\Commands;

use App\Actions\SqlAction;
use App\Actions\TinkerAction;
use App\Actions\VpsAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestDiscordWebhooksCommand extends Command
{
    protected $signature = 'discord:test';

    protected $description = 'Test all Discord webhook configurations (VPS, Tinker, SQL)';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('        Discord Webhook Configuration Test');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $results = [
            'vps' => $this->testVpsWebhook(),
            'tinker' => $this->testTinkerWebhook(),
            'sql' => $this->testSqlWebhook(),
        ];

        $this->newLine();
        $this->displaySummary($results);

        // Return success if at least one webhook is working
        $hasWorking = collect($results)->contains(fn($result) => $result['configured'] && $result['test_sent']);

        return $hasWorking ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Test VPS webhook configuration
     */
    protected function testVpsWebhook(): array
    {
        $this->info('🔧 Testing VPS Discord Webhook...');
        $this->line('');

        $webhookUrl = env('VPS_DISCORD_WEBHOOK_URL', '');
        $isEnabled = VpsAction::isEnabled();
        $isProduction = env('APP_ENV', 'local') === 'production';
        $onlyProduction = filter_var(env('VPS_ONLY_ON_PRODUCTION', 'true'), FILTER_VALIDATE_BOOLEAN);
        $username = env('VPS_DISCORD_WEBHOOK_USERNAME', 'vps-manager');

        $result = [
            'name' => 'VPS Management',
            'configured' => !empty($webhookUrl),
            'enabled' => $isEnabled,
            'test_sent' => false,
            'webhook_url' => $webhookUrl,
            'environment' => [
                'current' => env('APP_ENV', 'local'),
                'is_production' => $isProduction,
                'only_production' => $onlyProduction,
            ],
            'username' => $username,
        ];

        // Check configuration
        if (empty($webhookUrl)) {
            $this->warn('  ⚠️  Webhook URL not configured (VPS_DISCORD_WEBHOOK_URL)');
            return $result;
        }

        $this->info('  ✅ Webhook URL configured');
        $this->line("     URL: " . substr($webhookUrl, 0, 50) . '...');
        $this->line("     Username: {$username}");

        // Check environment restrictions
        if (!$isEnabled) {
            $this->warn('  ⚠️  VPS management is disabled in this environment');
            $this->line("     Current environment: {$result['environment']['current']}");
            $this->line("     Only production: " . ($onlyProduction ? 'Yes' : 'No'));
            return $result;
        }

        $this->info('  ✅ Environment check passed');

        // Test webhook
        $this->line('  📤 Sending test message...');
        $testSent = $this->sendTestMessage($webhookUrl, $username, 'VPS Management', 'vps-manager');

        if ($testSent) {
            $this->info('  ✅ Test message sent successfully!');
            $result['test_sent'] = true;
        } else {
            $this->error('  ❌ Failed to send test message');
        }

        $this->newLine();
        return $result;
    }

    /**
     * Test Tinker webhook configuration
     */
    protected function testTinkerWebhook(): array
    {
        $this->info('🔧 Testing Tinker Discord Webhook...');
        $this->line('');

        $webhookUrl = env('TINKER_DISCORD_WEBHOOK_URL', '');
        $isEnabled = TinkerAction::isEnabled();
        $isProduction = env('APP_ENV', 'local') === 'production';
        $onlyProduction = filter_var(env('TINKER_ONLY_ON_PRODUCTION', 'true'), FILTER_VALIDATE_BOOLEAN);
        $username = env('TINKER_DISCORD_WEBHOOK_USERNAME', 'tinker-manager');

        $result = [
            'name' => 'Tinker Discord',
            'configured' => !empty($webhookUrl),
            'enabled' => $isEnabled,
            'test_sent' => false,
            'webhook_url' => $webhookUrl,
            'environment' => [
                'current' => env('APP_ENV', 'local'),
                'is_production' => $isProduction,
                'only_production' => $onlyProduction,
            ],
            'username' => $username,
        ];

        // Check configuration
        if (empty($webhookUrl)) {
            $this->warn('  ⚠️  Webhook URL not configured (TINKER_DISCORD_WEBHOOK_URL)');
            return $result;
        }

        $this->info('  ✅ Webhook URL configured');
        $this->line("     URL: " . substr($webhookUrl, 0, 50) . '...');
        $this->line("     Username: {$username}");

        // Check environment restrictions
        if (!$isEnabled) {
            $this->warn('  ⚠️  Tinker Discord is disabled in this environment');
            $this->line("     Current environment: {$result['environment']['current']}");
            $this->line("     Only production: " . ($onlyProduction ? 'Yes' : 'No'));
            return $result;
        }

        $this->info('  ✅ Environment check passed');

        // Test webhook
        $this->line('  📤 Sending test message...');
        $testSent = $this->sendTestMessage($webhookUrl, $username, 'Tinker Discord', 'tinker-manager');

        if ($testSent) {
            $this->info('  ✅ Test message sent successfully!');
            $result['test_sent'] = true;
        } else {
            $this->error('  ❌ Failed to send test message');
        }

        $this->newLine();
        return $result;
    }

    /**
     * Test SQL webhook configuration
     */
    protected function testSqlWebhook(): array
    {
        $this->info('🔧 Testing SQL Discord Webhook...');
        $this->line('');

        $webhookUrl = env('SQL_DISCORD_WEBHOOK_URL', '');
        $isEnabled = SqlAction::isDiscordEnabled();
        $isProduction = env('APP_ENV', 'local') === 'production';
        $onlyProduction = filter_var(env('SQL_DISCORD_ONLY_ON_PRODUCTION', 'true'), FILTER_VALIDATE_BOOLEAN);
        $username = env('SQL_DISCORD_WEBHOOK_USERNAME', 'sql-manager');

        $result = [
            'name' => 'SQL Management',
            'configured' => !empty($webhookUrl),
            'enabled' => $isEnabled,
            'test_sent' => false,
            'webhook_url' => $webhookUrl,
            'environment' => [
                'current' => env('APP_ENV', 'local'),
                'is_production' => $isProduction,
                'only_production' => $onlyProduction,
            ],
            'username' => $username,
        ];

        // Check configuration
        if (empty($webhookUrl)) {
            $this->warn('  ⚠️  Webhook URL not configured (SQL_DISCORD_WEBHOOK_URL)');
            return $result;
        }

        $this->info('  ✅ Webhook URL configured');
        $this->line("     URL: " . substr($webhookUrl, 0, 50) . '...');
        $this->line("     Username: {$username}");

        // Check environment restrictions
        if (!$isEnabled) {
            $this->warn('  ⚠️  SQL Discord is disabled in this environment');
            $this->line("     Current environment: {$result['environment']['current']}");
            $this->line("     Only production: " . ($onlyProduction ? 'Yes' : 'No'));
            return $result;
        }

        $this->info('  ✅ Environment check passed');

        // Test webhook
        $this->line('  📤 Sending test message...');
        $testSent = $this->sendTestMessage($webhookUrl, $username, 'SQL Management', 'sql-manager');

        if ($testSent) {
            $this->info('  ✅ Test message sent successfully!');
            $result['test_sent'] = true;
        } else {
            $this->error('  ❌ Failed to send test message');
        }

        $this->newLine();
        return $result;
    }

    /**
     * Send a test message to Discord webhook
     */
    protected function sendTestMessage(string $webhookUrl, string $username, string $title, string $footer): bool
    {
        try {
            $embed = [
                'title' => $title . ' - Test Message',
                'description' => "**Status:** ✅ Test Successful\n**Time:** " . now()->format('Y-m-d H:i:s'),
                'color' => 0x00ff00, // Green
                'timestamp' => now()->toIso8601String(),
                'fields' => [
                    [
                        'name' => 'Test Information',
                        'value' => 'This is a test message from the Discord webhook test command. If you see this, your webhook is configured correctly!',
                        'inline' => false,
                    ],
                ],
                'footer' => [
                    'text' => env('APP_NAME', 'Laravel App') . ' - ' . $footer,
                ],
            ];

            $payload = [
                'embeds' => [$embed],
                'username' => $username,
            ];

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Display test summary
     */
    protected function displaySummary(array $results): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    Test Summary');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $headers = ['Service', 'Configured', 'Enabled', 'Test Sent', 'Status'];
        $rows = [];

        foreach ($results as $key => $result) {
            $configured = $result['configured'] ? '✅ Yes' : '❌ No';
            $enabled = $result['enabled'] ? '✅ Yes' : '⚠️  No';
            $testSent = $result['test_sent'] ? '✅ Yes' : '❌ No';

            $status = '❌ Not Working';
            if ($result['test_sent']) {
                $status = '✅ Working';
            } elseif ($result['configured'] && $result['enabled']) {
                $status = '⚠️  Configured but test failed';
            } elseif ($result['configured'] && !$result['enabled']) {
                $status = '⚠️  Disabled in this environment';
            } else {
                $status = '❌ Not configured';
            }

            $rows[] = [
                $result['name'],
                $configured,
                $enabled,
                $testSent,
                $status,
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        // Environment information
        $this->info('Environment Information:');
        $this->line("  Current Environment: " . env('APP_ENV', 'local'));
        $this->line("  Is Production: " . (env('APP_ENV', 'local') === 'production' ? 'Yes' : 'No'));
        $this->newLine();

        // Recommendations
        $this->info('Recommendations:');
        foreach ($results as $result) {
            if (!$result['configured']) {
                $envVar = match($result['name']) {
                    'VPS Management' => 'VPS_DISCORD_WEBHOOK_URL',
                    'Tinker Discord' => 'TINKER_DISCORD_WEBHOOK_URL',
                    'SQL Management' => 'SQL_DISCORD_WEBHOOK_URL',
                    default => 'WEBHOOK_URL',
                };
                $this->line("  • {$result['name']}: Set {$envVar} in your .env file");
            } elseif (!$result['enabled']) {
                $envVar = match($result['name']) {
                    'VPS Management' => 'VPS_ONLY_ON_PRODUCTION',
                    'Tinker Discord' => 'TINKER_ONLY_ON_PRODUCTION',
                    'SQL Management' => 'SQL_DISCORD_ONLY_ON_PRODUCTION',
                    default => 'ONLY_ON_PRODUCTION',
                };
                $this->line("  • {$result['name']}: Set {$envVar}=false to enable in this environment");
            } elseif (!$result['test_sent']) {
                $this->line("  • {$result['name']}: Check webhook URL and Discord permissions");
            }
        }
    }
}

