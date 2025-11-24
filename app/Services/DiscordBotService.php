<?php

namespace App\Services;

use App\Actions\SqlAction;
use App\Actions\TinkerAction;
use App\Actions\VpsAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordBotService
{
    protected string $token;
    protected ?string $guildId;
    protected array $channelMappings;

    public function __construct()
    {
        $this->token = env('DISCORD_BOT_TOKEN', '');
        $this->guildId = env('DISCORD_GUILD_ID', null);
        
        // Map channel IDs to command types
        $this->channelMappings = [
            env('DISCORD_VPS_CHANNEL_ID', '') => 'vps',
            env('DISCORD_SQL_CHANNEL_ID', '') => 'sql',
            env('DISCORD_TINKER_CHANNEL_ID', '') => 'tinker',
        ];
    }

    /**
     * Process a message from Discord
     */
    public function processMessage(array $message): void
    {
        $channelId = $message['channel_id'] ?? '';
        $content = trim($message['content'] ?? '');
        $authorId = $message['author']['id'] ?? '';
        $messageId = $message['id'] ?? '';

        // Ignore bot messages
        if (isset($message['author']['bot']) && $message['author']['bot']) {
            return;
        }

        // Ignore empty messages
        if (empty($content)) {
            return;
        }

        // Determine command type based on channel
        $commandType = $this->channelMappings[$channelId] ?? null;
        
        if (!$commandType) {
            return; // Not a monitored channel
        }

        // Execute command based on type
        try {
            switch ($commandType) {
                case 'vps':
                    $this->executeVpsCommand($channelId, $content, $messageId);
                    break;
                case 'sql':
                    $this->executeSqlCommand($channelId, $content, $messageId);
                    break;
                case 'tinker':
                    $this->executeTinkerCommand($channelId, $content, $messageId);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Discord bot command execution failed', [
                'error' => $e->getMessage(),
                'command_type' => $commandType,
                'content' => $content,
            ]);
            
            $this->sendMessage($channelId, "❌ Erro ao executar comando: " . $e->getMessage());
        }
    }

    /**
     * Execute VPS command
     */
    protected function executeVpsCommand(string $channelId, string $command, string $originalMessageId): void
    {
        if (!VpsAction::isEnabled()) {
            $this->sendMessage($channelId, "❌ VPS management is only available in production environment.");
            return;
        }

        $result = VpsAction::executeCommand($command);
        
        $status = $result['exit_code'] === 0 ? '✅' : '❌';
        $output = !empty($result['output']) ? $result['output'] : '(sem output)';
        
        // Truncate if too long
        if (strlen($output) > 1900) {
            $output = substr($output, 0, 1900) . "\n... (truncado)";
        }

        $response = "**Comando:** `{$command}`\n";
        $response .= "**Status:** {$status} (Exit Code: {$result['exit_code']})\n";
        $response .= "**Tempo:** {$result['execution_time']}s\n\n";
        $response .= "**Output:**\n```\n{$output}\n```";

        $this->sendMessage($channelId, $response);
    }

    /**
     * Execute SQL command
     */
    protected function executeSqlCommand(string $channelId, string $query, string $originalMessageId): void
    {
        if (!SqlAction::isDiscordEnabled()) {
            $this->sendMessage($channelId, "❌ SQL management is only available in production environment.");
            return;
        }

        $result = SqlAction::executeQuery($query);
        
        if ($result['success']) {
            $status = '✅';
            $output = "Linhas: {$result['rows']}\n\n";
            
            if ($result['rows'] > 0) {
                $preview = array_slice($result['data'], 0, 10);
                $output .= "Preview (primeiras " . min(10, $result['rows']) . " linhas):\n";
                $output .= "```json\n" . json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n```";
                
                if ($result['rows'] > 10) {
                    $output .= "\n... e mais " . ($result['rows'] - 10) . " linhas";
                }
            } else {
                $output .= "Query executada com sucesso (0 linhas retornadas)";
            }
        } else {
            $status = '❌';
            $output = "Erro: " . ($result['error'] ?? 'Erro desconhecido');
        }

        // Truncate if too long
        if (strlen($output) > 1900) {
            $output = substr($output, 0, 1900) . "\n... (truncado)";
        }

        $response = "**Query:**\n```sql\n{$query}\n```\n";
        $response .= "**Status:** {$status}\n";
        $response .= "**Tempo:** {$result['execution_time']}s\n\n";
        $response .= "**Resultado:**\n{$output}";

        $this->sendMessage($channelId, $response);
    }

    /**
     * Execute Tinker command
     */
    protected function executeTinkerCommand(string $channelId, string $code, string $originalMessageId): void
    {
        if (!TinkerAction::isEnabled()) {
            $this->sendMessage($channelId, "❌ Tinker is only available in production environment.");
            return;
        }

        $result = TinkerAction::executeCode($code);
        
        $status = $result['has_error'] ? '❌' : '✅';
        $output = !empty($result['output']) ? $result['output'] : '(sem output)';
        
        // Truncate if too long
        if (strlen($output) > 1900) {
            $output = substr($output, 0, 1900) . "\n... (truncado)";
        }

        $response = "**Código:**\n```php\n{$code}\n```\n";
        $response .= "**Status:** {$status}\n";
        $response .= "**Tempo:** {$result['execution_time']}s\n\n";
        $response .= "**Output:**\n```\n{$output}\n```";

        $this->sendMessage($channelId, $response);
    }

    /**
     * Send message to Discord channel
     */
    protected function sendMessage(string $channelId, string $content): void
    {
        if (empty($this->token)) {
            Log::warning('Discord bot token not configured');
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bot {$this->token}",
                'Content-Type' => 'application/json',
            ])->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                'content' => $content,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Discord message', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Discord message send exception', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get gateway URL for WebSocket connection
     */
    public function getGatewayUrl(): string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->token}",
        ])->get('https://discord.com/api/v10/gateway/bot');

        if ($response->successful()) {
            $data = $response->json();
            return $data['url'] ?? '';
        }

        return '';
    }

    /**
     * Check if bot is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty(array_filter($this->channelMappings));
    }
}

