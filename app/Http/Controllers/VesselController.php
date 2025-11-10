<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVesselRequest;
use App\Http\Requests\UpdateVesselRequest;
use App\Http\Resources\VesselResource;
use App\Models\Vessel;
use App\Models\Country;
use App\Models\Currency;
use App\Models\VesselUser;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VesselController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentVessel = $this->getCurrentVessel();

        $query = Vessel::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vessel type
        if ($request->filled('vessel_type')) {
            $query->where('vessel_type', $request->vessel_type);
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $vessels = $query->with(['crewMembers', 'transactions', 'country', 'currency'])
                        ->paginate(15)
                        ->withQueryString();

        return Inertia::render('Vessels/Index', [
            'vessels' => VesselResource::collection($vessels),
            'currentVessel' => new VesselResource($currentVessel),
            'filters' => $request->only(['search', 'status', 'vessel_type', 'sort', 'direction']),
            'vesselTypes' => [
                'cargo' => 'Cargo',
                'passenger' => 'Passenger',
                'fishing' => 'Fishing',
                'yacht' => 'Yacht',
            ],
            'statuses' => [
                'active' => 'Active',
                'suspended' => 'Suspended',
                'maintenance' => 'Maintenance',
            ],
            'countries' => Country::orderBy('name')->get(['code', 'name']),
            'currencies' => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();

        // Check if user can create vessels
        if (!$user->canCreateVessels()) {
            abort(403, 'You do not have permission to create vessels.');
        }

        return Inertia::render('Vessels/Create', [
            'vesselTypes' => [
                'cargo' => 'Cargo',
                'passenger' => 'Passenger',
                'fishing' => 'Fishing',
                'yacht' => 'Yacht',
            ],
            'statuses' => [
                'active' => 'Active',
                'suspended' => 'Suspended',
                'maintenance' => 'Maintenance',
            ],
            'countries' => Country::orderBy('name')->get(['code', 'name']),
            'currencies' => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVesselRequest $request)
    {
        $user = auth()->user();

        // Check if user can create vessels
        if (!$user->canCreateVessels()) {
            abort(403, 'You do not have permission to create vessels.');
        }

        try {
            $vessel = Vessel::create([
                'name' => $request->name,
                'registration_number' => $request->registration_number,
                'vessel_type' => $request->vessel_type,
                'capacity' => $request->capacity,
                'year_built' => $request->year_built,
                'status' => $request->status,
                'notes' => $request->notes,
                'country_code' => $request->country_code,
                'currency_code' => $request->currency_code,
            ]);

            // Assign the vessel to the current user as administrator (owner)
            $user = auth()->user();

            // Get the administrator role access
            $adminRoleAccess = \App\Models\VesselRoleAccess::where('name', 'administrator')->first();

            if ($adminRoleAccess) {
                // Create vessel user role with administrator access
                \App\Models\VesselUserRole::create([
                    'vessel_id' => $vessel->id,
                    'user_id' => $user->id,
                    'vessel_role_access_id' => $adminRoleAccess->id,
                    'is_active' => true,
                ]);
            }

            // Also maintain the old vessel_users table for backward compatibility
            VesselUser::create([
                'vessel_id' => $vessel->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'is_active' => true,
            ]);

            // Set the vessel owner
            $vessel->update(['owner_id' => $user->id]);

            // Log the create action
            AuditLogService::logCreate(
                $vessel,
                'Vessel',
                $vessel->name,
                null // Vessels are not vessel-scoped (they're global entities)
            );

            return redirect()
                ->route('panel.index')
                ->with('success', "Vessel '{$vessel->name}' has been created successfully.")
                ->with('notification_delay', 3); // 3 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create vessel. Please try again.')
                ->with('notification_delay', 0); // Persistent error (0 = no auto-dismiss)
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Vessel $vessel)
    {
        $currentVessel = $this->getCurrentVessel();

        // Ensure the vessel being viewed is the current vessel
        if ($vessel->id !== $currentVessel->id) {
            abort(403, 'You can only view the current vessel.');
        }

        $vessel->load(['crewMembers.position', 'transactions.category', 'transactions.bankAccount']);

        return Inertia::render('Vessels/Show', [
            'vessel' => new VesselResource($vessel),
            'currentVessel' => new VesselResource($currentVessel),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vessel $vessel)
    {
        return Inertia::render('Vessels/Edit', [
            'vessel' => new VesselResource($vessel->load(['country', 'currency'])),
            'vesselTypes' => [
                'cargo' => 'Cargo',
                'passenger' => 'Passenger',
                'fishing' => 'Fishing',
                'yacht' => 'Yacht',
            ],
            'statuses' => [
                'active' => 'Active',
                'suspended' => 'Suspended',
                'maintenance' => 'Maintenance',
            ],
            'countries' => Country::orderBy('name')->get(['code', 'name']),
            'currencies' => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVesselRequest $request, Vessel $vessel)
    {
        try {
            // Store original state for change detection
            $originalVessel = $vessel->replicate();

            $vessel->update([
                'name' => $request->name,
                'registration_number' => $request->registration_number,
                'vessel_type' => $request->vessel_type,
                'capacity' => $request->capacity,
                'year_built' => $request->year_built,
                'status' => $request->status,
                'notes' => $request->notes,
                'country_code' => $request->country_code,
                'currency_code' => $request->currency_code,
            ]);

            // Get changed fields and log the update action
            $changedFields = AuditLogService::getChangedFields($vessel, $originalVessel);
            AuditLogService::logUpdate(
                $vessel,
                $changedFields,
                'Vessel',
                $vessel->name,
                null // Vessels are not vessel-scoped (they're global entities)
            );

            return redirect()
                ->route('panel.index')
                ->with('success', "Vessel '{$vessel->name}' has been updated successfully.")
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update vessel. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vessel $vessel)
    {
        try {
            // Check if vessel has crew members or transactions
            if ($vessel->crewMembers()->count() > 0) {
                return back()->with('error', "Cannot delete vessel '{$vessel->name}' because it has crew members assigned. Please reassign or remove crew members first.")
                    ->with('notification_delay', 0); // Persistent error
            }

            if ($vessel->transactions()->count() > 0) {
                return back()->with('error', "Cannot delete vessel '{$vessel->name}' because it has transactions. Please remove all transactions first.")
                    ->with('notification_delay', 0); // Persistent error
            }

            $vesselName = $vessel->name;

            // Log the delete action BEFORE deletion
            AuditLogService::logDelete(
                $vessel,
                'Vessel',
                $vesselName,
                null // Vessels are not vessel-scoped (they're global entities)
            );

            $vessel->delete();

            return redirect()
                ->route('panel.index')
                ->with('success', "Vessel '{$vesselName}' has been deleted successfully.")
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete vessel. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    /**
     * Search vessels for autocomplete
     */
    public function search(Request $request)
    {
        $query = Vessel::query();

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('registration_number', 'like', "%{$request->q}%");
            });
        }

        $vessels = $query->limit(10)->get();

        return VesselResource::collection($vessels);
    }
}
