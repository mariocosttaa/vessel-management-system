<?php

namespace App\Providers;

use App\Actions\General\EasyHashAction;
use App\Models\User;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
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
        // Customize redirect for authenticated users to panel
        RedirectIfAuthenticated::redirectUsing(function () {
            return route('panel.index');
        });

        // Register Microsoft OAuth provider
        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });

        // Route model binding for crewMember parameter (handles hashed IDs)
        Route::bind('crewMember', function ($value) {
            Log::info('Route Model Binding: crewMember', [
                'value' => $value,
                'type' => gettype($value),
            ]);

            if (empty($value)) {
                Log::warning('Route Model Binding: crewMember - Empty value');
                abort(404, 'Crew member not found.');
            }

            // Try to decode as hashed ID - try both 'crewmember-id' and 'user-id'
            $hashTypes = ['crewmember-id', 'user-id'];
            $decoded = null;

            foreach ($hashTypes as $hashType) {
                try {
                    $decoded = EasyHashAction::decode($value, $hashType);
                    Log::info('Route Model Binding: crewMember - Decoded', [
                        'original' => $value,
                        'decoded' => $decoded,
                        'type' => $hashType,
                    ]);

                    if ($decoded && is_numeric($decoded)) {
                        $user = User::find((int) $decoded);
                        if ($user) {
                            Log::info('Route Model Binding: crewMember - Found user by hashed ID', [
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                                'hash_type' => $hashType,
                            ]);
                            return $user;
                        } else {
                            Log::warning('Route Model Binding: crewMember - User not found by hashed ID', [
                                'decoded_id' => $decoded,
                                'hash_type' => $hashType,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Route Model Binding: crewMember - Decode exception', [
                        'value' => $value,
                        'hash_type' => $hashType,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to numeric ID for backward compatibility
            if (is_numeric($value)) {
                $user = User::find((int) $value);
                if ($user) {
                    Log::info('Route Model Binding: crewMember - Found user by numeric ID', [
                        'user_id' => $user->id,
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
                'value' => $value,
                'decoded' => $decoded,
                'attempted_hash_types' => $hashTypes,
            ]);

            // Always abort with 404 if user not found - this prevents passing raw string to controller
            abort(404, 'Crew member not found.');
        });
    }
}
