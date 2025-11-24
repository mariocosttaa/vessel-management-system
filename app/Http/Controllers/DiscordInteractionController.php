<?php

namespace App\Http\Controllers;

use App\Actions\SqlAction;
use App\Actions\TinkerAction;
use App\Actions\VpsAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiscordInteractionController extends Controller
{
    /**
     * Handle Discord interactions (slash commands)
     */
    public function handle(Request $request)
    {
        // Verify Discord signature (security)
        if (!$this->verifySignature($request)) {
            return response('Unauthorized', 401);
        }

        $interaction = $request->json()->all();

        // Handle ping (Discord verification)
        if ($interaction['type'] === 1) {
            return response()->json(['type' => 1]); // PONG
        }

        // Handle application command (slash command)
        if ($interaction['type'] === 2) {
            return $this->handleCommand($interaction);
        }

        return response()->json(['type' => 4, 'data' => ['content' => 'Unknown interaction type']]);
    }

    /**
     * Handle slash command
     */
    protected function handleCommand(array $interaction): \Illuminate\Http\JsonResponse
    {
        $commandName = $interaction['data']['name'] ?? '';
        $options = $this->parseOptions($interaction['data']['options'] ?? []);

        try {
            switch ($commandName) {
                case 'vps':
                    return $this->executeVps($options['command'] ?? '', $interaction);
                case 'sql':
                    return $this->executeSql($options['query'] ?? '', $interaction);
                case 'tinker':
                    return $this->executeTinker($options['code'] ?? '', $interaction);
                default:
                    return $this->respond('❌ Comando desconhecido', true);
            }
        } catch (\Exception $e) {
            Log::error('Discord interaction error', [
                'error' => $e->getMessage(),
                'command' => $commandName,
            ]);
            return $this->respond('❌ Erro ao executar: ' . $e->getMessage(), true);
        }
    }

    /**
     * Execute VPS command
     */
    protected function executeVps(string $command, array $interaction): \Illuminate\Http\JsonResponse
    {
        if (empty($command)) {
            return $this->respond('❌ Comando vazio. Use: `/vps ls -la`', true);
        }

        if (!VpsAction::isEnabled()) {
            return $this->respond('❌ VPS management is only available in production environment.', true);
        }

        $result = VpsAction::executeCommand($command);
        
        $status = $result['exit_code'] === 0 ? '✅' : '❌';
        $output = !empty($result['output']) ? $result['output'] : '(sem output)';
        
        if (strlen($output) > 1900) {
            $output = substr($output, 0, 1900) . "\n... (truncado)";
        }

        $response = "**Comando:** `{$command}`\n";
        $response .= "**Status:** {$status} (Exit Code: {$result['exit_code']})\n";
        $response .= "**Tempo:** {$result['execution_time']}s\n\n";
        $response .= "**Output:**\n```\n{$output}\n```";

        return $this->respond($response, false);
    }

    /**
     * Execute SQL command
     */
    protected function executeSql(string $query, array $interaction): \Illuminate\Http\JsonResponse
    {
        if (empty($query)) {
            return $this->respond('❌ Query vazia. Use: `/sql SELECT * FROM users LIMIT 10`', true);
        }

        if (!SqlAction::isDiscordEnabled()) {
            return $this->respond('❌ SQL management is only available in production environment.', true);
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

        if (strlen($output) > 1900) {
            $output = substr($output, 0, 1900) . "\n... (truncado)";
        }

        $response = "**Query:**\n```sql\n{$query}\n```\n";
        $response .= "**Status:** {$status}\n";
        $response .= "**Tempo:** {$result['execution_time']}s\n\n";
        $response .= "**Resultado:**\n{$output}";

        return $this->respond($response, false);
    }

    /**
     * Execute Tinker command
     */
    protected function executeTinker(string $code, array $interaction): \Illuminate\Http\JsonResponse
    {
        if (empty($code)) {
            return $this->respond('❌ Código vazio. Use: `/tinker User::count()`', true);
        }

        if (!TinkerAction::isEnabled()) {
            return $this->respond('❌ Tinker is only available in production environment.', true);
        }

        $result = TinkerAction::executeCode($code);
        
        $status = $result['has_error'] ? '❌' : '✅';
        $output = !empty($result['output']) ? $result['output'] : '(sem output)';
        
        if (strlen($output) > 1900) {
            $output = substr($output, 0, 1900) . "\n... (truncado)";
        }

        $response = "**Código:**\n```php\n{$code}\n```\n";
        $response .= "**Status:** {$status}\n";
        $response .= "**Tempo:** {$result['execution_time']}s\n\n";
        $response .= "**Output:**\n```\n{$output}\n```";

        return $this->respond($response, false);
    }

    /**
     * Parse command options
     */
    protected function parseOptions(array $options): array
    {
        $parsed = [];
        foreach ($options as $option) {
            $parsed[$option['name']] = $option['value'] ?? '';
        }
        return $parsed;
    }

    /**
     * Respond to Discord interaction
     */
    protected function respond(string $content, bool $ephemeral = false): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'type' => 4, // CHANNEL_MESSAGE_WITH_SOURCE
            'data' => [
                'content' => $content,
                'flags' => $ephemeral ? 64 : 0, // EPHEMERAL flag
            ],
        ]);
    }

    /**
     * Verify Discord request signature (Ed25519)
     * 
     * Discord signs all interaction requests with Ed25519.
     * This prevents unauthorized requests to your endpoint.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Signature-Ed25519');
        $timestamp = $request->header('X-Signature-Timestamp');
        $body = $request->getContent();

        if (!$signature || !$timestamp) {
            Log::warning('Discord interaction missing signature headers');
            return false;
        }

        $publicKey = env('DISCORD_PUBLIC_KEY', '');
        
        // In development, allow without verification for easier testing
        if (app()->environment('local') && empty($publicKey)) {
            Log::warning('Discord signature verification skipped in local environment');
            return true;
        }

        if (empty($publicKey)) {
            Log::error('DISCORD_PUBLIC_KEY not configured - rejecting request for security');
            return false;
        }

        // Verify signature using Ed25519 (sodium extension)
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            Log::error('sodium extension not available - cannot verify Discord signature');
            // In production, we should reject, but for now allow if sodium is not available
            // You should install sodium extension: apt-get install php-sodium
            return app()->environment('local');
        }

        // Discord signature format: hex-encoded Ed25519 signature
        $signatureBinary = hex2bin($signature);
        $publicKeyBinary = hex2bin($publicKey);
        
        // Discord signs: timestamp + body
        $message = $timestamp . $body;

        // Verify the signature
        $isValid = sodium_crypto_sign_verify_detached($signatureBinary, $message, $publicKeyBinary);

        if (!$isValid) {
            Log::warning('Discord interaction signature verification failed', [
                'timestamp' => $timestamp,
                'body_length' => strlen($body),
            ]);
        }

        return $isValid;
    }
}

