<?php

namespace App\Http\Middleware;

use App\Actions\General\EasyHashAction;
use App\Models\Currency;
use App\Models\VesselSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        // Use a version that changes when IDs are hashed to force cache refresh
        return md5(config('app.key') . 'hashed-ids-v1');
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'locale' => $this->getLocale($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => EasyHashAction::encode($request->user()->id, 'user-id'),
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'language' => $request->user()->language ?? 'en',
                    'vessel_role' => $this->getCurrentVesselRole($request), // Current vessel role
                    'permissions' => $this->getUserPermissions($request->user(), $request),
                    'vessels' => $this->getUserVessels($request->user()),
                    'current_vessel' => $this->getCurrentVessel($request),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'notification_delay' => $request->session()->get('notification_delay'),
                // OAuth signup modal data
                'show_signup_modal' => $request->session()->get('show_signup_modal'),
                'oauth_provider' => $request->session()->get('oauth_provider'),
                'oauth_email' => $request->session()->get('oauth_email'),
                'oauth_name' => $request->session()->get('oauth_name'),
                'active_tab' => $request->session()->get('active_tab'),
            ],
            'currencies' => \Illuminate\Support\Facades\Cache::remember('currencies_list', 3600, function () {
                return Currency::orderBy('name')->get(['code', 'name', 'symbol', 'decimal_separator'])->map(function ($currency) {
                    return [
                        'code' => $currency->code,
                        'name' => $currency->name,
                        'symbol' => $currency->symbol,
                        'decimal_separator' => $currency->decimal_separator,
                    ];
                });
            }),
        ];
    }

    /**
     * Get user permissions based on vessel role.
     * Permissions are loaded from config/permissions.php for better organization and maintainability.
     */
    private function getUserPermissions($user, Request $request): array
    {
        $vesselRole = $this->getCurrentVesselRole($request);

        // Get all permissions from config
        $allPermissions = config('permissions', []);

        // Get default permissions for users without vessel access
        $permissions = $allPermissions['default'] ?? [];

        // If user has a vessel role, load permissions from config
        if ($vesselRole && isset($allPermissions[$vesselRole])) {
            $permissions = $allPermissions[$vesselRole];
        }

        return $permissions;
    }

    /**
     * Get user's vessels with roles.
     */
    private function getUserVessels($user): array
    {
        // Eager load the vessels with the pivot data to avoid N+1
        // We need to get the role from the pivot table 'vessel_user_roles'
        // The User model has 'vesselUserRoles' relationship
        
        // Get all vessel user roles for this user with the associated vessel and role access definition
        $userVesselRoles = $user->vesselUserRoles()
            ->where('is_active', true)
            ->with(['vessel', 'vesselRoleAccess'])
            ->get();
            
        // Map by vessel_id to handle multiple roles if necessary (though usually one active role per vessel)
        $vessels = [];
        
        foreach ($userVesselRoles as $userRole) {
            $vessel = $userRole->vessel;
            if (!$vessel) continue;
            
            $vessels[] = [
                'id' => EasyHashAction::encode($vessel->id, 'vessel-id'),
                'name' => $vessel->name,
                'registration_number' => $vessel->registration_number,
                'status' => $vessel->status,
                'user_role' => $userRole->vesselRoleAccess->display_name ?? null,
            ];
        }
        
        return $vessels;
    }

    /**
     * Get current vessel information.
     */
    private function getCurrentVessel(Request $request): ?array
    {
        if (!$request->user()) {
            return null;
        }

        // First, try to get vessel from request attributes (set by EnsureVesselAccess middleware)
        $vessel = $request->attributes->get('vessel');

        // If not in attributes, try to get vessel_id from attributes (set by EnsureVesselAccess middleware)
        if (!$vessel) {
            $vesselId = $request->attributes->get('vessel_id');
            if ($vesselId) {
                // Eager load setting if we have to fetch it here
                $vessel = \App\Models\Vessel::with('setting')->find($vesselId);
            }
        }

        // If still no vessel, try route parameter (shouldn't happen with proper middleware)
        if (!$vessel) {
            $vesselParam = $request->route('vessel');
            if ($vesselParam) {
                // Use resolveRouteBinding to handle both hashed and numeric IDs
                $vessel = (new \App\Models\Vessel())->resolveRouteBinding($vesselParam);
                if ($vessel) {
                    $vessel->load('setting');
                }
            }
        }

        if (!$vessel) {
            return null;
        }

        // If vessel is a model instance, use it directly
        if (is_object($vessel)) {
            $vesselId = $vessel->id;
            if (!$request->user()->hasAccessToVessel((int) $vesselId)) {
                return null;
            }

            // Get currency from vessel_settings first, then fallback to vessel currency_code
            // Use the relation if loaded, otherwise fetch it (or use the helper which might query)
            if ($vessel->relationLoaded('setting') && $vessel->setting) {
                $currencyCode = $vessel->setting->currency_code ?? $vessel->currency_code;
            } else {
                $vesselSetting = VesselSetting::getForVessel($vesselId);
                $currencyCode = $vesselSetting->currency_code ?? $vessel->currency_code;
            }

            // Share vessel with other middleware/controllers to avoid re-fetching
            if (!$request->attributes->has('vessel')) {
                $request->attributes->set('vessel', $vessel);
                $request->attributes->set('vessel_id', $vesselId);
            }

            return [
                'id' => EasyHashAction::encode($vessel->id, 'vessel-id'),
                'name' => $vessel->name,
                'registration_number' => $vessel->registration_number,
                'status' => $vessel->status,
                'currency_code' => $currencyCode,
                'logo_url' => $vessel->logo_url,
            ];
        }

        // This shouldn't happen, but handle it just in case
        return null;
    }

    /**
     * Get user's role for current vessel.
     */
    private function getCurrentVesselRole(Request $request): ?string
    {
        if (!$request->user()) {
            return null;
        }

        // First, try to get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        $vesselId = $request->attributes->get('vessel_id');

        // If not in attributes, try to get vessel from attributes and extract ID
        if (!$vesselId) {
            $vessel = $request->attributes->get('vessel');
            if ($vessel && is_object($vessel)) {
                $vesselId = $vessel->id;
            } else {
                // Fallback to route parameter (shouldn't happen with proper middleware)
                $vesselParam = $request->route('vessel');
                if ($vesselParam) {
                    // Use resolveRouteBinding to handle both hashed and numeric IDs
                    $vessel = (new \App\Models\Vessel())->resolveRouteBinding($vesselParam);
                    if ($vessel) {
                        $vesselId = $vessel->id;
                    }
                }
            }
        }

        if (!$vesselId) {
            return null;
        }

        return $request->user()->getRoleForVessel((int) $vesselId);
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
        $locale = $request->cookie('locale', 'en');

        return in_array($locale, $supportedLocales) ? $locale : 'en';
    }
}
