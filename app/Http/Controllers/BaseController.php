<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vessel;

abstract class BaseController extends Controller
{
    /**
     * Get the current vessel from route parameter.
     */
    protected function getCurrentVessel(): Vessel
    {
        $vesselId = request()->route('vessel');

        if (!$vesselId) {
            abort(403, 'No vessel specified.');
        }

        $vessel = Vessel::findOrFail($vesselId);

        // Verify user still has access
        if (!auth()->user()->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        return $vessel;
    }

    /**
     * Get current vessel ID.
     */
    protected function getCurrentVesselId(): int
    {
        return $this->getCurrentVessel()->id;
    }

    /**
     * Get user's role for current vessel.
     */
    protected function getCurrentVesselRole(): string
    {
        return auth()->user()->getRoleForVessel($this->getCurrentVesselId()) ?? 'viewer';
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

