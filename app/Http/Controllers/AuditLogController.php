<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Vessel;
use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    use HasTranslations;
    /**
     * Display a listing of audit logs (monitoring page).
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes if available (set by EnsureVesselAccess middleware)
        /** @var int|null $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view audit logs
        // Only administrators should have access to audit logs
        // Check if user has admin role (check both role names and vessel roles)
        $hasAdminRole = $user && (
            $user->hasAnyRole(['administrator', 'admin']) ||
            ($vesselId && $user->getRoleForVessel($vesselId) === 'administrator')
        );

        if (!$hasAdminRole) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to view audit logs.'));
        }

        // Start building query
        $query = AuditLog::with(['user', 'vessel'])
            ->orderBy('created_at', 'desc');

        // Filter by vessel if vessel_id is provided
        if ($vesselId) {
            $query->where('vessel_id', $vesselId);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vessel', function ($vesselQuery) use ($search) {
                      $vesselQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Paginate results
        $auditLogs = $query->paginate(50)->withQueryString();

        // Get filter options
        $actions = ['create', 'update', 'delete'];
        $modelTypes = AuditLog::distinct('model_type')
            ->orderBy('model_type')
            ->pluck('model_type')
            ->map(function ($type) {
                $className = class_basename($type);
                // Convert CamelCase to readable format (e.g., "CrewPosition" -> "Crew Position")
                $label = preg_replace('/(?<!^)([A-Z])/', ' $1', $className);
                return [
                    'value' => $type,
                    'label' => $label,
                ];
            })
            ->values();

        // Get users for filter
        $users = \App\Models\User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });

        // Get vessels for filter (if not vessel-scoped)
        $vessels = null;
        if (!$vesselId) {
            $vessels = Vessel::select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($vessel) {
                    return [
                        'id' => $vessel->id,
                        'name' => $vessel->name,
                    ];
                });
        }

        return Inertia::render('AuditLogs/Index', [
            'auditLogs' => AuditLogResource::collection($auditLogs),
            'filters' => $request->only(['search', 'action', 'model_type', 'user_id', 'date_from', 'date_to', 'vessel_id']),
            'actions' => $actions,
            'modelTypes' => $modelTypes,
            'users' => $users,
            'vessels' => $vessels,
            'currentVesselId' => $vesselId,
        ]);
    }

    /**
     * Get recent audit logs for notification dropdown.
     * Returns the last 5 audit logs for the current vessel (if vessel-scoped) or all vessels.
     */
    public function recent(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes if available (set by EnsureVesselAccess middleware)
        /** @var int|null $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view audit logs
        // Only administrators should have access to audit logs
        $hasAdminRole = $user && (
            $user->hasAnyRole(['administrator', 'admin']) ||
            ($vesselId && $user->getRoleForVessel($vesselId) === 'administrator')
        );

        if (!$hasAdminRole) {
            return response()->json(['data' => []]);
        }

        // Start building query
        $query = AuditLog::with(['user', 'vessel'])
            ->orderBy('created_at', 'desc');

        // Filter by vessel if vessel_id is provided
        if ($vesselId) {
            $query->where('vessel_id', $vesselId);
        }

        // Get last 5 audit logs
        $auditLogs = $query->limit(5)->get();

        return response()->json([
            'data' => AuditLogResource::collection($auditLogs),
        ]);
    }
}
