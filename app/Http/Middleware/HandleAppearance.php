<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance') ?? 'system';
        View::share('appearance', $appearance);

        // Also handle locale for error pages and Blade views
        $locale = $this->getLocale($request);
        View::share('locale', $locale);

        // Set the application locale
        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Get the current locale from user preference, cookie, or default to 'en'.
     */
    private function getLocale(Request $request): string
    {
        $supportedLocales = ['en', 'pt', 'es', 'fr'];

        // First, try to get from user's saved preference
        if ($request->user() && $request->user()->language) {
            $userLocale = $request->user()->language;
            if (in_array($userLocale, $supportedLocales)) {
                return $userLocale;
            }
        }

        // Fallback to cookie
        $locale = $request->cookie('locale', config('app.locale', 'en'));

        return in_array($locale, $supportedLocales) ? $locale : 'en';
    }
}
