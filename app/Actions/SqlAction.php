<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SqlAction
{
    /**
     * Execute a SQL query and return results
     *
     * @param string $query The SQL query to execute
     * @param int $timeout Query timeout in seconds (default: 30)
     * @return array ['success' => bool, 'data' => array, 'rows' => int, 'execution_time' => float, 'error' => string|null]
     */
    public static function executeQuery(string $query, int $timeout = 30): array
    {
        $startTime = microtime(true);
        $query = trim($query);

        // Security: Only allow SELECT queries by default
        $queryUpper = strtoupper(trim($query));
        $isSelect = str_starts_with($queryUpper, 'SELECT');
        $isShow = str_starts_with($queryUpper, 'SHOW') || str_starts_with($queryUpper, 'DESCRIBE') || str_starts_with($queryUpper, 'DESC');
        $isExplain = str_starts_with($queryUpper, 'EXPLAIN');

        // Check if non-SELECT queries are allowed
        $allowNonSelect = env('SQL_ALLOW_NON_SELECT', 'false') === 'true';

        if (!$isSelect && !$isShow && !$isExplain) {
            if (!$allowNonSelect) {
                return [
                    'success' => false,
                    'data' => [],
                    'rows' => 0,
                    'execution_time' => 0.0,
                    'error' => 'Only SELECT, SHOW, DESCRIBE, and EXPLAIN queries are allowed. Set SQL_ALLOW_NON_SELECT=true to allow other queries.',
                ];
            }
        }

        try {
            // Set query timeout (MySQL/MariaDB)
            try {
                DB::statement("SET SESSION max_execution_time = {$timeout}000"); // Convert to milliseconds for MySQL
            } catch (\Exception $e) {
                // Ignore if timeout setting is not supported (e.g., SQLite, PostgreSQL)
            }

            // Execute query
            if ($isSelect || $isShow || $isExplain) {
                $results = DB::select($query);
                $rows = count($results);

                // Convert results to array format
                $data = array_map(function ($row) {
                    return (array) $row;
                }, $results);
            } else {
                // For non-SELECT queries (INSERT, UPDATE, DELETE, etc.)
                $affected = DB::affectingStatement($query);
                $rows = $affected;
                $data = ['affected_rows' => $rows];
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'data' => $data,
                'rows' => $rows,
                'execution_time' => $executionTime,
                'error' => null,
            ];
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);

            return [
                'success' => false,
                'data' => [],
                'rows' => 0,
                'execution_time' => $executionTime,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SQL query execution result to Discord webhook (production only)
     *
     * @param string $query The SQL query that was executed
     * @param array $result The query result array
     * @return bool Success status
     */
    public static function sendToDiscord(string $query, array $result): bool
    {
        // Check if SQL Discord logging is enabled
        if (!self::isDiscordEnabled()) {
            return false;
        }

        $webhookUrl = env('SQL_DISCORD_WEBHOOK_URL', '');

        if (empty($webhookUrl)) {
            Log::warning('SQL Discord webhook URL not configured');
            return false;
        }

        try {
            $status = $result['success'] ? '✅ Success' : '❌ Error';
            $color = $result['success'] ? 0x00ff00 : 0xff0000; // Green for success, red for error

            // Format query for display - ensure it's always visible
            $maxQueryLength = 1000;
            $queryDisplay = !empty(trim($query))
                ? (strlen($query) > $maxQueryLength
                    ? substr($query, 0, $maxQueryLength) . "\n... (truncated)"
                    : $query)
                : '(no query specified)';

            // Format results
            $resultDisplay = '';
            if ($result['success']) {
                if ($result['rows'] > 0) {
                    // Show first few rows as preview
                    $previewRows = array_slice($result['data'], 0, 5);
                    $resultDisplay = "Rows: {$result['rows']}\n\n";
                    $resultDisplay .= "Preview (first " . min(5, $result['rows']) . " rows):\n";
                    $resultDisplay .= "```json\n" . json_encode($previewRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";

                    if ($result['rows'] > 5) {
                        $resultDisplay .= "\n... and " . ($result['rows'] - 5) . " more rows";
                    }
                } else {
                    $resultDisplay = "Query executed successfully (0 rows returned)";
                }
            } else {
                $resultDisplay = "Error: " . ($result['error'] ?? 'Unknown error');
            }

            // Truncate result display if too long
            $maxResultLength = 1800;
            if (strlen($resultDisplay) > $maxResultLength) {
                $resultDisplay = substr($resultDisplay, 0, $maxResultLength) . "\n... (truncated)";
            }

            $embed = [
                'title' => 'SQL Query Execution',
                'description' => "**Status:** {$status}\n**Rows:** `{$result['rows']}`\n**Execution Time:** `{$result['execution_time']}s`",
                'color' => $color,
                'timestamp' => now()->toIso8601String(),
                'fields' => [
                    [
                        'name' => 'Query',
                        'value' => "```sql\n{$queryDisplay}\n```",
                        'inline' => false,
                    ],
                    [
                        'name' => 'Result',
                        'value' => $resultDisplay,
                        'inline' => false,
                    ],
                ],
                'footer' => [
                    'text' => env('APP_NAME', 'Laravel App') . ' - SQL Manager',
                ],
            ];

            $payload = [
                'embeds' => [$embed],
            ];

            $username = env('SQL_DISCORD_WEBHOOK_USERNAME', 'sql-manager');
            if ($username) {
                $payload['username'] = $username;
            }

            $avatarUrl = env('SQL_DISCORD_WEBHOOK_AVATAR_URL', '');
            if ($avatarUrl) {
                $payload['avatar_url'] = $avatarUrl;
            }

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error('SQL Discord webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('SQL Discord webhook exception', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if SQL Discord logging is enabled (production only by default)
     */
    public static function isDiscordEnabled(): bool
    {
        $onlyProduction = env('SQL_DISCORD_ONLY_ON_PRODUCTION', 'true') === 'true' || env('SQL_DISCORD_ONLY_ON_PRODUCTION', true) === true;
        $isProduction = env('APP_ENV', 'local') === 'production';

        // If onlyProduction is true, only allow in production
        // If onlyProduction is false, allow in all environments
        if ($onlyProduction && !$isProduction) {
            return false;
        }

        return true;
    }

    /**
     * Format query results as a table for console output
     *
     * @param array $data Query results
     * @return array ['headers' => array, 'rows' => array]
     */
    public static function formatAsTable(array $data): array
    {
        if (empty($data)) {
            return ['headers' => [], 'rows' => []];
        }

        // Get headers from first row
        $headers = array_keys($data[0]);

        // Extract rows
        $rows = array_map(function ($row) use ($headers) {
            return array_map(function ($header) use ($row) {
                $value = $row[$header] ?? null;

                // Format values for display
                if (is_null($value)) {
                    return '<null>';
                }

                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                if (is_array($value) || is_object($value)) {
                    return json_encode($value, JSON_UNESCAPED_SLASHES);
                }

                // Truncate long strings
                $str = (string) $value;
                if (strlen($str) > 100) {
                    return substr($str, 0, 97) . '...';
                }

                return $str;
            }, $headers);
        }, $data);

        return ['headers' => $headers, 'rows' => $rows];
    }
}

