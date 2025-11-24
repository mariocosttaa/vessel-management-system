<?php

namespace App\Console\Commands;

use App\Services\DiscordBotService;
use Illuminate\Console\Command;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;
use React\Socket\Connector as SocketConnector;

class DiscordBotCommand extends Command
{
    protected $signature = 'discord:bot
                            {--daemon : Run as daemon process}';

    protected $description = 'Run Discord bot to listen for commands in channels';

    protected DiscordBotService $botService;
    protected $loop;
    protected $ws;
    protected int $sequence = 0;
    protected ?string $sessionId = null;
    protected bool $connected = false;
    protected int $heartbeatInterval = 0;
    protected $heartbeatTimer = null;

    public function handle(): int
    {
        $this->botService = new DiscordBotService();

        if (!$this->botService->isConfigured()) {
            $this->error('Discord bot is not configured.');
            $this->info('Please set the following environment variables:');
            $this->info('  - DISCORD_BOT_TOKEN');
            $this->info('  - DISCORD_VPS_CHANNEL_ID (optional)');
            $this->info('  - DISCORD_SQL_CHANNEL_ID (optional)');
            $this->info('  - DISCORD_TINKER_CHANNEL_ID (optional)');
            return Command::FAILURE;
        }

        $this->info('Starting Discord bot...');
        $this->info('Press Ctrl+C to stop.');

        $this->loop = Loop::get();
        $this->connect();

        return Command::SUCCESS;
    }

    protected function connect(): void
    {
        $gatewayUrl = $this->botService->getGatewayUrl();
        
        if (empty($gatewayUrl)) {
            $this->error('Failed to get Discord gateway URL');
            return;
        }

        $this->info("Connecting to Discord Gateway: {$gatewayUrl}");

        $connector = new Connector($this->loop);
        $connector($gatewayUrl . '/?v=10&encoding=json')
            ->then(function (WebSocket $conn) {
                $this->ws = $conn;
                $this->info('Connected to Discord Gateway');

                $conn->on('message', function ($msg) {
                    $this->handleMessage($msg);
                });

                $conn->on('close', function ($code = null, $reason = null) {
                    $this->warn("Connection closed: {$code} - {$reason}");
                    $this->connected = false;
                    
                    // Reconnect after 5 seconds
                    $this->loop->addTimer(5, function () {
                        $this->connect();
                    });
                });

                $conn->on('error', function ($error) {
                    $this->error("WebSocket error: {$error}");
                });
            }, function (\Exception $e) {
                $this->error("Connection failed: {$e->getMessage()}");
                
                // Retry after 5 seconds
                $this->loop->addTimer(5, function () {
                    $this->connect();
                });
            });

        $this->loop->run();
    }

    protected function handleMessage($msg): void
    {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        $op = $data['op'] ?? null;
        $event = $data['t'] ?? null;
        $payload = $data['d'] ?? null;

        switch ($op) {
            case 10: // Hello
                $this->handleHello($payload);
                break;
            case 11: // Heartbeat ACK
                // Heartbeat acknowledged
                break;
            case 0: // Dispatch
                $this->handleDispatch($event, $payload);
                break;
            case 7: // Reconnect
                $this->warn('Discord requested reconnect');
                $this->ws->close();
                break;
            case 9: // Invalid Session
                $this->warn('Invalid session, reconnecting...');
                $this->sessionId = null;
                $this->sequence = 0;
                $this->loop->addTimer(2, function () {
                    $this->connect();
                });
                break;
        }

        // Update sequence number
        if (isset($data['s']) && $data['s'] !== null) {
            $this->sequence = $data['s'];
        }
    }

    protected function handleHello(array $payload): void
    {
        $this->heartbeatInterval = $payload['heartbeat_interval'] ?? 41250;
        
        // Send heartbeat immediately
        $this->sendHeartbeat();
        
        // Schedule periodic heartbeats
        if ($this->heartbeatTimer) {
            $this->loop->cancelTimer($this->heartbeatTimer);
        }
        
        $this->heartbeatTimer = $this->loop->addPeriodicTimer($this->heartbeatInterval / 1000, function () {
            $this->sendHeartbeat();
        });

        // Send Identify or Resume
        if ($this->sessionId) {
            $this->sendResume();
        } else {
            $this->sendIdentify();
        }
    }

    protected function sendIdentify(): void
    {
        $token = env('DISCORD_BOT_TOKEN');
        
        $payload = [
            'op' => 2, // Identify
            'd' => [
                'token' => $token,
                'intents' => 513, // GUILD_MESSAGES (512) + MESSAGE_CONTENT (1)
                'properties' => [
                    '$os' => PHP_OS,
                    '$browser' => 'Laravel Discord Bot',
                    '$device' => 'Laravel Discord Bot',
                ],
            ],
        ];

        $this->send($payload);
        $this->info('Sent Identify');
    }

    protected function sendResume(): void
    {
        $token = env('DISCORD_BOT_TOKEN');
        
        $payload = [
            'op' => 6, // Resume
            'd' => [
                'token' => $token,
                'session_id' => $this->sessionId,
                'seq' => $this->sequence,
            ],
        ];

        $this->send($payload);
        $this->info('Sent Resume');
    }

    protected function sendHeartbeat(): void
    {
        $payload = [
            'op' => 1, // Heartbeat
            'd' => $this->sequence,
        ];

        $this->send($payload);
    }

    protected function handleDispatch(string $event, ?array $payload): void
    {
        switch ($event) {
            case 'READY':
                $this->handleReady($payload);
                break;
            case 'MESSAGE_CREATE':
                $this->handleMessageCreate($payload);
                break;
            case 'RESUMED':
                $this->info('Session resumed');
                $this->connected = true;
                break;
        }
    }

    protected function handleReady(?array $payload): void
    {
        if ($payload) {
            $this->sessionId = $payload['session_id'] ?? null;
            $user = $payload['user'] ?? [];
            $username = $user['username'] ?? 'Unknown';
            $this->info("Bot ready! Logged in as: {$username}");
            $this->connected = true;
        }
    }

    protected function handleMessageCreate(?array $payload): void
    {
        if (!$payload) {
            return;
        }

        // Process message in background to avoid blocking
        $this->loop->futureTick(function () use ($payload) {
            try {
                $this->botService->processMessage($payload);
            } catch (\Exception $e) {
                $this->error("Error processing message: {$e->getMessage()}");
            }
        });
    }

    protected function send(array $data): void
    {
        if ($this->ws) {
            $this->ws->send(json_encode($data));
        }
    }
}

