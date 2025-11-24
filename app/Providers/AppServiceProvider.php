<?php
namespace App\Providers;

use App\Actions\General\EasyHashAction;
use App\Logging\DiscordWebhookHandler;
use App\Models\CrewPosition;
use App\Models\User;
use App\Observers\CrewPositionObserver;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URLs in production or when request is HTTPS
        // This ensures Inertia.js and all URL generation uses HTTPS
        if (app()->environment('production')) {
            // In production, always force HTTPS
            URL::forceScheme('https');
        } elseif (config('app.url') && str_starts_with(config('app.url'), 'https://')) {
            // In other environments, force HTTPS if APP_URL is HTTPS
            URL::forceScheme('https');
        } elseif (request()->secure() || request()->header('X-Forwarded-Proto') === 'https') {
            // If request is HTTPS (even behind proxy), force HTTPS URLs
            URL::forceScheme('https');
        }

        // Register observers
        CrewPosition::observe(CrewPositionObserver::class);

        // Register Discord logging channels
        // Only restrict registration if discord_logs_only_on_production is true and not in production
        $onlyProduction = config('logging.discord_logs_only_on_production', false);
        $isProduction = app()->environment('production');

        // Register if: not restricted OR (restricted AND in production)
        if (!($onlyProduction && !$isProduction)) {
            $this->registerDiscordLogChannels();
        }

        // Customize redirect for authenticated users to panel
        RedirectIfAuthenticated::redirectUsing(function () {
            return route('panel.index');
        });

        // Register Microsoft OAuth provider
        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });

        // Rate limiter for Excel exports (5 requests per minute per user)
        RateLimiter::for('excel-export', function (Request $request) {
            $user = $request->user();
            $key  = $user ? 'excel-export-user-' . $user->id : 'excel-export-ip-' . $request->ip();
            return Limit::perMinute(5)->by($key);
        });

        // Route model binding for crewPosition parameter (handles hashed IDs)
        Route::bind('crewPosition', function ($value) {
            Log::info('Route Model Binding: crewPosition', [
                'value' => $value,
                'type'  => gettype($value),
            ]);

            if (empty($value)) {
                Log::warning('Route Model Binding: crewPosition - Empty value');
                abort(404, 'Crew position not found.');
            }

            // Try to decode as hashed ID - use 'crewposition-id'
            $hashType = 'crewposition-id';
            $decoded  = null;

            try {
                $decoded = EasyHashAction::decode($value, $hashType);
                Log::info('Route Model Binding: crewPosition - Decoded', [
                    'original' => $value,
                    'decoded'  => $decoded,
                    'type'     => $hashType,
                ]);

                if ($decoded && is_numeric($decoded)) {
                    $position = CrewPosition::find((int) $decoded);
                    if ($position) {
                        Log::info('Route Model Binding: crewPosition - Found position by hashed ID', [
                            'position_id'   => $position->id,
                            'position_name' => $position->name,
                            'hash_type'      => $hashType,
                        ]);
                        return $position;
                    } else {
                        Log::warning('Route Model Binding: crewPosition - Position not found by hashed ID', [
                            'decoded_id' => $decoded,
                            'hash_type'  => $hashType,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Route Model Binding: crewPosition - Decode failed', [
                    'value'     => $value,
                    'hash_type' => $hashType,
                    'error'     => $e->getMessage(),
                ]);
            }

            // Fallback to numeric ID for backward compatibility
            if (is_numeric($value)) {
                $position = CrewPosition::find((int) $value);
                if ($position) {
                    Log::info('Route Model Binding: crewPosition - Found position by numeric ID', [
                        'position_id'   => $position->id,
                        'position_name' => $position->name,
                    ]);
                    return $position;
                } else {
                    Log::warning('Route Model Binding: crewPosition - Position not found by numeric ID', [
                        'numeric_id' => $value,
                    ]);
                }
            }

            // If we get here, we couldn't find the position
            Log::error('Route Model Binding: crewPosition - Position not found', [
                'value'                => $value,
                'decoded'              => $decoded,
                'attempted_hash_type'  => $hashType,
            ]);

            // Always abort with 404 if position not found - this prevents passing raw string to controller
            abort(404, 'Crew position not found.');
        });

        // View composer for email views to set locale for translations
        View::composer('emails.*', function ($view) {
            $data = $view->getData();
            if (isset($data['locale'])) {
                App::setLocale($data['locale']);
            }
        });

        // Route model binding for crewMember parameter (handles hashed IDs)
        Route::bind('crewMember', function ($value) {
            Log::info('Route Model Binding: crewMember', [
                'value' => $value,
                'type'  => gettype($value),
            ]);

            if (empty($value)) {
                Log::warning('Route Model Binding: crewMember - Empty value');
                abort(404, 'Crew member not found.');
            }

            // Try to decode as hashed ID - try both 'crewmember-id' and 'user-id'
            $hashTypes = ['crewmember-id', 'user-id'];
            $decoded   = null;

            foreach ($hashTypes as $hashType) {
                try {
                    $decoded = EasyHashAction::decode($value, $hashType);
                    Log::info('Route Model Binding: crewMember - Decoded', [
                        'original' => $value,
                        'decoded'  => $decoded,
                        'type'     => $hashType,
                    ]);

                    if ($decoded && is_numeric($decoded)) {
                        $user = User::find((int) $decoded);
                        if ($user) {
                            Log::info('Route Model Binding: crewMember - Found user by hashed ID', [
                                'user_id'   => $user->id,
                                'user_name' => $user->name,
                                'hash_type' => $hashType,
                            ]);
                            return $user;
                        } else {
                            Log::warning('Route Model Binding: crewMember - User not found by hashed ID', [
                                'decoded_id' => $decoded,
                                'hash_type'  => $hashType,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Route Model Binding: crewMember - Decode exception', [
                        'value'     => $value,
                        'hash_type' => $hashType,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to numeric ID for backward compatibility
            if (is_numeric($value)) {
                $user = User::find((int) $value);
                if ($user) {
                    Log::info('Route Model Binding: crewMember - Found user by numeric ID', [
                        'user_id'   => $user->id,
                        'user_name' => $user->name,
                    ]);
                    return $user;
                } else {
                    Log::warning('Route Model Binding: crewMember - User not found by numeric ID', [
                        'numeric_id' => $value,
                    ]);
                }
            }

            // If we get here, we couldn't find the user
            Log::error('Route Model Binding: crewMember - User not found', [
                'value'                => $value,
                'decoded'              => $decoded,
                'attempted_hash_types' => $hashTypes,
            ]);

            // Always abort with 404 if user not found - this prevents passing raw string to controller
            abort(404, 'Crew member not found.');
        });
    }

    /**
     * Register custom Discord logging channels
     */
    protected function registerDiscordLogChannels(): void
    {
        // Register discord channel
        Log::extend('discord', function ($app, array $config) {
            $handler = new DiscordWebhookHandler($config['handler_with'] ?? []);
            $handler->setLevel(Level::fromName($config['level'] ?? 'info'));

            $logger = new Logger('discord');
            $logger->pushHandler($handler);
            $logger->pushProcessor(new PsrLogMessageProcessor());

            return $logger;
        });

        // Register discord-errors channel
        Log::extend('discord-errors', function ($app, array $config) {
            $handler = new DiscordWebhookHandler($config['handler_with'] ?? []);
            $handler->setLevel(Level::fromName($config['level'] ?? 'error'));

            $logger = new Logger('discord-errors');
            $logger->pushHandler($handler);
            $logger->pushProcessor(new PsrLogMessageProcessor());

            return $logger;
        });

        // Register discord-critical channel
        Log::extend('discord-critical', function ($app, array $config) {
            $handler = new DiscordWebhookHandler($config['handler_with'] ?? []);
            $handler->setLevel(Level::fromName($config['level'] ?? 'critical'));

            $logger = new Logger('discord-critical');
            $logger->pushHandler($handler);
            $logger->pushProcessor(new PsrLogMessageProcessor());

            return $logger;
        });
    }
}
