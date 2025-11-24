<?php
namespace App\Http\Controllers;

use App\Actions\AuditLogAction;
use App\Http\Requests\StoreVesselRequest;
use App\Http\Requests\UpdateVesselRequest;
use App\Http\Resources\VesselResource;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Vessel;
use App\Models\VesselUser;
use App\Services\VesselService;
use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VesselController extends BaseController
{
    use HasTranslations;
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
        $sortField     = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $vessels = $query->with(['crewMembers', 'movimentations', 'country', 'currency'])
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Vessels/Index', [
            'vessels'       => VesselResource::collection($vessels),
            'currentVessel' => new VesselResource($currentVessel),
            'filters'       => $request->only(['search', 'status', 'vessel_type', 'sort', 'direction']),
            'vesselTypes'   => [
                'cargo'     => 'Cargo',
                'passenger' => 'Passenger',
                'fishing'   => 'Fishing',
                'fish'      => 'Fish',
                'yacht'     => 'Yacht',
            ],
            'statuses'      => [
                'active'      => 'Active',
                'suspended'   => 'Suspended',
                'maintenance' => 'Maintenance',
            ],
            'countries'     => Country::orderBy('name')->get(['code', 'name']),
            'currencies'    => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();

        // Check if user can create vessels
        if (! $user->canCreateVessels()) {
            abort(403, 'You do not have permission to create vessels.');
        }

        return Inertia::render('Vessels/Create', [
            'vesselTypes' => [
                'cargo'     => 'Cargo',
                'passenger' => 'Passenger',
                'fishing'   => 'Fishing',
                'fish'      => 'Fish',
                'yacht'     => 'Yacht',
            ],
            'statuses'    => [
                'active'      => 'Active',
                'suspended'   => 'Suspended',
                'maintenance' => 'Maintenance',
            ],
            'countries'   => Country::orderBy('name')->get(['code', 'name']),
            'currencies'  => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol']),
            'vatProfiles' => \App\Models\VatProfile::active()->with('country')->orderBy('name')->get()->map(function ($profile) {
                return [
                    'id'         => $profile->id,
                    'name'       => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'country'    => $profile->country ? [
                        'id'   => $profile->country->id,
                        'name' => $profile->country->name,
                        'code' => $profile->country->code,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVesselRequest $request)
    {
        $user = auth()->user();

        // Check if user can create vessels (must have tenant role - paid_system)
        if (! $user->canCreateVessels()) {
            abort(403, 'You do not have permission to create vessels. You must have tenant role (paid_system).');
        }

        // Validate that user has tenant role (mandatory)
        if ($user->user_type !== 'paid_system') {
            abort(403, 'You must have tenant role (paid_system) to create vessels.');
        }

        try {
            $vesselService = new VesselService();

            $vessel = $vesselService->createVessel($user, [
                'name'                => $request->name,
                'registration_number' => $request->registration_number,
                'vessel_type'         => $request->vessel_type,
                'capacity'            => $request->capacity,
                'year_built'          => $request->year_built,
                'status'              => $request->status,
                'notes'               => $request->notes,
                'country_code'        => $request->country_code,
                'currency_code'       => $request->currency_code,
                'vat_profile_id'      => $request->vat_profile_id,
            ]);

            return redirect()
                ->route('panel.index')
                ->with('success', $this->transFrom('notifications', "Vessel ':name' has been created successfully.", [
                    'name' => $vessel->name,
                ]))
                ->with('notification_delay', 3); // 3 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to create vessel: :message', [
                    'message' => $e->getMessage(),
                ]))
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
            'vessel'        => new VesselResource($vessel),
            'currentVessel' => new VesselResource($currentVessel),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vessel $vessel)
    {
        return Inertia::render('Vessels/Edit', [
            'vessel'      => new VesselResource($vessel->load(['country', 'currency'])),
            'vesselTypes' => [
                'cargo'     => 'Cargo',
                'passenger' => 'Passenger',
                'fishing'   => 'Fishing',
                'fish'      => 'Fish',
                'yacht'     => 'Yacht',
            ],
            'statuses'    => [
                'active'      => 'Active',
                'suspended'   => 'Suspended',
                'maintenance' => 'Maintenance',
            ],
            'countries'   => Country::orderBy('name')->get(['code', 'name']),
            'currencies'  => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol']),
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
                'name'                => $request->name,
                'registration_number' => $request->registration_number,
                'vessel_type'         => $request->vessel_type,
                'capacity'            => $request->capacity,
                'year_built'          => $request->year_built,
                'status'              => $request->status,
                'notes'               => $request->notes,
                'country_code'        => $request->country_code,
                'currency_code'       => $request->currency_code,
            ]);

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($vessel, $originalVessel);
            AuditLogAction::logUpdate(
                $vessel,
                $changedFields,
                'Vessel',
                $vessel->name,
                null// Vessels are not vessel-scoped (they're global entities)
            );

            return redirect()
                ->route('panel.index')
                ->with('success', $this->transFrom('notifications', "Vessel ':name' has been updated successfully.", [
                    'name' => $vessel->name,
                ]))
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to update vessel. Please try again.'))
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
                return back()->with('error', $this->transFrom('notifications', "Cannot delete vessel ':name' because it has crew members assigned. Please reassign or remove crew members first.", [
                    'name' => $vessel->name,
                ]))
                    ->with('notification_delay', 0); // Persistent error
            }

            if ($vessel->transactions()->count() > 0) {
                return back()->with('error', $this->transFrom('notifications', "Cannot delete vessel ':name' because it has transactions. Please remove all transactions first.", [
                    'name' => $vessel->name,
                ]))
                    ->with('notification_delay', 0); // Persistent error
            }

            $vesselName = $vessel->name;
            $vesselId   = $vessel->id;

            // Explicitly unlink all users from this vessel before deletion
            // This ensures clean removal even if foreign keys don't cascade properly
            \App\Models\VesselUserRole::where('vessel_id', $vesselId)->delete();
            \App\Models\VesselUser::where('vessel_id', $vesselId)->delete();

            // Also clear owner_id to unlink the owner relationship
            $vessel->update(['owner_id' => null]);

            // Log the delete action BEFORE deletion
            AuditLogAction::logDelete(
                $vessel,
                'Vessel',
                $vesselName,
                null// Vessels are not vessel-scoped (they're global entities)
            );

            $vessel->delete();

            return redirect()
                ->route('panel.index')
                ->with('success', $this->transFrom('notifications', "Vessel ':name' has been deleted successfully. All user access has been removed.", [
                    'name' => $vesselName,
                ]))
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to delete vessel. Please try again.'))
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
