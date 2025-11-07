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

        // Load vessel model
        $vessel = \App\Models\Vessel::findOrFail($vesselId);

        // Share vessel data with all views
        view()->share('currentVessel', $vessel);
        view()->share('currentVesselRole', $user->getRoleForVessel($vesselId));

        // Share vessel via request attributes for use in controllers and requests
        // Access via: $request->attributes->get('vessel') or $request->get('vessel')
        $request->attributes->set('vessel', $vessel);
        $request->attributes->set('vessel_id', $vesselId);

        return $next($request);
    }
}
