<?php
namespace App\Http\Controllers;

use App\Actions\AuditLogAction;
use App\Http\Controllers\Concerns\HashesIds;
use App\Http\Requests\DeleteCrewMemberRequest;
use App\Http\Requests\StoreCrewMemberRequest;
use App\Http\Requests\UpdateCrewMemberRequest;
use App\Http\Resources\CrewMemberResource;
use App\Mail\CrewMemberInvitationCancelledMail;
use App\Mail\CrewMemberInvitationMail;
use App\Models\CrewPosition;
use App\Models\Currency;
use App\Models\InvitationEmail;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselRoleAccess;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CrewMemberController extends Controller
{
    use HashesIds;
    public function index(Request $request)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');
        $vessel   = Vessel::with('owner')->findOrFail($vesselId);

        $query = User::query()->where(function ($builder) use ($vesselId, $vessel) {
            $builder->where(function ($crewQuery) use ($vesselId) {
                $crewQuery->where('vessel_id', $vesselId);
                // Removed whereNotNull('position_id') to allow users without position to appear
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
        $sortField     = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $crewMembers = $query->with(['vessel', 'position', 'activeSalaryCompensation'])
            ->paginate(15)
            ->withQueryString();

        // Transform the data manually without using JsonResource
        $crewMembers->through(function ($crewMember) use ($request) {
            return (new CrewMemberResource($crewMember))->toArray($request);
        });

        // Get pending invitations (users with invitation_token but not accepted)
        $pendingInvitations = User::where('vessel_id', $vesselId)
            ->whereNotNull('invitation_token')
            ->whereNull('invitation_accepted_at')
            ->with(['position'])
            ->orderBy('invitation_sent_at', 'desc')
            ->get()
            ->map(function ($user) use ($vesselId) {
                $emailCount = InvitationEmail::where('user_id', $user->id)
                    ->where('vessel_id', $vesselId)
                    ->where('email_type', 'invitation')
                    ->count();

                return [
                    'id'                    => $user->id,
                    'name'                  => $user->name,
                    'email'                 => $user->email,
                    'position'              => $user->position ? $user->position->name : null,
                    'invitation_sent_at'    => $user->invitation_sent_at?->format('Y-m-d H:i:s'),
                    'days_since_invitation' => $user->invitation_sent_at ? $user->invitation_sent_at->diffInDays(now()) : null,
                    'email_send_count'      => $emailCount,
                    'can_resend'            => $emailCount < 3,
                ];
            });

        return inertia('CrewMembers/Index', [
            'crewMembers'        => $crewMembers,
            'pendingInvitations' => $pendingInvitations,
            'filters'            => $request->only(['search', 'status', 'position_id', 'sort', 'direction']),
            'positions'          => CrewPosition::where(function ($query) use ($vesselId) {
                $query->where('vessel_id', $vesselId)
                    ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })->select('id', 'name')->get(),
            'statuses'           => [
                'active'   => 'Active',
                'inactive' => 'Inactive',
                'on_leave' => 'On Leave',
            ],
            'currencies'         => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol'])->toArray(),
            'paymentFrequencies' => [
                'weekly'    => 'Weekly',
                'bi_weekly' => 'Bi-weekly',
                'monthly'   => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually'  => 'Annually',
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
            'vessels'            => Vessel::select('id', 'name')->get()->map(function ($vessel) {
                return [
                    'id'   => $this->hashId($vessel->id, 'vessel'),
                    'name' => $vessel->name,
                ];
            }),
            'positions'          => CrewPosition::where(function ($query) use ($vesselId) {
                $query->where('vessel_id', $vesselId)
                    ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })->select('id', 'name')->get()->map(function ($position) {
                return [
                    'id'   => $this->hashId($position->id, 'crewposition'),
                    'name' => $position->name,
                ];
            }),
            'statuses'           => [
                'active'   => 'Active',
                'inactive' => 'Inactive',
                'on_leave' => 'On Leave',
            ],
            'currencies'         => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol'])->toArray(),
            'paymentFrequencies' => [
                'weekly'    => 'Weekly',
                'bi_weekly' => 'Bi-weekly',
                'monthly'   => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually'  => 'Annually',
            ],
        ]);
    }

    /**
     * Check if email exists.
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user  = User::where('email', $email)->first();

        return response()->json([
            'exists' => $user !== null,
            'user'   => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ] : null,
        ]);
    }

    public function store(StoreCrewMemberRequest $request)
    {
        try {
            Log::info('CrewMemberController::store - Start', [
                'request_url'    => $request->fullUrl(),
                'request_method' => $request->method(),
                'request_data'   => $request->except(['password', '_token']),
            ]);

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');
            $vessel   = Vessel::findOrFail($vesselId);

            $createWithoutEmail = $request->boolean('create_without_email') ?? false;

            // Email is required unless creating without email
            if (! $createWithoutEmail && ! $request->email) {
                return back()
                    ->withInput()
                    ->with('error', 'Email is required.')
                    ->with('notification_delay', 0);
            }

            $email        = $request->email ? strtolower(trim($request->email)) : null;
            $existingUser = $email ? User::where('email', $email)->first() : null;

            // Generate invitation token only if not creating without email
            $invitationToken = $createWithoutEmail ? null : Str::random(64);

            if ($existingUser && ! $createWithoutEmail) {
                // User exists - update vessel assignment and send invitation
                // Don't update name, password, or other fields for existing users
                $crewMember = $existingUser;

                // Update vessel assignment if not already set
                if (! $crewMember->vessel_id || $crewMember->vessel_id !== $vesselId) {
                    $crewMember->update([
                        'vessel_id'   => $vesselId,
                        'position_id' => $request->position_id, // Already decoded in prepareForValidation
                        'hire_date'   => $request->hire_date,
                        'status'      => $request->status ?? 'active',
                    ]);
                }

                // Set invitation token for existing users too (they need to accept to link to vessel)
                $crewMember->update([
                    'invitation_token'   => $invitationToken,
                    'invitation_sent_at' => now(),
                ]);
            } else {
                // Create new user
                $userData = [
                    'position_id'   => $request->position_id, // Already decoded in prepareForValidation
                    'name'          => $request->name,
                    'phone'         => $request->phone,
                    'date_of_birth' => $request->date_of_birth,
                    'hire_date'     => $request->hire_date,
                    'status'        => $request->status ?? 'active',
                    'notes'         => $request->notes,
                    'vessel_id'     => $vesselId,
                    'user_type'     => 'employee_of_vessel',
                ];

                if ($createWithoutEmail) {
                                                                            // Create without email - no account access
                    $userData['email']           = null;                    // Set to null or use a placeholder
                    $userData['login_permitted'] = false;                   // No account access
                    $userData['password']        = bcrypt(Str::random(64)); // Still need password for database constraint
                                                                            // No invitation token
                } else {
                    // Create with email - will send invitation
                    $userData['email']              = $email;
                    $userData['login_permitted']    = false;                   // Will be enabled when they accept invitation
                    $userData['password']           = bcrypt(Str::random(64)); // Temporary password - user will set their own when accepting invitation
                    $userData['invitation_token']   = $invitationToken;
                    $userData['invitation_sent_at'] = now();
                }

                $crewMember = User::create($userData);
            }

            // Only create salary compensation if not skipped
            $skipSalary = $request->boolean('skip_salary') ?? false;

            if (! $skipSalary) {
                $salaryData = [
                    'compensation_type' => $request->compensation_type,
                    'fixed_amount'      => $request->compensation_type === 'fixed' ? (int) $request->fixed_amount : null,
                    'percentage'        => $request->compensation_type === 'percentage' ? $request->percentage : null,
                    'currency'          => $request->currency,
                    'payment_frequency' => $request->payment_frequency,
                    'is_active'         => true,
                ];

                if (! $crewMember->salaryCompensations()->where('is_active', true)->exists()) {
                    $crewMember->salaryCompensations()->create($salaryData);
                }
            }

            // Also maintain the old vessel_users table for backward compatibility
            VesselUser::updateOrCreate(
                [
                    'vessel_id' => $vesselId,
                    'user_id'   => $crewMember->id,
                ],
                [
                    'role'      => 'viewer',
                    'is_active' => true,
                ]
            );

            // Create VesselUserRole based on position's vessel_role_access_id
            $vesselRoleAccessId = null;

            // Get role from position if crew member has a position assigned
            if ($crewMember->position_id) {
                $position = CrewPosition::find($crewMember->position_id);
                if ($position && $position->vessel_role_access_id) {
                    $vesselRoleAccessId = $position->vessel_role_access_id;
                }
            }

            // If no role from position, use default "normal" role
            if (! $vesselRoleAccessId) {
                $normalRole = VesselRoleAccess::where('name', 'normal')->where('is_active', true)->first();
                if ($normalRole) {
                    $vesselRoleAccessId = $normalRole->id;
                }
            }

            // Create VesselUserRole if we have a role access ID
            if ($vesselRoleAccessId) {
                VesselUserRole::updateOrCreate(
                    [
                        'vessel_id' => $vesselId,
                        'user_id'   => $crewMember->id,
                    ],
                    [
                        'vessel_role_access_id' => $vesselRoleAccessId,
                        'is_active'             => true,
                    ]
                );
            }

            // Send invitation email (only if email is provided and not creating without email)
            if (! $createWithoutEmail && $crewMember->email) {
                try {
                    Mail::to($crewMember->email)->send(
                        new CrewMemberInvitationMail(
                            $crewMember,
                            $vessel,
                            $invitationToken
                        )
                    );

                    // Track email send
                    InvitationEmail::create([
                        'user_id'          => $crewMember->id,
                        'vessel_id'        => $vesselId,
                        'email_type'       => 'invitation',
                        'invitation_token' => $invitationToken,
                        'sent_at'          => now(),
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't fail the request
                    Log::error('Failed to send crew member invitation email', [
                        'user_id'   => $crewMember->id,
                        'vessel_id' => $vesselId,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            // Log the create action
            AuditLogAction::logCreate(
                $crewMember,
                'Crew Member',
                $crewMember->name,
                $vesselId
            );

            // Set appropriate success message based on whether email was provided
            $successMessage = $createWithoutEmail || ! $crewMember->email
                ? "Crew member '{$crewMember->name}' has been created successfully. They do not have system access."
                : "Invitation sent to '{$crewMember->email}'. They will receive an email to accept the invitation.";

            Log::info('CrewMemberController::store - Success', [
                'crew_member_id'       => $crewMember->id,
                'crew_member_name'     => $crewMember->name,
                'vessel_id'            => $vesselId,
                'create_without_email' => $createWithoutEmail,
            ]);

            return redirect()
                ->route('panel.crew-members.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $successMessage)
                ->with('notification_delay', 5);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('CrewMemberController::store - Validation Error', [
                'errors'      => $e->errors(),
                'request_url' => $request->fullUrl(),
            ]);

            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Validation failed. Please check your input.')
                ->with('notification_delay', 0);
        } catch (\Exception $e) {
            Log::error('CrewMemberController::store - Exception', [
                'error'       => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => $e->getTraceAsString(),
                'request_url' => $request->fullUrl(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create crew member: ' . $e->getMessage())
                ->with('notification_delay', 0);
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
            'crewMember'         => new CrewMemberResource($crewMember),
            'vessels'            => Vessel::select('id', 'name')->get()->map(function ($vessel) {
                return [
                    'id'   => $this->hashId($vessel->id, 'vessel'),
                    'name' => $vessel->name,
                ];
            }),
            'positions'          => CrewPosition::where(function ($query) use ($vesselId) {
                $query->where('vessel_id', $vesselId)
                    ->orWhereNull('vessel_id'); // Include global positions (NULL vessel_id)
            })->select('id', 'name')->get()->map(function ($position) {
                return [
                    'id'   => $this->hashId($position->id, 'crewposition'),
                    'name' => $position->name,
                ];
            }),
            'statuses'           => [
                'active'   => 'Active',
                'inactive' => 'Inactive',
                'on_leave' => 'On Leave',
            ],
            'currencies'         => Currency::active()->orderBy('name')->get(['code', 'name', 'symbol'])->toArray(),
            'paymentFrequencies' => [
                'weekly'    => 'Weekly',
                'bi_weekly' => 'Bi-weekly',
                'monthly'   => 'Monthly',
                'quarterly' => 'Quarterly',
                'annually'  => 'Annually',
            ],
        ]);
    }

    public function update(UpdateCrewMemberRequest $request, $vessel, User $crewMember)
    {
        try {
            Log::info('CrewMemberController::update - Start', [
                'crew_member_id' => $crewMember->id,
                'vessel_param'   => $vessel,
                'request_url'    => $request->fullUrl(),
                'request_method' => $request->method(),
            ]);

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');
            if ($crewMember->vessel_id !== $vesselId) {
                Log::warning('CrewMemberController::update - Unauthorized access', [
                    'crew_member_id'        => $crewMember->id,
                    'crew_member_vessel_id' => $crewMember->vessel_id,
                    'request_vessel_id'     => $vesselId,
                ]);
                abort(403, 'Unauthorized access to crew member.');
            }

            // Get the vessel model for email sending
            $vessel = Vessel::findOrFail($vesselId);

            // Store original state for change detection BEFORE update
            $originalCrewMember = $crewMember->replicate();

            // Check if user has an existing account (not just a crew member account)
            $hasExistingAccount = $crewMember->hasExistingAccount();

            // Handle password update
            // Note: position_id is already decoded by UpdateCrewMemberRequest::prepareForValidation()
            $updateData = [
                'position_id'   => $request->position_id,
                'name'          => $request->name,
                'phone'         => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'hire_date'     => $request->hire_date,
                'status'        => $request->status,
                'notes'         => $request->notes,
            ];

            // Handle email update - only if user doesn't have existing account
            // For existing accounts, email shouldn't be changed through crew member update
            if (! $hasExistingAccount && $request->filled('email')) {
                $email = strtolower(trim($request->email));

                // Check if email is already used by another user
                $emailUser = User::where('email', $email)->where('id', '!=', $crewMember->id)->first();
                if ($emailUser) {
                    return back()
                        ->withInput()
                        ->with('error', 'This email is already registered to another user.')
                        ->with('notification_delay', 0);
                }

                $updateData['email'] = $email;
            }

            // Handle login settings
            // Password changes are no longer supported - users must use password reset
            $wasLoginPermitted             = $crewMember->login_permitted;
            $isEnablingAccess              = $request->login_permitted && ! $wasLoginPermitted;
            $updateData['login_permitted'] = $request->login_permitted ?? false;

            // If disabling login, clear temporary password
            if (! $updateData['login_permitted']) {
                $updateData['temporary_password'] = 'temp_' . time();
            } else {
                // If enabling login and user doesn't have existing account, ensure email is set
                if (! $hasExistingAccount && ! $request->filled('email')) {
                    return back()
                        ->withInput()
                        ->with('error', 'Email is required when enabling system access.')
                        ->with('notification_delay', 0);
                }

                // If enabling system access for the first time, prepare invitation
                if ($isEnablingAccess && ! $hasExistingAccount) {
                    // Generate invitation token if user doesn't have one or has already accepted
                    if (! $crewMember->invitation_token || $crewMember->invitation_accepted_at) {
                        $invitationToken                      = Str::random(64);
                        $updateData['invitation_token']       = $invitationToken;
                        $updateData['invitation_accepted_at'] = null; // Reset acceptance if re-inviting
                    } else {
                        $invitationToken = $crewMember->invitation_token;
                    }

                    // Set invitation sent date
                    $updateData['invitation_sent_at'] = now();
                    // Initially set login_permitted to false - they need to accept invitation first
                    // This matches the create flow where users must accept invitation before they can log in
                    $updateData['login_permitted'] = false;
                }
            }

            $crewMember->update($updateData);

            // Send invitation email if system access was just enabled
            if ($isEnablingAccess && ! $hasExistingAccount && $crewMember->email) {
                // Create VesselUserRole based on position's vessel_role_access_id
                $vesselRoleAccessId = null;

                // Get role from position if crew member has a position assigned
                if ($crewMember->position_id) {
                    $position = CrewPosition::find($crewMember->position_id);
                    if ($position && $position->vessel_role_access_id) {
                        $vesselRoleAccessId = $position->vessel_role_access_id;
                    }
                }

                // If no role from position, use default "normal" role
                if (! $vesselRoleAccessId) {
                    $normalRole = VesselRoleAccess::where('name', 'normal')->where('is_active', true)->first();
                    if ($normalRole) {
                        $vesselRoleAccessId = $normalRole->id;
                    }
                }

                // Create VesselUserRole if we have a role access ID
                if ($vesselRoleAccessId) {
                    VesselUserRole::updateOrCreate(
                        [
                            'vessel_id' => $vesselId,
                            'user_id'   => $crewMember->id,
                        ],
                        [
                            'vessel_role_access_id' => $vesselRoleAccessId,
                            'is_active'             => true,
                        ]
                    );
                }

                try {
                    $invitationToken = $crewMember->invitation_token;

                    Mail::to($crewMember->email)->send(
                        new CrewMemberInvitationMail(
                            $crewMember,
                            $vessel,
                            $invitationToken
                        )
                    );

                    // Track email send
                    InvitationEmail::create([
                        'user_id'          => $crewMember->id,
                        'vessel_id'        => $vesselId,
                        'email_type'       => 'invitation',
                        'invitation_token' => $invitationToken,
                        'sent_at'          => now(),
                    ]);

                    Log::info('CrewMemberController::update - Invitation email sent', [
                        'user_id'   => $crewMember->id,
                        'vessel_id' => $vesselId,
                        'email'     => $crewMember->email,
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't fail the request
                    Log::error('Failed to send crew member invitation email on update', [
                        'user_id'   => $crewMember->id,
                        'vessel_id' => $vesselId,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            // Handle salary compensation only if not skipped
            if (! $request->skip_salary) {
                // Extract salary compensation data
                // Note: fixed_amount comes from MoneyInput as integer (cents), no conversion needed
                $salaryData = [
                    'compensation_type' => $request->compensation_type,
                    'fixed_amount'      => $request->compensation_type === 'fixed' ? (int) $request->fixed_amount : null,
                    'percentage'        => $request->compensation_type === 'percentage' ? $request->percentage : null,
                    'currency'          => $request->currency,
                    'payment_frequency' => $request->payment_frequency,
                    'is_active'         => true,
                ];

                // Update or create salary compensation
                $crewMember->salaryCompensations()->updateOrCreate(
                    ['is_active' => true],
                    $salaryData
                );
            }

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($crewMember, $originalCrewMember);
            AuditLogAction::logUpdate(
                $crewMember,
                $changedFields,
                'Crew Member',
                $crewMember->name,
                $vesselId
            );

            Log::info('CrewMemberController::update - Success', [
                'crew_member_id'   => $crewMember->id,
                'crew_member_name' => $crewMember->name,
                'vessel_id'        => $vesselId,
            ]);

            // Set appropriate success message
            $successMessage = "Crew member '{$crewMember->name}' has been updated successfully.";

            // If invitation was sent, add that to the message
            if ($isEnablingAccess && ! $hasExistingAccount && $crewMember->email) {
                $successMessage = "Invitation sent to '{$crewMember->email}'. They will receive an email to accept the invitation and set their password.";
            }

            return redirect()
                ->route('panel.crew-members.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $successMessage)
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('CrewMemberController::update - Validation Error', [
                'errors'         => $e->errors(),
                'request_url'    => $request->fullUrl(),
                'crew_member_id' => $crewMember->id ?? null,
            ]);

            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Validation failed. Please check your input.')
                ->with('notification_delay', 0);
        } catch (\Exception $e) {
            Log::error('CrewMemberController::update - Exception', [
                'error'          => $e->getMessage(),
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
                'trace'          => $e->getTraceAsString(),
                'request_url'    => $request->fullUrl(),
                'crew_member_id' => $crewMember->id ?? null,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update crew member: ' . $e->getMessage())
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function destroy(DeleteCrewMemberRequest $request, $vessel, User $crewMember)
    {
        try {
            Log::info('CrewMemberController::destroy - Start', [
                'crew_member_id'   => $crewMember->id,
                'crew_member_name' => $crewMember->name,
                'vessel_param'     => $vessel,
                'request_url'      => $request->fullUrl(),
                'request_method'   => $request->method(),
                'route_params'     => $request->route()?->parameters(),
            ]);

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');

            Log::info('CrewMemberController::destroy - Vessel ID', [
                'vessel_id'             => $vesselId,
                'crew_member_vessel_id' => $crewMember->vessel_id,
            ]);

            // Check if user has access to this vessel (through VesselUserRole or VesselUser)
            // Allow deletion/unlinking if user has access to this vessel, even if not created on it
            $hasVesselRoleAccess = VesselUserRole::where('user_id', $crewMember->id)
                ->where('vessel_id', $vesselId)
                ->where('is_active', true)
                ->exists();

            $hasVesselUserAccess = VesselUser::where('user_id', $crewMember->id)
                ->where('vessel_id', $vesselId)
                ->where('is_active', true)
                ->exists();

            $isCrewMemberOnVessel = $crewMember->vessel_id === $vesselId;

            // User must have access to this vessel (through role access, vessel user, or as crew member)
            if (! $hasVesselRoleAccess && ! $hasVesselUserAccess && ! $isCrewMemberOnVessel) {
                Log::warning('CrewMemberController::destroy - Unauthorized access', [
                    'crew_member_id'         => $crewMember->id,
                    'crew_member_vessel_id'  => $crewMember->vessel_id,
                    'request_vessel_id'      => $vesselId,
                    'has_vessel_role_access' => $hasVesselRoleAccess,
                    'has_vessel_user_access' => $hasVesselUserAccess,
                ]);
                abort(403, 'Unauthorized access to crew member. User does not have access to this vessel.');
            }

            $vesselModel = Vessel::findOrFail($vesselId);

            if ($vesselModel->owner_id === $crewMember->id) {
                return back()
                    ->with('error', "Cannot delete vessel owner '{$crewMember->name}'. Transfer ownership before removing this user.")
                    ->with('notification_delay', 0);
            }

            $crewMemberName = $crewMember->name;

            // Check conditions BEFORE removing access to determine if we should fully delete or just unlink
            // Count vessel access BEFORE removing current vessel access
            $vesselRoleCount = VesselUserRole::where('user_id', $crewMember->id)
                ->where('is_active', true)
                ->count();

            $vesselUserCount = VesselUser::where('user_id', $crewMember->id)
                ->where('is_active', true)
                ->count();

            // Check if user has access to OTHER vessels (excluding current vessel)
            $hasOtherVesselRoles = VesselUserRole::where('user_id', $crewMember->id)
                ->where('vessel_id', '!=', $vesselId)
                ->where('is_active', true)
                ->exists();

            $hasOtherVesselUsers = VesselUser::where('user_id', $crewMember->id)
                ->where('vessel_id', '!=', $vesselId)
                ->where('is_active', true)
                ->exists();

            // Check if user owns other vessels
            $ownsOtherVessels = Vessel::where('owner_id', $crewMember->id)
                ->where('id', '!=', $vesselId)
                ->exists();

            // Check if user was created on this vessel (crew member with this vessel_id)
            $wasCreatedOnThisVessel = $crewMember->vessel_id === $vesselId;

            // Check if user has account access (login_permitted)
            $hasAccountAccess = $crewMember->login_permitted === true;

            // Check if user has transactions
            $hasTransactions = $crewMember->transactions()->count() > 0;

            // Determine if we should fully delete or just unlink
            // FULL DELETE conditions:
            // 1. User was created on this vessel (vessel_id matches)
            // 2. User has NO account access (login_permitted is false)
            // 3. User has NO other vessel access (no other VesselUserRole or VesselUser)
            // 4. User doesn't own other vessels
            // 5. User has no transactions
            $shouldFullDelete = $wasCreatedOnThisVessel
            && ! $hasAccountAccess
            && ! $hasOtherVesselRoles
            && ! $hasOtherVesselUsers
            && ! $ownsOtherVessels
            && ! $hasTransactions;

            Log::info('CrewMemberController::destroy - Delete Decision', [
                'crew_member_id'             => $crewMember->id,
                'was_created_on_this_vessel' => $wasCreatedOnThisVessel,
                'has_account_access'         => $hasAccountAccess,
                'has_other_vessel_roles'     => $hasOtherVesselRoles,
                'has_other_vessel_users'     => $hasOtherVesselUsers,
                'owns_other_vessels'         => $ownsOtherVessels,
                'has_transactions'           => $hasTransactions,
                'should_full_delete'         => $shouldFullDelete,
            ]);

            if ($shouldFullDelete) {
                // FULL DELETE: Remove all vessel access and delete user completely
                $crewMemberId = $crewMember->id;

                Log::info('CrewMemberController::destroy - Full Delete', [
                    'crew_member_id'   => $crewMemberId,
                    'crew_member_name' => $crewMemberName,
                ]);

                // Check if crew member has transactions - prevent full delete if they do
                if ($hasTransactions) {
                    return back()->with('error', "Cannot delete crew member '{$crewMemberName}' because they have transactions. They will be unlinked from this vessel instead.")
                        ->with('notification_delay', 0); // Persistent error
                }

                // Log the delete action BEFORE deletion
                AuditLogAction::logDelete(
                    $crewMember,
                    'Crew Member',
                    $crewMemberName,
                    $vesselId
                );

                // Remove all vessel access first
                VesselUserRole::where('user_id', $crewMemberId)
                    ->where('vessel_id', $vesselId)
                    ->delete();

                VesselUser::where('user_id', $crewMemberId)
                    ->where('vessel_id', $vesselId)
                    ->delete();

                // Delete the user completely
                $crewMember->delete();

                $successMessage = "Crew member '{$crewMemberName}' has been deleted successfully.";

                Log::info('CrewMemberController::destroy - Full Delete Success', [
                    'crew_member_id'   => $crewMemberId,
                    'crew_member_name' => $crewMemberName,
                    'vessel_id'        => $vesselId,
                ]);
            } else {
                // UNLINK: Only remove vessel access, keep user record
                Log::info('CrewMemberController::destroy - Unlink from Vessel', [
                    'crew_member_id'   => $crewMember->id,
                    'crew_member_name' => $crewMemberName,
                    'reason'           => ! $wasCreatedOnThisVessel ? 'not_created_on_vessel' : ($hasAccountAccess ? 'has_account_access' : ($hasOtherVesselRoles || $hasOtherVesselUsers ? 'has_other_vessels' : 'has_transactions')),
                ]);

                // Log the unlink action
                AuditLogAction::logUpdate(
                    $crewMember,
                    ['vessel_id' => $vesselId, 'position_id' => $crewMember->position_id],
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

                // If user was created on this vessel, clear crew member data
                if ($wasCreatedOnThisVessel) {
                    $crewMember->update([
                        'vessel_id'   => null,
                        'position_id' => null,
                        'hire_date'   => null,
                        'status'      => null,
                    ]);
                }

                $successMessage = "Crew member '{$crewMemberName}' has been removed from this vessel.";

                Log::info('CrewMemberController::destroy - Unlink Success', [
                    'crew_member_id'   => $crewMember->id,
                    'crew_member_name' => $crewMemberName,
                    'vessel_id'        => $vesselId,
                ]);
            }

            return redirect()
                ->route('panel.crew-members.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $successMessage)
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('CrewMemberController::destroy - Model Not Found', [
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
                'request_url'    => $request->fullUrl(),
                'crew_member_id' => $crewMember->id ?? null,
            ]);

            return back()
                ->with('error', 'Crew member not found. Please refresh the page and try again.')
                ->with('notification_delay', 0);
        } catch (\Exception $e) {
            Log::error('CrewMemberController::destroy - Exception', [
                'error'          => $e->getMessage(),
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
                'trace'          => $e->getTraceAsString(),
                'request_url'    => $request->fullUrl(),
                'crew_member_id' => $crewMember->id ?? null,
            ]);

            return back()
                ->with('error', 'Failed to delete crew member: ' . $e->getMessage())
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
            ->get(['id', 'name', 'document_number'])
            ->map(function ($crewMember) {
                return [
                    'id'              => $this->hashId($crewMember->id, 'user-id'),
                    'name'            => $crewMember->name,
                    'document_number' => $crewMember->document_number,
                ];
            });

        return response()->json($crewMembers);
    }

    /**
     * Cancel an invitation for a crew member.
     */
    public function cancelInvitation(Request $request, string $vessel, User $crewMember)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');
        $vessel   = Vessel::findOrFail($vesselId);

        // Verify this is a pending invitation
        if (! $crewMember->invitation_token || $crewMember->invitation_accepted_at) {
            return redirect()->back()->with('error', 'This invitation is not pending or has already been accepted.');
        }

        // Verify the crew member belongs to this vessel
        if ($crewMember->vessel_id !== $vesselId) {
            abort(403, 'This crew member does not belong to this vessel.');
        }

        $user = $crewMember;

        // Send cancellation email if email exists
        if ($user->email) {
            try {
                Mail::to($user->email)->send(
                    new CrewMemberInvitationCancelledMail($user, $vessel)
                );

                // Track cancellation email
                InvitationEmail::create([
                    'user_id'          => $user->id,
                    'vessel_id'        => $vesselId,
                    'email_type'       => 'cancellation',
                    'invitation_token' => null,
                    'sent_at'          => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send cancellation email', [
                    'user_id'   => $user->id,
                    'vessel_id' => $vesselId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // Clear invitation fields
        $user->update([
            'invitation_token'   => null,
            'invitation_sent_at' => null,
        ]);

        // Log the action
        AuditLogAction::logUpdate(
            $user,
            [],
            'Crew Member',
            $user->name,
            $vesselId
        );

        return redirect()->back()->with('success', 'Invitation cancelled successfully. Email notification sent.');
    }

    /**
     * Resend invitation email for a crew member.
     */
    public function resendInvitation(Request $request, string $vessel, User $crewMember)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');
        $vessel   = Vessel::findOrFail($vesselId);

        // Verify this is a pending invitation
        if (! $crewMember->invitation_token || $crewMember->invitation_accepted_at) {
            return redirect()->back()->with('error', 'This invitation is not pending or has already been accepted.');
        }

        // Verify the crew member belongs to this vessel
        if ($crewMember->vessel_id !== $vesselId) {
            abort(403, 'This crew member does not belong to this vessel.');
        }

        $user = $crewMember;

        // Check email send count (max 3)
        $emailCount = InvitationEmail::where('user_id', $user->id)
            ->where('vessel_id', $vesselId)
            ->where('email_type', 'invitation')
            ->count();

        if ($emailCount >= 3) {
            return redirect()->back()->with('error', 'Maximum resend limit (3) reached for this invitation.');
        }

        if (! $user->email) {
            return redirect()->back()->with('error', 'User does not have an email address.');
        }

        // Generate new invitation token
        $invitationToken = Str::random(64);
        $user->update([
            'invitation_token'   => $invitationToken,
            'invitation_sent_at' => now(),
        ]);

        // Send invitation email
        try {
            Mail::to($user->email)->send(
                new CrewMemberInvitationMail(
                    $user,
                    $vessel,
                    $invitationToken
                )
            );

            // Track email send
            InvitationEmail::create([
                'user_id'          => $user->id,
                'vessel_id'        => $vesselId,
                'email_type'       => 'invitation',
                'invitation_token' => $invitationToken,
                'sent_at'          => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resend crew member invitation email', [
                'user_id'   => $user->id,
                'vessel_id' => $vesselId,
                'error'     => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to send invitation email. Please try again.');
        }

        // Log the action
        AuditLogAction::logUpdate(
            $user,
            [],
            'Crew Member',
            $user->name,
            $vesselId
        );

        return redirect()->back()->with('success', 'Invitation email resent successfully.');
    }
}
