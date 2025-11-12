<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vessel;

abstract class BaseController extends Controller
{
    /**
     * Get the current vessel from request attributes (set by EnsureVesselAccess middleware).
     * This is the preferred method as it uses the vessel already validated and loaded by middleware.
     */
    protected function getCurrentVessel(Request $request = null): Vessel
    {
        $request = $request ?? request();

        // Get vessel from request attributes (set by EnsureVesselAccess middleware)
        $vessel = $request->attributes->get('vessel');

        if (!$vessel) {
            // Fallback to route parameter if attributes not set (shouldn't happen with proper middleware)
            $vesselParam = $request->route('vessel');
            if (!$vesselParam) {
                abort(403, 'No vessel specified.');
            }
            // Use resolveRouteBinding to handle both hashed and numeric IDs
            $vessel = (new Vessel())->resolveRouteBinding($vesselParam);
            if (!$vessel) {
                abort(404, 'Vessel not found.');
            }
        }

        return $vessel;
    }

    /**
     * Get current vessel ID from request attributes (set by EnsureVesselAccess middleware).
     * This is the preferred method as it uses the vessel_id already validated by middleware.
     */
    protected function getCurrentVesselId(Request $request = null): int
    {
        $request = $request ?? request();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        $vesselId = $request->attributes->get('vessel_id');

        if (!$vesselId) {
            // Fallback to getting from vessel model
            return $this->getCurrentVessel($request)->id;
        }

        return $vesselId;
    }

    /**
     * Get user's role for current vessel.
     */
    protected function getCurrentVesselRole(): string
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return 'viewer';
        }
        return $user->getRoleForVessel($this->getCurrentVesselId()) ?? 'viewer';
    }

    /**
     * Check if user has specific role for current vessel.
     */
    protected function hasRoleForCurrentVessel(string $role): bool
    {
        return $this->getCurrentVesselRole() === $role;
    }

    /**
     * Check if user has any of the specified roles for current vessel.
     */
    protected function hasAnyRoleForCurrentVessel(array $roles): bool
    {
        return in_array($this->getCurrentVesselRole(), $roles);
    }
}

