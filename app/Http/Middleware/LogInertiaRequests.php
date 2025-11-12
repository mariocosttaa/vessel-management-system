<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogInertiaRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log all Inertia requests with full details
        $user = $request->user();
        $userId = $user ? $user->id : 'guest';
        $userEmail = $user ? $user->email : 'guest';

        Log::info('Inertia Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'route_params' => $request->route()?->parameters(),
            'user_id' => $userId,
            'user_email' => $userEmail,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_data' => $request->except(['password', '_token', 'password_confirmation']),
        ]);

        $response = $next($request);

        // Log response status
        if ($response->getStatusCode() >= 400) {
            Log::warning('Inertia Request Error', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
                'user_id' => $userId,
                'user_email' => $userEmail,
            ]);
        }

        return $response;
    }
}

