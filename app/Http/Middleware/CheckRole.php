<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks if the user has the required permissions for the current vessel.
     * It supports legacy role names (admin, manager, administrator) which are mapped to
     * vessel-specific roles (Administrator, Supervisor) based on config/permissions.php.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$requirements): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        // Get current vessel ID from request attributes (set by EnsureVesselAccess middleware)
        $vesselId = $request->attributes->get('vessel_id');

        // Fallback to getting vessel from attributes
        if (!$vesselId) {
            $vessel = $request->attributes->get('vessel');
            if ($vessel && is_object($vessel)) {
                $vesselId = $vessel->id;
            }
        }

        if (!$vesselId) {
            abort(403, 'No vessel context available.');
        }

        // Get user's role for the vessel (returns display_name like "Administrator", "Supervisor", etc.)
        $vesselRole = $user->getRoleForVessel((int) $vesselId);

        if (!$vesselRole) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Get permissions from config based on role display name
        $allPermissions = config('permissions', []);
        $permissions = $allPermissions[$vesselRole] ?? $allPermissions['default'] ?? [];

        // Check if user satisfies ANY of the requirements (OR logic)
        // This allows routes like 'role:admin,manager' to work correctly
        $hasAccess = false;

        foreach ($requirements as $requirement) {
            // Handle legacy role-based checks (map to vessel roles)
            if ($this->checkLegacyRole($requirement, $vesselRole)) {
                $hasAccess = true;
                break; // User has access through legacy role mapping
            }

            // Handle permission-based checks (e.g., 'transactions.create')
            if (isset($permissions[$requirement]) && $permissions[$requirement] === true) {
                $hasAccess = true;
                break; // User has this permission
            }
        }

        if (!$hasAccess) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    /**
     * Check legacy role names and map them to vessel roles.
     * This provides backward compatibility with routes using 'role:admin,manager'.
     *
     * Maps legacy roles to vessel roles based on permissions:
     * - 'admin' or 'manager' -> Administrator or Supervisor (can create/edit/delete)
     * - 'administrator' -> Administrator only (full access)
     */
    private function checkLegacyRole(string $role, string $vesselRole): bool
    {
        // Map legacy role names to vessel role display names
        $roleMapping = [
            'admin' => ['Administrator', 'Supervisor'], // Admin/Manager can create/edit/delete
            'manager' => ['Administrator', 'Supervisor'], // Manager can create/edit/delete
            'administrator' => ['Administrator'], // Administrator only for full access
        ];

        // Check if role is in the map
        if (!isset($roleMapping[$role])) {
            return false;
        }

        // Check if user's vessel role matches any of the required roles
        $allowedRoles = $roleMapping[$role];
        return in_array($vesselRole, $allowedRoles);
    }
}

