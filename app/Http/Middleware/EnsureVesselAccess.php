<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVesselAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Get vessel ID from route parameter
        $vesselId = $request->route('vessel');

        if (!$vesselId) {
            return redirect()->route('panel.index');
        }

        // Check if user has access to this vessel
        if (!$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Share vessel data with all views
        $vessel = \App\Models\Vessel::findOrFail($vesselId);
        view()->share('currentVessel', $vessel);
        view()->share('currentVesselRole', $user->getRoleForVessel($vesselId));

        return $next($request);
    }
}
