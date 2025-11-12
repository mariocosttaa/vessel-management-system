<?php

namespace App\Http\Middleware;

use App\Actions\General\EasyHashAction;
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

        // Get vessel ID from route parameter (may be hashed)
        $vesselParam = $request->route('vessel');

        if (!$vesselParam) {
            return redirect()->route('panel.index');
        }

        // Block numeric IDs - redirect to hashed version
        if (is_numeric($vesselParam)) {
            $vesselId = (int) $vesselParam;
            $vessel = \App\Models\Vessel::find($vesselId);
            if ($vessel) {
                // Redirect to hashed URL
                $hashedId = EasyHashAction::encode($vesselId, 'vessel-id');
                $currentPath = $request->path();
                // Replace the numeric vessel ID with hashed ID in the path
                $newPath = preg_replace('#^panel/\d+#', "panel/{$hashedId}", $currentPath);
                return redirect('/' . $newPath, 301); // 301 permanent redirect
            }
            abort(404, 'Vessel not found.');
        }

        // Hashed ID - decode it
        $vesselId = EasyHashAction::decode($vesselParam, 'vessel-id');
        if (!$vesselId || !is_numeric($vesselId)) {
            abort(404, 'Vessel not found.');
        }
        $vesselId = (int) $vesselId;

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
