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

        // Load vessel model first to check if it exists
        // Eager load setting to avoid N+1 in HandleInertiaRequests and other places
        // Check if vessel is already loaded in request attributes (e.g. by HandleInertiaRequests)
        $vessel = $request->attributes->get('vessel');
        
        if (!$vessel || $vessel->id !== $vesselId) {
            $vessel = \App\Models\Vessel::with('setting')->find($vesselId);
        }
        
        if (!$vessel) {
            abort(404, 'Vessel not found.');
        }

        // Optimize access check and role retrieval
        // Instead of calling hasAccessToVessel (1 query) and then getRoleForVessel (1 query),
        // we try to get the role first.
        
        $userRole = null;
        $hasAccess = false;

        // 1. Check for explicit role assignment
        // Use memoized getRoleForVessel from User model if available, or query efficiently
        $userRole = $user->getRoleForVessel($vesselId);
        
        if ($userRole) {
            $hasAccess = true;
        } else {
            // 2. Check if user is the owner
            if ($vessel->owner_id === $user->id) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Share vessel data with all views
        view()->share('currentVessel', $vessel);
        view()->share('currentVesselRole', $userRole);

        // Share vessel via request attributes for use in controllers and requests
        // Access via: $request->attributes->get('vessel') or $request->get('vessel')
        $request->attributes->set('vessel', $vessel);
        $request->attributes->set('vessel_id', $vesselId);
        $request->attributes->set('vessel_role', $userRole);

        return $next($request);
    }
}
