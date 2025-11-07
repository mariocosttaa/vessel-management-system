<?php

namespace App\Http\Middleware;

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
        ];
    }

    /**
     * Get user permissions based on vessel role.
     */
    private function getUserPermissions($user, Request $request): array
    {
        $vesselRole = $this->getCurrentVesselRole($request);

        // Default permissions for users without vessel access
        $permissions = [
            'vessels.create' => false,
            'vessels.edit' => false,
            'vessels.delete' => false,
            'vessels.view' => false,
            'crew.create' => false,
            'crew.edit' => false,
            'crew.delete' => false,
            'crew.view' => false,
            'suppliers.create' => false,
            'suppliers.edit' => false,
            'suppliers.delete' => false,
            'suppliers.view' => false,
            'bank-accounts.create' => false,
            'bank-accounts.edit' => false,
            'bank-accounts.delete' => false,
            'bank-accounts.view' => false,
            'transactions.create' => false,
            'transactions.edit' => false,
            'transactions.delete' => false,
            'transactions.view' => false,
            'reports.access' => false,
            'settings.access' => false,
            'users.manage' => false,
        ];

        // Set permissions based on vessel role
        switch ($vesselRole) {
            case 'Administrator':
                $permissions = [
                    'vessels.create' => true,
                    'vessels.edit' => true,
                    'vessels.delete' => true,
                    'vessels.view' => true,
                    'crew.create' => true,
                    'crew.edit' => true,
                    'crew.delete' => true,
                    'crew.view' => true,
                    'suppliers.create' => true,
                    'suppliers.edit' => true,
                    'suppliers.delete' => true,
                    'suppliers.view' => true,
                    'bank-accounts.create' => true,
                    'bank-accounts.edit' => true,
                    'bank-accounts.delete' => true,
                    'bank-accounts.view' => true,
                    'transactions.create' => true,
                    'transactions.edit' => true,
                    'transactions.delete' => true,
                    'transactions.view' => true,
                    'reports.access' => true,
                    'settings.access' => true,
                    'users.manage' => true,
                ];
                break;

            case 'Supervisor':
                $permissions = [
                    'vessels.create' => false,
                    'vessels.edit' => true,
                    'vessels.delete' => false,
                    'vessels.view' => true,
                    'crew.create' => true,
                    'crew.edit' => true,
                    'crew.delete' => true,
                    'crew.view' => true,
                    'suppliers.create' => true,
                    'suppliers.edit' => true,
                    'suppliers.delete' => false,
                    'suppliers.view' => true,
                    'bank-accounts.create' => true,
                    'bank-accounts.edit' => true,
                    'bank-accounts.delete' => false,
                    'bank-accounts.view' => true,
                    'transactions.create' => true,
                    'transactions.edit' => true,
                    'transactions.delete' => false,
                    'transactions.view' => true,
                    'reports.access' => true,
                    'settings.access' => false,
                    'users.manage' => false,
                ];
                break;

            case 'Moderator':
                $permissions = [
                    'vessels.create' => false,
                    'vessels.edit' => true,
                    'vessels.delete' => false,
                    'vessels.view' => true,
                    'crew.create' => false,
                    'crew.edit' => true,
                    'crew.delete' => false,
                    'crew.view' => true,
                    'suppliers.create' => false,
                    'suppliers.edit' => true,
                    'suppliers.delete' => false,
                    'suppliers.view' => true,
                    'bank-accounts.create' => false,
                    'bank-accounts.edit' => true,
                    'bank-accounts.delete' => false,
                    'bank-accounts.view' => true,
                    'transactions.create' => false,
                    'transactions.edit' => true,
                    'transactions.delete' => false,
                    'transactions.view' => true,
                    'reports.access' => true,
                    'settings.access' => false,
                    'users.manage' => false,
                ];
                break;

            case 'Normal User':
                $permissions = [
                    'vessels.create' => false,
                    'vessels.edit' => false,
                    'vessels.delete' => false,
                    'vessels.view' => true,
                    'crew.create' => false,
                    'crew.edit' => false,
                    'crew.delete' => false,
                    'crew.view' => true,
                    'suppliers.create' => false,
                    'suppliers.edit' => false,
                    'suppliers.delete' => false,
                    'suppliers.view' => true,
                    'bank-accounts.create' => false,
                    'bank-accounts.edit' => false,
                    'bank-accounts.delete' => false,
                    'bank-accounts.view' => true,
                    'transactions.create' => false,
                    'transactions.edit' => false,
                    'transactions.delete' => false,
                    'transactions.view' => true,
                    'reports.access' => true,
                    'settings.access' => false,
                    'users.manage' => false,
                ];
                break;
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
        $vessel = $request->route('vessel');

        if (!$vessel || !$request->user()) {
            return null;
        }

        // If vessel is a model instance, get the ID
        $vesselId = is_object($vessel) ? $vessel->id : $vessel;

        if (!$request->user()->hasAccessToVessel((int) $vesselId)) {
            return null;
        }

        // If vessel is already a model, use it directly
        if (is_object($vessel)) {
            return [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'registration_number' => $vessel->registration_number,
                'status' => $vessel->status,
                'currency_code' => $vessel->currency_code,
            ];
        }

        // Otherwise, fetch the vessel
        $vesselModel = \App\Models\Vessel::find($vesselId);
        if (!$vesselModel) {
            return null;
        }

        return [
            'id' => $vesselModel->id,
            'name' => $vesselModel->name,
            'registration_number' => $vesselModel->registration_number,
            'status' => $vesselModel->status,
            'currency_code' => $vesselModel->currency_code,
        ];
    }

    /**
     * Get user's role for current vessel.
     */
    private function getCurrentVesselRole(Request $request): ?string
    {
        $vessel = $request->route('vessel');

        if (!$vessel || !$request->user()) {
            return null;
        }

        // If vessel is a model instance, get the ID
        $vesselId = is_object($vessel) ? $vessel->id : $vessel;

        return $request->user()->getRoleForVessel((int) $vesselId);
    }
}
