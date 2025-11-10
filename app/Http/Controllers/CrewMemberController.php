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
use App\Models\VesselRoleAccess;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class CrewMemberController extends Controller
{
    public function index(Request $request)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');
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

        return inertia('CrewMembers/Index', [
            'crewMembers' => $crewMembers,
            'filters' => $request->only(['search', 'status', 'position_id', 'sort', 'direction']),
            'positions' => CrewPosition::where(function ($query) use ($vesselId) {
                $query->where('vessel_id', $vesselId)
                      ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })->select('id', 'name')->get(),
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
        $vesselId = (int) $request->attributes->get('vessel_id');

        // No need for available users anymore - we create users directly

        return inertia('CrewMembers/Create', [
            'vessels' => Vessel::select('id', 'name')->get(),
            'positions' => CrewPosition::where(function ($query) use ($vesselId) {
                $query->where('vessel_id', $vesselId)
                      ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })->select('id', 'name')->get(),
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
            $vesselId = (int) $request->attributes->get('vessel_id');

            // Check if user with this email already exists
            $existingUser = null;
            if ($request->email) {
                $existingUser = User::where('email', strtolower(trim($request->email)))->first();
            }

            // If user exists, link them to vessel instead of creating new user
            if ($existingUser) {
                // Check if user already has an existing account (not just a crew member account)
                $hasExistingAccount = $existingUser->hasExistingAccount();

                // If user already has account, don't allow password changes
                // Only update crew member fields, not account credentials
                $updateData = [
                    'position_id' => $request->position_id,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'date_of_birth' => $request->date_of_birth,
                    'hire_date' => $request->hire_date,
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'vessel_id' => $vesselId,
                ];

                // Only update password/login settings if user doesn't have existing account
                if (!$hasExistingAccount) {
                    // Handle password for new crew member account
                    if ($request->login_permitted && $request->password) {
                        $updateData['password'] = bcrypt($request->password);
                        $updateData['login_permitted'] = true;
                        $updateData['temporary_password'] = null;
                    } else {
                        $updateData['password'] = bcrypt('temp_' . time());
                        $updateData['temporary_password'] = 'temp_' . time();
                        $updateData['login_permitted'] = false;
                    }
                } else {
                    // User has existing account - enable login if requested, but don't change password
                    if ($request->login_permitted) {
                        $updateData['login_permitted'] = true;
                    }
                    // Don't update password or temporary_password for existing accounts
                }

                // Update user_type to employee_of_vessel if it's not already set
                if (!$hasExistingAccount) {
                    $updateData['user_type'] = 'employee_of_vessel';
                }

                $existingUser->update($updateData);
                $crewMember = $existingUser;
            } else {
                // Create new user
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
                    'login_permitted' => $request->login_permitted ?? false,
                ]);
            }

            // Only create salary compensation if not skipped and crew member was just created (not existing user)
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

                // Only create if crew member doesn't already have an active salary compensation
                if (!$crewMember->salaryCompensations()->where('is_active', true)->exists()) {
                    $crewMember->salaryCompensations()->create($salaryData);
                }
            }

            // Grant vessel access to the crew member
            // Get the "normal" role access (view-only access for crew members)
            $normalRoleAccess = VesselRoleAccess::where('name', 'normal')->first();

            if ($normalRoleAccess) {
                // Create or update vessel user role with normal access
                // Using updateOrCreate to handle cases where access might already exist
                VesselUserRole::updateOrCreate(
                    [
                        'vessel_id' => $vesselId,
                        'user_id' => $crewMember->id,
                    ],
                    [
                        'vessel_role_access_id' => $normalRoleAccess->id,
                        'is_active' => true,
                    ]
                );
            }

            // Also maintain the old vessel_users table for backward compatibility
            VesselUser::updateOrCreate(
                [
                    'vessel_id' => $vesselId,
                    'user_id' => $crewMember->id,
                ],
                [
                    'role' => 'viewer',
                    'is_active' => true,
                ]
            );

            // Log the create action
            AuditLogService::logCreate(
                $crewMember,
                'Crew Member',
                $crewMember->name,
                $vesselId
            );

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

        return inertia('CrewMembers/Show', [
            'crewMember' => new CrewMemberResource($crewMember),
        ]);
    }

    public function edit(Request $request, User $crewMember)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');

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

        return inertia('CrewMembers/Edit', [
            'crewMember' => new CrewMemberResource($crewMember),
            'vessels' => Vessel::select('id', 'name')->get(),
            'positions' => CrewPosition::where(function ($query) use ($vesselId) {
                $query->where('vessel_id', $vesselId)
                      ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })->select('id', 'name')->get(),
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
            $vesselId = (int) $request->attributes->get('vessel_id');
            if ($crewMember->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew member.');
            }

            // Store original state for change detection BEFORE update
            $originalCrewMember = $crewMember->replicate();

            // Check if user has an existing account (not just a crew member account)
            $hasExistingAccount = $crewMember->hasExistingAccount();

            // Handle password update
            $updateData = [
                'position_id' => $request->position_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'hire_date' => $request->hire_date,
                'status' => $request->status,
                'notes' => $request->notes,
            ];

            // Only update email if user doesn't have existing account
            // For existing accounts, email shouldn't be changed through crew member update
            if (!$hasExistingAccount && $request->email) {
                $updateData['email'] = $request->email;
            }

            // Handle password and login settings
            if ($hasExistingAccount) {
                // User has existing account - don't allow password changes
                // Only update login_permitted status
                $updateData['login_permitted'] = $request->login_permitted ?? false;
                // Don't update password or temporary_password for existing accounts
            } else {
                // User doesn't have existing account - allow password changes
                if ($request->login_permitted && $request->password) {
                    $updateData['password'] = bcrypt($request->password);
                    $updateData['temporary_password'] = null;
                    $updateData['login_permitted'] = true;
                } elseif (!$request->login_permitted) {
                    $updateData['temporary_password'] = 'temp_' . time();
                    $updateData['login_permitted'] = false;
                }
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

            // Get changed fields and log the update action
            $changedFields = AuditLogService::getChangedFields($crewMember, $originalCrewMember);
            AuditLogService::logUpdate(
                $crewMember,
                $changedFields,
                'Crew Member',
                $crewMember->name,
                $vesselId
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
            $vesselId = (int) $request->attributes->get('vessel_id');
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

            // Log the delete action BEFORE deletion
            AuditLogService::logDelete(
                $crewMember,
                'Crew Member',
                $crewMemberName,
                $vesselId
            );

            // Remove vessel access - delete vessel_user_roles entries for this user and vessel
            // This removes their permission-based access (RBAC system)
            VesselUserRole::where('user_id', $crewMember->id)
                ->where('vessel_id', $vesselId)
                ->delete();

            // Remove vessel access - delete vessel_users entries for this user and vessel
            // This removes their legacy vessel access (tenant access)
            VesselUser::where('user_id', $crewMember->id)
                ->where('vessel_id', $vesselId)
                ->delete();

            // Check if user has access to other vessels (after removing current vessel access)
            $hasOtherVesselAccess = VesselUserRole::where('user_id', $crewMember->id)
                ->where('is_active', true)
                ->exists();

            $hasOtherVesselUsers = VesselUser::where('user_id', $crewMember->id)
                ->where('is_active', true)
                ->exists();

            // Check if user owns other vessels
            $ownsOtherVessels = Vessel::where('owner_id', $crewMember->id)
                ->where('id', '!=', $vesselId)
                ->exists();

            // If this is a crew member (has vessel_id and position_id), handle crew member data
            if ($crewMember->isCrewMember() && $crewMember->vessel_id == $vesselId) {
                // If user has no other vessel access, no other vessel users, doesn't own other vessels,
                // and is only an employee of vessel, delete the user entirely
                if (!$hasOtherVesselAccess && !$hasOtherVesselUsers && !$ownsOtherVessels
                    && $crewMember->user_type === 'employee_of_vessel') {
                    // Delete the user completely - this will cascade delete any remaining records
                    $crewMember->delete();
                } else {
                    // User has access to other vessels or owns other vessels, just clear crew member data for this vessel
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
                }
            } else {
                // If it's a system user or crew member from different vessel
                // Only delete the user if they have no other vessel access and don't own other vessels
                if (!$hasOtherVesselAccess && !$hasOtherVesselUsers && !$ownsOtherVessels) {
                    // Delete the user completely - this will cascade delete any remaining records
                    $crewMember->delete();
                }
                // If user has access to other vessels or owns other vessels, we've already removed their access to this vessel above
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
