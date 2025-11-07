<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteCrewMemberRequest;
use App\Http\Requests\StoreCrewMemberRequest;
use App\Http\Requests\UpdateCrewMemberRequest;
use App\Http\Resources\CrewMemberResource;
use App\Models\CrewPosition;
use App\Models\Currency;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrewMemberController extends Controller
{
    public function index(Request $request)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');
        $vessel = Vessel::with('owner')->findOrFail($vesselId);

        $query = User::query()->where(function ($builder) use ($vesselId, $vessel) {
            $builder->where(function ($crewQuery) use ($vesselId) {
                $crewQuery->where('vessel_id', $vesselId)
                    ->whereNotNull('position_id');
            });

            if ($vessel->owner_id) {
                $builder->orWhere('id', $vessel->owner_id);
            }
        });

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Vessel filtering is handled by tenant-based access above

        // Filter by position
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $crewMembers = $query->with(['vessel', 'position', 'activeSalaryCompensation'])
                            ->paginate(15)
                            ->withQueryString();

        // Transform the data manually without using JsonResource
        $crewMembers->through(function ($crewMember) {
            return (new CrewMemberResource($crewMember))->resolve();
        });

        // No need for available users anymore - we create users directly

        return Inertia::render('CrewMembers/Index', [
            'crewMembers' => $crewMembers,
            'filters' => $request->only(['search', 'status', 'position_id', 'sort', 'direction']),
            'positions' => CrewPosition::where('vessel_id', $vesselId)->select('id', 'name')->get(),
            'statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'on_leave' => 'On Leave',
            ],
            'currencies' => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol'])->toArray(),
            'paymentFrequencies' => [
                'weekly' => 'Weekly',
                'bi_weekly' => 'Bi-weekly',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually' => 'Annually',
            ],
        ]);
    }

    public function create(Request $request)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // No need for available users anymore - we create users directly

        return Inertia::render('CrewMembers/Create', [
            'vessels' => Vessel::select('id', 'name')->get(),
            'positions' => CrewPosition::select('id', 'name')->get(),
            'statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'on_leave' => 'On Leave',
            ],
            'currencies' => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol'])->toArray(),
            'paymentFrequencies' => [
                'weekly' => 'Weekly',
                'bi_weekly' => 'Bi-weekly',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually' => 'Annually',
            ],
        ]);
    }

    public function store(StoreCrewMemberRequest $request)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');

            // Handle password
            $password = null;
            $temporaryPassword = null;
            if ($request->login_permitted && $request->password) {
                $password = bcrypt($request->password);
                $temporaryPassword = null;
            } else {
                $password = bcrypt('temp_' . time());
                $temporaryPassword = 'temp_' . time();
            }

            // Only create salary compensation if salary is not skipped
            $skipSalary = $request->boolean('skip_salary') ?? false;
            $salaryData = null;

            if (!$skipSalary) {
                // Extract salary compensation data
                // Note: fixed_amount comes from MoneyInput as integer (cents), no conversion needed
                $salaryData = [
                    'compensation_type' => $request->compensation_type,
                    'fixed_amount' => $request->compensation_type === 'fixed' ? (int) $request->fixed_amount : null,
                    'percentage' => $request->compensation_type === 'percentage' ? $request->percentage : null,
                    'currency' => $request->currency,
                    'payment_frequency' => $request->payment_frequency,
                    'is_active' => true,
                ];
            }

            $crewMember = User::create([
                'position_id' => $request->position_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'hire_date' => $request->hire_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'vessel_id' => $vesselId,
                'password' => $password,
                'temporary_password' => $temporaryPassword,
                'user_type' => 'employee_of_vessel',
            ]);

            // Create salary compensation only if not skipped
            if (!$skipSalary && $salaryData) {
                $crewMember->salaryCompensations()->create($salaryData);
            }

            return redirect()
                ->route('panel.crew-members.index', ['vessel' => $vesselId])
                ->with('success', "Crew member '{$crewMember->name}' has been created successfully.")
                ->with('notification_delay', 3); // 3 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create crew member. Please try again.')
                ->with('notification_delay', 0); // Persistent error (0 = no auto-dismiss)
        }
    }

    public function show(User $crewMember)
    {
        $crewMember->load(['vessel', 'position', 'activeSalaryCompensation']);

        return Inertia::render('CrewMembers/Show', [
            'crewMember' => new CrewMemberResource($crewMember),
        ]);
    }

    public function edit(Request $request, User $crewMember)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Load salary compensation relationship
        $crewMember->load(['activeSalaryCompensation']);

        // Get users who have access to this vessel and can be crew members
        // Include the current user if they're already connected
        $availableUsers = User::whereHas('vesselsThroughRoles', function ($query) use ($vesselId) {
            $query->where('vessels.id', $vesselId);
        })->where(function ($query) use ($vesselId, $crewMember) {
            $query->whereDoesntHave('crewMembers', function ($q) use ($vesselId) {
                $q->where('vessel_id', $vesselId);
            })->orWhere('id', $crewMember->user_id);
        })->select('id', 'name', 'email')->get();

        return Inertia::render('CrewMembers/Edit', [
            'crewMember' => new CrewMemberResource($crewMember),
            'vessels' => Vessel::select('id', 'name')->get(),
            'positions' => CrewPosition::select('id', 'name')->get(),
            'statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'on_leave' => 'On Leave',
            ],
            'currencies' => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol'])->toArray(),
            'paymentFrequencies' => [
                'weekly' => 'Weekly',
                'bi_weekly' => 'Bi-weekly',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually' => 'Annually',
            ],
        ]);
    }

    public function update(UpdateCrewMemberRequest $request, $vessel, User $crewMember)
    {
        try {
            // Verify crew member belongs to current vessel
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if ($crewMember->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew member.');
            }

            // Handle password update
            $updateData = [
                'position_id' => $request->position_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'hire_date' => $request->hire_date,
                'status' => $request->status,
                'notes' => $request->notes,
            ];

            if ($request->login_permitted && $request->password) {
                $updateData['password'] = bcrypt($request->password);
                $updateData['temporary_password'] = null;
            } elseif (!$request->login_permitted) {
                $updateData['temporary_password'] = 'temp_' . time();
            }

            // Extract salary compensation data
            // Note: fixed_amount comes from MoneyInput as integer (cents), no conversion needed
            $salaryData = [
                'compensation_type' => $request->compensation_type,
                'fixed_amount' => $request->compensation_type === 'fixed' ? (int) $request->fixed_amount : null,
                'percentage' => $request->compensation_type === 'percentage' ? $request->percentage : null,
                'currency' => $request->currency,
                'payment_frequency' => $request->payment_frequency,
                'is_active' => true,
            ];

            $crewMember->update($updateData);

            // Update or create salary compensation
            $crewMember->salaryCompensations()->updateOrCreate(
                ['is_active' => true],
                $salaryData
            );

            return redirect()
                ->route('panel.crew-members.index', ['vessel' => $vesselId])
                ->with('success', "Crew member '{$crewMember->name}' has been updated successfully.")
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update crew member. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function destroy(DeleteCrewMemberRequest $request, $vessel, User $crewMember)
    {
        try {
            // Verify crew member belongs to current vessel
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if ($crewMember->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew member.');
            }

            $vesselModel = Vessel::findOrFail($vesselId);

            if ($vesselModel->owner_id === $crewMember->id) {
                return back()
                    ->with('error', "Cannot delete vessel owner '{$crewMember->name}'. Transfer ownership before removing this user.")
                    ->with('notification_delay', 0);
            }

            // Check if crew member has transactions
            if ($crewMember->transactions()->count() > 0) {
                return back()->with('error', "Cannot delete crew member '{$crewMember->name}' because they have transactions. Please remove all transactions first.")
                    ->with('notification_delay', 0); // Persistent error
            }

            $crewMemberName = $crewMember->name;

            // If this is a crew member (has vessel_id and position_id), remove crew member data
            if ($crewMember->isCrewMember()) {
                $crewMember->update([
                    'vessel_id' => null,
                    'position_id' => null,
                    'phone' => null,
                    'date_of_birth' => null,
                    'hire_date' => null,
                    'salary_amount' => null,
                    'salary_currency' => null,
                    'house_of_zeros' => null,
                    'payment_frequency' => null,
                    'status' => null,
                    'notes' => null,
                ]);
            } else {
                // If it's a system user, just delete them
                $crewMember->delete();
            }

            return redirect()
                ->route('panel.crew-members.index', ['vessel' => $vesselId])
                ->with('success', "Crew member '{$crewMemberName}' has been deleted successfully.")
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete crew member. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $crewMembers = User::query()
            ->whereNotNull('position_id')
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('document_number', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'document_number']);

        return response()->json($crewMembers);
    }
}
