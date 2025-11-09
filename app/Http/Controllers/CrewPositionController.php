<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCrewPositionRequest;
use App\Http\Requests\UpdateCrewPositionRequest;
use App\Http\Resources\CrewPositionResource;
use App\Models\CrewPosition;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrewPositionController extends Controller
{
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
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, 'You do not have permission to view crew roles.');
        }

        $query = CrewPosition::query()
            ->where(function ($q) use ($vesselId) {
                $q->where('vessel_id', $vesselId)
                  ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })
            ->with(['vessel', 'crewMembers']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by scope (global vs vessel-specific)
        if ($request->filled('scope')) {
            if ($request->scope === 'global') {
                $query->whereNull('vessel_id');
            } elseif ($request->scope === 'vessel') {
                $query->where('vessel_id', $vesselId);
            }
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $crewPositions = $query->paginate(15)->withQueryString();

        // Transform the data
        $crewPositions->through(function ($position) {
            return (new CrewPositionResource($position))->resolve();
        });

        return Inertia::render('CrewRoles/Index', [
            'crewPositions' => $crewPositions,
            'filters' => $request->only(['search', 'scope', 'sort', 'direction']),
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

            // Access validated values directly as properties (never use validated())
            $crewPosition = CrewPosition::create([
                'name' => $request->name,
                'description' => $request->description,
                'vessel_id' => $request->is_global ? null : $vesselId, // NULL for global, vessel_id for vessel-specific
            ]);

            $crewPosition->load(['vessel', 'crewMembers']);

            return redirect()
                ->route('panel.crew-roles.index', ['vessel' => $vesselId])
                ->with('success', "Crew role '{$crewPosition->name}' has been created successfully.")
                ->with('notification_delay', 3);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create crew role. Please try again.')
                ->with('notification_delay', 0);
        }
    }

    /**
     * Display the specified crew position.
     */
    public function show(CrewPosition $crewPosition)
    {
        $crewPosition->load(['vessel', 'crewMembers']);

        return Inertia::render('CrewRoles/Show', [
            'crewPosition' => new CrewPositionResource($crewPosition),
        ]);
    }

    /**
     * Update the specified crew position.
     */
    public function update(UpdateCrewPositionRequest $request, $crewPosition)
    {
        try {
            // Get the ID from the route parameter directly - bypass route model binding
            $id = (int) $request->route('crewPosition');

            // Resolve crew position manually to avoid route model binding issues
            $crewPosition = CrewPosition::findOrFail($id);

            // Verify crew position belongs to current vessel
            /** @var \Illuminate\Http\Request $request */
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id', 0);

            // Prevent editing of global roles (vessel_id = NULL)
            if ($crewPosition->vessel_id === null) {
                abort(403, 'Cannot edit default roles. Default roles are system-managed.');
            }

            // Only allow updates to vessel-specific positions that belong to current vessel
            if ($crewPosition->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew role.');
            }

            // Access validated values directly as properties (never use validated())
            $crewPosition->update([
                'name' => $request->name,
                'description' => $request->description,
                // Note: vessel_id cannot be changed after creation (global vs vessel-specific)
            ]);

            $crewPosition->load(['vessel', 'crewMembers']);

            return redirect()
                ->route('panel.crew-roles.index', ['vessel' => $vesselId])
                ->with('success', "Crew role '{$crewPosition->name}' has been updated successfully.")
                ->with('notification_delay', 4);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Re-throw HTTP exceptions (like 403, 404) so they're handled properly
            throw $e;
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update crew role. Please try again.')
                ->with('notification_delay', 0);
        }
    }

    /**
     * Remove the specified crew position from storage.
     */
    public function destroy(Request $request, $crewPosition)
    {
        try {
            // Get the ID from the route parameter directly - bypass route model binding
            $id = (int) $request->route('crewPosition');

            // Resolve crew position manually to avoid route model binding issues
            $crewPosition = CrewPosition::findOrFail($id);

            // Verify crew position belongs to current vessel
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id', 0);

            // Prevent deletion of global roles (vessel_id = NULL)
            if ($crewPosition->vessel_id === null) {
                abort(403, 'Cannot delete default roles. Default roles are system-managed.');
            }

            // Only allow deletion of vessel-specific positions that belong to current vessel
            if ($crewPosition->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew role.');
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
                    ->with('error', "Cannot delete crew role '{$crewPosition->name}' because it has {$crewMembersCount} crew member(s) assigned. Please reassign or remove crew members first.")
                    ->with('notification_delay', 0);
            }

            $crewPositionName = $crewPosition->name;
            $crewPosition->delete();

            return redirect()
                ->route('panel.crew-roles.index', ['vessel' => $vesselId])
                ->with('success', "Crew role '{$crewPositionName}' has been deleted successfully.")
                ->with('notification_delay', 5);
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete crew role. Please try again.')
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

            // Get the ID from the route parameter directly to avoid any route model binding
            $id = (int) $request->route('crewPositionId');

            // Resolve crew position manually to avoid route model binding issues
            $crewPosition = CrewPosition::findOrFail($id);

            // Verify crew position belongs to current vessel or is global
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id', 0);

            // Check if user has permission to view crew roles (moderator and administrator only)
            if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
                abort(403, 'You do not have permission to view crew role details.');
            }

            // Allow access to global positions (vessel_id = NULL) or vessel-specific positions
            if ($crewPosition->vessel_id !== null && $crewPosition->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew role.');
            }

            // Load count only (no need to load full relationships for edit modal)
            $crewPosition->loadCount('crewMembers');

            return response()->json([
                'crewPosition' => new CrewPositionResource($crewPosition),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load crew role details.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

