<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Actions\General\EasyHashAction;

class VesselAuthPrivateFiles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthorized.');
        }

        $vesselIdHashed = $request->route('vesselIdHashed');

        if (!$vesselIdHashed) {
            abort(404, 'Vessel not found.');
        }

        $vesselId = EasyHashAction::decode($vesselIdHashed, 'vessel-id');

        if (!$vesselId) {
            abort(404, 'Vessel not found.');
        }

        // Check if user has access to this vessel
        if (!$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        return $next($request);
    }
}

