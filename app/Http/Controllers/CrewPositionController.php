<?php
namespace App\Http\Controllers;

use App\Actions\AuditLogAction;
use App\Http\Controllers\Concerns\HashesIds;
use App\Http\Requests\StoreCrewPositionRequest;
use App\Http\Requests\UpdateCrewPositionRequest;
use App\Http\Resources\CrewPositionResource;
use App\Models\CrewPosition;
use App\Models\User;
use App\Models\VesselRoleAccess;
use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrewPositionController extends Controller
{
    use HasTranslations, HashesIds;
    /**
     * Display a listing of crew positions for the current vessel.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id', 0);

        // Check if user has permission to view crew roles (moderator and administrator only)
        // Normal users should not have access to this page
        if (! $user || ! $user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to view crew roles.'));
        }

        $query = CrewPosition::query()
            ->where(function ($q) use ($vesselId) {
                $q->where('vessel_id', $vesselId)
                    ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })
            ->with(['vessel', 'crewMembers', 'vesselRoleAccess']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by scope (global vs vessel-specific)
        if ($request->filled('scope')) {
            if ($request->scope === 'global') {
                $query->whereNull('vessel_id');
            } elseif ($request->scope === 'vessel') {
                $query->where('vessel_id', $vesselId);
            }
        }

        // Filter by administrative vs normal roles
        // Default to administrative if no filter is provided
        if ($request->filled('role_type')) {
            if ($request->role_type === 'administrative') {
                $query->where('is_administrative', true);
            } elseif ($request->role_type === 'normal') {
                $query->where('is_administrative', false);
            }
        } else {
            // Default to administrative roles
            $query->where('is_administrative', true);
        }

        // Sorting
        $sortField     = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $crewPositions = $query->paginate(15)->withQueryString();

        // Transform the data
        $crewPositions->through(function ($position) {
            return (new CrewPositionResource($position))->resolve();
        });

        // Get all available vessel roles for the form
        $vesselRoles = VesselRoleAccess::where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'display_name', 'description']);

        return Inertia::render('CrewRoles/Index', [
            'crewPositions' => $crewPositions,
            'filters'       => $request->only(['search', 'scope', 'role_type', 'sort', 'direction']),
            'vesselRoles'   => $vesselRoles->map(function ($role) {
                return [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created crew position for the current vessel.
     */
    public function store(StoreCrewPositionRequest $request)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var \Illuminate\Http\Request $request */
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id', 0);

            // Decode vessel_role_access_id if it's hashed, or convert string to int
            $vesselRoleAccessId = $request->vessel_role_access_id;
            if ($vesselRoleAccessId) {
                if (is_numeric($vesselRoleAccessId)) {
                    $vesselRoleAccessId = (int) $vesselRoleAccessId;
                } else {
                    $vesselRoleAccessId = $this->unhashId($vesselRoleAccessId, 'vesselroleaccess');
                }
            } else {
                $vesselRoleAccessId = null;
            }

            // Access validated values directly as properties (never use validated())
            $crewPosition = CrewPosition::create([
                'name'                  => $request->name,
                'description'           => null,
                'vessel_id'             => $request->is_global ? null : $vesselId, // NULL for global, vessel_id for vessel-specific
                'vessel_role_access_id' => $vesselRoleAccessId,
                'is_administrative'     => $request->is_administrative ?? false,
            ]);

            $crewPosition->load(['vessel', 'crewMembers']);

            // Log the create action
            AuditLogAction::logCreate(
                $crewPosition,
                'Crew Position',
                $crewPosition->name,
                $vesselId
            );

            return redirect()
                ->route('panel.crew-roles.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $this->transFrom('notifications', "Crew role ':name' has been created successfully.", [
                    'name' => $crewPosition->name,
                ]))
                ->with('notification_delay', 3);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to create crew role. Please try again.'))
                ->with('notification_delay', 0);
        }
    }

    /**
     * Display the specified crew position.
     */
    public function show(CrewPosition $crewPosition)
    {
        $crewPosition->load(['vessel', 'crewMembers', 'vesselRoleAccess']);

        return Inertia::render('CrewRoles/Show', [
            'crewPosition' => new CrewPositionResource($crewPosition),
        ]);
    }

    /**
     * Update the specified crew position.
     */
    public function update(UpdateCrewPositionRequest $request, string $vessel, CrewPosition $crewPosition)
    {
        try {
            // Route model binding ensures $crewPosition is always a CrewPosition instance
            // $vessel parameter is the hashed vessel ID from the route, but we use vessel_id from request attributes

            // Verify crew position belongs to current vessel
            /** @var \Illuminate\Http\Request $request */
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id', 0);

            // Prevent editing of global roles (vessel_id = NULL)
            if ($crewPosition->vessel_id === null) {
                abort(403, $this->transFrom('notifications', 'Cannot edit default roles. Default roles are system-managed.'));
            }

            // Only allow updates to vessel-specific positions that belong to current vessel
            if ($crewPosition->vessel_id !== $vesselId) {
                abort(403, $this->transFrom('notifications', 'Unauthorized access to crew role.'));
            }

            // Store original state for change detection
            $originalCrewPosition = $crewPosition->replicate();

            // Decode vessel_role_access_id if it's hashed, or convert string to int
            $vesselRoleAccessId = $request->vessel_role_access_id;
            if ($vesselRoleAccessId) {
                if (is_numeric($vesselRoleAccessId)) {
                    $vesselRoleAccessId = (int) $vesselRoleAccessId;
                } else {
                    $vesselRoleAccessId = $this->unhashId($vesselRoleAccessId, 'vesselroleaccess');
                }
            } else {
                $vesselRoleAccessId = null;
            }

            // Access validated values directly as properties (never use validated())
            $crewPosition->update([
                'name'                  => $request->name,
                'description'           => null,
                'vessel_role_access_id' => $vesselRoleAccessId,
                'is_administrative'     => $request->is_administrative ?? false,
                // Note: vessel_id cannot be changed after creation (global vs vessel-specific)
            ]);

            $crewPosition->load(['vessel', 'crewMembers']);

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($crewPosition, $originalCrewPosition);
            AuditLogAction::logUpdate(
                $crewPosition,
                $changedFields,
                'Crew Position',
                $crewPosition->name,
                $vesselId
            );

            return redirect()
                ->route('panel.crew-roles.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $this->transFrom('notifications', "Crew role ':name' has been updated successfully.", [
                    'name' => $crewPosition->name,
                ]))
                ->with('notification_delay', 4);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Re-throw HTTP exceptions (like 403, 404) so they're handled properly
            throw $e;
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to update crew role. Please try again.'))
                ->with('notification_delay', 0);
        }
    }

    /**
     * Remove the specified crew position from storage.
     */
    public function destroy(Request $request, string $vessel, CrewPosition $crewPosition)
    {
        try {
            // Route model binding ensures $crewPosition is always a CrewPosition instance
            // $vessel parameter is the hashed vessel ID from the route, but we use vessel_id from request attributes

            // Verify crew position belongs to current vessel
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id', 0);

            // Prevent deletion of global roles (vessel_id = NULL)
            if ($crewPosition->vessel_id === null) {
                abort(403, $this->transFrom('notifications', 'Cannot delete default roles. Default roles are system-managed.'));
            }

            // Only allow deletion of vessel-specific positions that belong to current vessel
            if ($crewPosition->vessel_id !== $vesselId) {
                abort(403, $this->transFrom('notifications', 'Unauthorized access to crew role.'));
            }

            // Check if position has crew members assigned
            $crewMembersCount = User::where('position_id', $crewPosition->id)
                ->where(function ($q) use ($vesselId) {
                    $q->where('vessel_id', $vesselId)
                        ->orWhereNull('vessel_id');
                })
                ->count();

            if ($crewMembersCount > 0) {
                return back()
                    ->with('error', $this->transFrom('notifications', "Cannot delete crew role ':name' because it has :count crew member(s) assigned. Please reassign or remove crew members first.", [
                        'name' => $crewPosition->name,
                        'count' => $crewMembersCount,
                    ]))
                    ->with('notification_delay', 0);
            }

            // Store identifier before deletion
            $crewPositionName = $crewPosition->name;

            // Log the delete action BEFORE deletion
            AuditLogAction::logDelete(
                $crewPosition,
                'Crew Position',
                $crewPositionName,
                $vesselId
            );

            $crewPosition->delete();

            return redirect()
                ->route('panel.crew-roles.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $this->transFrom('notifications', "Crew role ':name' has been deleted successfully.", [
                    'name' => $crewPositionName,
                ]))
                ->with('notification_delay', 5);
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to delete crew role. Please try again.'))
                ->with('notification_delay', 0);
        }
    }

    /**
     * Get detailed crew position information for show modal
     */
    public function details(Request $request, $crewPositionId)
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = $request->user();

            // Get the ID from the route parameter and unhash it
            $crewPositionIdFromRoute = $request->route('crewPositionId');
            // Unhash crew position ID if it's a hashed string
            if ($crewPositionIdFromRoute && ! is_numeric($crewPositionIdFromRoute)) {
                $id = $this->unhashId($crewPositionIdFromRoute, 'crewposition');
            } else {
                $id = (int) $crewPositionIdFromRoute;
            }
            if (! $id) {
                abort(404, $this->transFrom('notifications', 'Crew position not found.'));
            }

            // Resolve crew position manually to avoid route model binding issues
            $crewPosition = CrewPosition::findOrFail($id);

            // Verify crew position belongs to current vessel or is global
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id', 0);

            // Check if user has permission to view crew roles (moderator and administrator only)
            if (! $user || ! $user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
                abort(403, $this->transFrom('notifications', 'You do not have permission to view crew role details.'));
            }

            // Allow access to global positions (vessel_id = NULL) or vessel-specific positions
            if ($crewPosition->vessel_id !== null && $crewPosition->vessel_id !== $vesselId) {
                abort(403, $this->transFrom('notifications', 'Unauthorized access to crew role.'));
            }

            // Load relationships for edit modal
            $crewPosition->loadCount('crewMembers');
            $crewPosition->load('vesselRoleAccess');

            // Get all available vessel roles
            $vesselRoles = VesselRoleAccess::where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name', 'display_name', 'description']);

            return response()->json([
                'crewPosition' => new CrewPositionResource($crewPosition),
                'vesselRoles'  => $vesselRoles->map(function ($role) {
                    return [
                        'id'           => $role->id,
                        'name'         => $role->name,
                        'display_name' => $role->display_name,
                        'description' => $role->description,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Failed to load crew role details.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
