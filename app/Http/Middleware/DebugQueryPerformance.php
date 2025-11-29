<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugQueryPerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if query performance logging is enabled
        if (!env('ENABLE_QUERY_PERFORMANCE_LOGGING', false)) {
            return $next($request);
        }

        // Enable query logging
        DB::enableQueryLog();
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $queries = DB::getQueryLog();
        $totalQueries = count($queries);
        $totalQueryTime = collect($queries)->sum('time');

        // Group queries to find duplicates
        $groupedQueries = collect($queries)->groupBy(function ($query) {
            return $query['query'];
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'time_total' => $group->sum('time'),
                'query' => $group->first()['query'],
                'bindings' => $group->first()['bindings'],
                // Capture stack trace for the first occurrence if possible
                // Note: DB::getQueryLog() doesn't include stack trace by default.
                // To get stack traces, we'd need to listen to DB events, but for now
                // we'll just identify WHICH queries are duplicated.
            ];
        })->filter(function ($group) {
            return $group['count'] > 1;
        })->sortByDesc('count');

        // Log report
        $logMessage = "
================================================================================
QUERY PERFORMANCE REPORT
URL: {$request->fullUrl()}
Method: {$request->method()}
Total Queries: {$totalQueries}
Total Query Time: {$totalQueryTime}ms
Total Request Time: {$duration}ms
Duplicate Queries: {$groupedQueries->count()}
================================================================================
";

        if ($groupedQueries->isNotEmpty()) {
            $logMessage .= "DUPLICATE QUERIES FOUND:\n";
            foreach ($groupedQueries as $group) {
                $logMessage .= "--------------------------------------------------------------------------------\n";
                $logMessage .= "Count: {$group['count']} | Total Time: {$group['time_total']}ms\n";
                $logMessage .= "SQL: {$group['query']}\n";
                // $logMessage .= "Bindings: " . json_encode($group['bindings']) . "\n";
            }
        } else {
            $logMessage .= "No duplicate queries found.\n";
        }

        // Write to specific log file
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/query_performance.log'),
        ])->info($logMessage);

        return $response;
    }
}
