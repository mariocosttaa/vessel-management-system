<?php

namespace App\Http\Middleware;

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
        return parent::version($request);
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
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
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
            ],
            'currencies' => Currency::orderBy('name')->get(['code', 'name', 'symbol', 'decimal_separator'])->map(function ($currency) {
                return [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'decimal_separator' => $currency->decimal_separator,
                ];
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
        return $user->vessels()->get()->map(function ($vessel) use ($user) {
            return [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'registration_number' => $vessel->registration_number,
                'status' => $vessel->status,
                'user_role' => $user->getRoleForVessel($vessel->id),
            ];
        })->toArray();
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

        // If not in attributes, try to get from route parameter
        if (!$vessel) {
            $vessel = $request->route('vessel');
            if (!$vessel) {
                return null;
            }
        }

        // If vessel is a model instance, use it directly
        if (is_object($vessel)) {
            $vesselId = $vessel->id;
            if (!$request->user()->hasAccessToVessel((int) $vesselId)) {
                return null;
            }

            // Get currency from vessel_settings first, then fallback to vessel currency_code
            $vesselSetting = VesselSetting::getForVessel($vesselId);
            $currencyCode = $vesselSetting->currency_code ?? $vessel->currency_code;

            return [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'registration_number' => $vessel->registration_number,
                'status' => $vessel->status,
                'currency_code' => $currencyCode,
            ];
        }

        // If vessel is an ID, fetch the vessel model
        $vesselId = (int) $vessel;
        if (!$request->user()->hasAccessToVessel($vesselId)) {
            return null;
        }

        $vesselModel = \App\Models\Vessel::find($vesselId);
        if (!$vesselModel) {
            return null;
        }

        // Get currency from vessel_settings first, then fallback to vessel currency_code
        $vesselSetting = VesselSetting::getForVessel($vesselId);
        $currencyCode = $vesselSetting->currency_code ?? $vesselModel->currency_code;

        return [
            'id' => $vesselModel->id,
            'name' => $vesselModel->name,
            'registration_number' => $vesselModel->registration_number,
            'status' => $vesselModel->status,
            'currency_code' => $currencyCode,
        ];
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

        // If not in attributes, try to get from route parameter
        if (!$vesselId) {
            $vessel = $request->route('vessel');
            if (!$vessel) {
                return null;
            }
            // If vessel is a model instance, get the ID
            $vesselId = is_object($vessel) ? $vessel->id : $vessel;
        }

        if (!$vesselId) {
            return null;
        }

        return $request->user()->getRoleForVessel((int) $vesselId);
    }
}
