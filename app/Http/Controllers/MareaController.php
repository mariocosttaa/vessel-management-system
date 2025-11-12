<?php

namespace App\Http\Controllers;

use App\Models\Marea;
use App\Models\MareaDistributionProfile;
use App\Models\MareaDistributionItem;
use App\Models\MareaCrew;
use App\Models\MareaQuantityReturn;
use App\Models\Transaction;
use App\Models\User;
use App\Actions\AuditLogAction;
use App\Actions\EmailNotificationAction;
use App\Traits\HasTranslations;
use App\Http\Controllers\Concerns\HashesIds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MareaController extends Controller
{
    use HasTranslations, HashesIds;
    /**
     * Display a listing of mareas for the current vessel.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view mareas using config permissions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Check mareas.view permission from config
        $userRole = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (!($permissions['mareas.view'] ?? false)) {
            abort(403, 'You do not have permission to view mareas.');
        }

        // Main data query - filter by vessel
        $query = Marea::query()->where('vessel_id', $vesselId);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('marea_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where(function ($q) use ($request) {
                $q->where('estimated_departure_date', '>=', $request->date_from)
                  ->orWhere('actual_departure_date', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->where(function ($q) use ($request) {
                $q->where('estimated_return_date', '<=', $request->date_to)
                  ->orWhere('actual_return_date', '<=', $request->date_to);
            });
        }

        // Sorting - default to created_at descending (newest first)
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $query->orderBy($sortField, $sortDirection);

        // Eager load relationships for performance
        $mareas = $query->with([
            'vessel:id,name',
            'distributionProfile:id,name',
            'createdBy:id,name',
        ])->paginate(15)->withQueryString();

        // Current filters
        $filters = $request->only([
            'search',
            'status',
            'date_from',
            'date_to',
            'sort',
            'direction',
        ]);

        // Status options
        $statuses = [
            'preparing' => 'Preparing',
            'at_sea' => 'At Sea',
            'returned' => 'Returned',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
        ];

        // Get vessel settings for default currency
        $vesselSetting = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        return Inertia::render('Mareas/Index', [
            'mareas' => $mareas->through(function ($marea) {
                // Count transactions for this marea
                $transactionCount = \App\Models\Transaction::where('marea_id', $marea->id)->count();

                return [
                    'id' => $this->hashId($marea->id, 'marea-id'),
                    'marea_number' => $marea->marea_number,
                    'name' => $marea->name,
                    'description' => $marea->description,
                    'status' => $marea->status,
                    'estimated_departure_date' => $marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : null,
                    'estimated_return_date' => $marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : null,
                    'actual_departure_date' => $marea->actual_departure_date ? $marea->actual_departure_date->format('Y-m-d') : null,
                    'actual_return_date' => $marea->actual_return_date ? $marea->actual_return_date->format('Y-m-d') : null,
                    'total_income' => $marea->total_income,
                    'total_expenses' => $marea->total_expenses,
                    'net_result' => $marea->net_result,
                    'created_at' => $marea->created_at ? $marea->created_at->format('Y-m-d H:i:s') : null,
                    'transaction_count' => $transactionCount,
                ];
            }),
            'statuses' => $statuses,
            'filters' => $filters,
            'defaultCurrency' => $defaultCurrency,
        ]);
    }

    /**
     * Show the form for creating a new marea.
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check permissions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        $userRole = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (!($permissions['mareas.create'] ?? false)) {
            abort(403, 'You do not have permission to create mareas.');
        }

        // Get next marea number for this vessel
        $nextMareaNumber = Marea::getNextMareaNumber($vesselId);

        return response()->json([
            'next_marea_number' => $nextMareaNumber,
        ]);
    }

    /**
     * Store a newly created marea.
     */
    public function store(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter
            $vessel = $request->route('vessel');

            // Handle both route model binding (object) and hashed ID (string)
            if (is_object($vessel)) {
                $vesselId = $vessel->id;
            } elseif (is_numeric($vessel)) {
                $vesselId = (int) $vessel;
            } else {
                // Decode hashed vessel ID
                $decoded = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                if (!$vesselId) {
                    abort(404, 'Vessel not found.');
                }
            }

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.create'] ?? false)) {
                abort(403, 'You do not have permission to create mareas.');
            }

            // Validate request - only required fields
            $validated = $request->validate([
                'marea_number' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('mareas', 'marea_number')->whereNull('deleted_at'),
                ],
                'estimated_departure_date' => 'nullable|date',
                'estimated_return_date' => 'nullable|date|after_or_equal:estimated_departure_date',
            ]);

            $vessel = \App\Models\Vessel::find($vesselId);
            $vesselSetting = \App\Models\VesselSetting::getForVessel($vesselId);
            $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

            $marea = Marea::create([
                'vessel_id' => $vesselId,
                'marea_number' => $validated['marea_number'],
                'estimated_departure_date' => $validated['estimated_departure_date'] ?? null,
                'estimated_return_date' => $validated['estimated_return_date'] ?? null,
                'distribution_profile_id' => null, // Not set during creation
                'use_calculation' => false, // Default to false, can be enabled later
                'currency' => $defaultCurrency,
                'house_of_zeros' => 2, // Default
                'status' => 'preparing',
                'created_by' => $user->id,
            ]);

            // Log the create action
            AuditLogAction::logCreate(
                $marea,
                'Marea',
                $marea->marea_number,
                $vesselId
            );

            // Create email notification for other users (not the user who created it)
            // Note: We don't send notification on creation, only when marea goes to sea
            // But we create the notification record for tracking

            return redirect()
                ->route('panel.mareas.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'mareaId' => $marea->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Marea ':number' has been created successfully.", [
                    'number' => $marea->marea_number
                ]));
        } catch (\Exception $e) {
            Log::error('Marea creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to create marea: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Display the specified marea.
     */
    public function show(Request $request, $vessel, $mareaId)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        if (!$vesselId) {
            // Fallback: try to get from route parameter
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
        }

        // CRITICAL: Get marea ID directly from route parameter, not from method parameter
        // The method parameter might be getting resolved incorrectly, so we get it directly from the route
        $mareaIdFromRoute = $request->route('mareaId');
        // Unhash marea ID if it's a hashed string
        if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
            $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
        } else {
            $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
        }

        // Force fresh query with both vessel_id and id to ensure correct marea
        $marea = Marea::where('vessel_id', $vesselId)
            ->where('id', $mareaId)
            ->firstOrFail();

        // Check permissions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        $userRole = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (!($permissions['mareas.view'] ?? false)) {
            abort(403, 'You do not have permission to view mareas.');
        }

        // Load all relationships
        $marea->load([
            'vessel:id,name,currency_code',
            'distributionProfile.items',
            'distributionItems.profileItem',
            'createdBy:id,name',
            'crew' => function ($query) {
                $query->with('user:id,name,email');
            },
            'quantityReturns',
            'transactions' => function ($query) {
                $query->with([
                    'category:id,name,type,color',
                    'supplier:id,company_name',
                    'crewMember:id,name,email',
                ])->orderBy('transaction_date', 'desc');
            },
        ]);

        // Also load crewMembers if it's a BelongsToMany relationship
        if (method_exists($marea, 'crewMembers')) {
            $marea->load('crewMembers:id,name,email');
        }

        // Calculate distribution
        $distribution = $marea->calculateDistribution();

        // Get related data for transaction creation modal
        $categories = \App\Models\TransactionCategory::orderBy('name')->get();
        $suppliers = \App\Models\Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = \App\Models\User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $vatProfiles = \App\Models\VatProfile::active()->orderBy('name')->get();
        $vesselSetting = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? \App\Models\VatProfile::find($vesselSetting->vat_profile_id)
            : \App\Models\VatProfile::where('is_default', true)->first();
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Get salary category
        $salaryCategory = \App\Models\TransactionCategory::where('name', 'Salários')
            ->where('type', 'expense')
            ->first();

        // Get salary compensation data for crew members in this marea
        $crewSalaryData = [];
        foreach ($marea->crew as $crew) {
            $salaryCompensation = \App\Models\SalaryCompensation::where('user_id', $crew->user_id)
                ->where('is_active', true)
                ->first();

            if ($salaryCompensation) {
                // Calculate amount based on compensation type
                $calculatedAmount = null;
                if ($salaryCompensation->compensation_type === 'fixed') {
                    $calculatedAmount = $salaryCompensation->fixed_amount;
                } elseif ($salaryCompensation->compensation_type === 'percentage' && $salaryCompensation->percentage) {
                    // Calculate percentage of marea total income
                    $totalIncome = $marea->total_income;
                    $percentage = (float) $salaryCompensation->percentage;
                    $calculatedAmount = (int) round(($totalIncome * $percentage) / 100);
                }

                $crewSalaryData[$crew->user_id] = [
                    'id' => $salaryCompensation->id,
                    'compensation_type' => $salaryCompensation->compensation_type,
                    'fixed_amount' => $salaryCompensation->fixed_amount,
                    'percentage' => $salaryCompensation->percentage ? (float) $salaryCompensation->percentage : null,
                    'currency' => $salaryCompensation->currency,
                    'calculated_amount' => $calculatedAmount,
                ];
            }
        }

        // Load all distribution profiles for instant selection
        $distributionProfiles = MareaDistributionProfile::orderBy('name')->get();

        // Count transactions for deletion warning
        $transactionCount = \App\Models\Transaction::where('marea_id', $marea->id)->count();

        return Inertia::render('Mareas/Show', [
            'transactionCount' => $transactionCount,
            'defaultCurrency' => $defaultCurrency,
            'distributionProfiles' => $distributionProfiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'is_default' => $profile->is_default,
                ];
            }),
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'color' => $category->color,
                ];
            }),
            'suppliers' => $suppliers->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'company_name' => $supplier->company_name,
                    'description' => $supplier->description ?? null,
                ];
            }),
            'crewMembers' => $crewMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ];
            }),
            'vatProfiles' => $vatProfiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'country_id' => $profile->country_id,
                ];
            }),
            'defaultVatProfile' => $defaultVatProfile ? [
                'id' => $defaultVatProfile->id,
                'name' => $defaultVatProfile->name,
                'percentage' => (float) $defaultVatProfile->percentage,
                'country_id' => $defaultVatProfile->country_id,
            ] : null,
            'salaryCategory' => $salaryCategory ? [
                'id' => $salaryCategory->id,
                'name' => $salaryCategory->name,
                'type' => $salaryCategory->type,
                'color' => $salaryCategory->color,
            ] : null,
            'crewSalaryData' => $crewSalaryData,
            'marea' => [
                'id' => $this->hashId($marea->id, 'marea-id'),
                'marea_number' => $marea->marea_number,
                'name' => $marea->name,
                'description' => $marea->description,
                'status' => $marea->status,
                'estimated_departure_date' => $marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : null,
                'estimated_return_date' => $marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : null,
                'actual_departure_date' => $marea->actual_departure_date ? $marea->actual_departure_date->format('Y-m-d') : null,
                'actual_return_date' => $marea->actual_return_date ? $marea->actual_return_date->format('Y-m-d') : null,
                'closed_at' => $marea->closed_at ? $marea->closed_at->format('Y-m-d H:i:s') : null,
                'distribution_profile_id' => $marea->distribution_profile_id ? $this->hashId($marea->distribution_profile_id, 'mareadistributionprofile-id') : null,
                'distribution_profile' => $marea->distributionProfile ? [
                    'id' => $this->hashId($marea->distributionProfile->id, 'mareadistributionprofile-id'),
                    'name' => $marea->distributionProfile->name,
                ] : null,
                'use_calculation' => $marea->use_calculation ?? true,
                'currency' => $marea->currency ?? $defaultCurrency,
                'house_of_zeros' => $marea->house_of_zeros ?? 2,
                'total_income' => $marea->total_income,
                'total_expenses' => $marea->total_expenses,
                'net_result' => $marea->net_result,
                'formatted_total_income' => $marea->formatted_total_income,
                'formatted_total_expenses' => $marea->formatted_total_expenses,
                'formatted_net_result' => $marea->formatted_net_result,
                'distribution' => $distribution,
                'distribution_items' => $marea->distributionItems->map(function ($item) use ($marea) {
                    return [
                        'id' => $this->hashId($item->id, 'mareadistributionitem-id'),
                        'profile_item_id' => $item->profile_item_id ? $this->hashId($item->profile_item_id, 'mareadistributionprofileitem-id') : null,
                        'order_index' => $item->order_index,
                        'name' => $item->name,
                        'description' => $item->description,
                        'value_type' => $item->value_type,
                        'value_amount' => $item->value_amount,
                        'reference_item_id' => $item->reference_item_id ? $this->hashId($item->reference_item_id, 'mareadistributionitem-id') : null,
                        'operation' => $item->operation,
                        'reference_operation_item_id' => $item->reference_operation_item_id ? $this->hashId($item->reference_operation_item_id, 'mareadistributionitem-id') : null,
                    ];
                }),
                'distribution_profile_items' => $marea->distributionProfile && $marea->distributionProfile->items ? $marea->distributionProfile->items->map(function ($item) {
                    return [
                        'id' => $this->hashId($item->id, 'mareadistributionprofileitem-id'),
                        'order_index' => $item->order_index,
                        'name' => $item->name,
                        'description' => $item->description,
                        'value_type' => $item->value_type,
                        'value_amount' => $item->value_amount,
                        'reference_item_id' => $item->reference_item_id ? $this->hashId($item->reference_item_id, 'mareadistributionprofileitem-id') : null,
                        'operation' => $item->operation,
                        'reference_operation_item_id' => $item->reference_operation_item_id ? $this->hashId($item->reference_operation_item_id, 'mareadistributionprofileitem-id') : null,
                    ];
                }) : [],
                'crew_members' => $marea->crew->map(function ($crew) {
                    return [
                        'id' => $this->hashId($crew->user->id, 'user-id'),
                        'name' => $crew->user->name,
                        'email' => $crew->user->email,
                        'notes' => $crew->notes ?? null,
                    ];
                }),
                'quantity_returns' => $marea->quantityReturns->map(function ($qr) {
                    return [
                        'id' => $this->hashId($qr->id, 'mareaquantityreturn-id'),
                        'name' => $qr->name,
                        'quantity' => $qr->quantity,
                        'notes' => $qr->notes,
                    ];
                }),
                'transactions' => $marea->transactions->map(function ($transaction) {
                    return [
                        'id' => $this->hashId($transaction->id, 'transaction-id'),
                        'transaction_number' => $transaction->transaction_number,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'amount_per_unit' => $transaction->amount_per_unit,
                        'quantity' => $transaction->quantity,
                        'vat_amount' => $transaction->vat_amount,
                        'total_amount' => $transaction->total_amount,
                        'currency' => $transaction->currency,
                        'transaction_date' => $transaction->transaction_date?->format('Y-m-d'),
                        'description' => $transaction->description,
                        'category' => $transaction->category ? [
                            'id' => $this->hashId($transaction->category->id, 'transactioncategory-id'),
                            'name' => $transaction->category->name,
                            'type' => $transaction->category->type,
                            'color' => $transaction->category->color,
                        ] : null,
                        'supplier' => $transaction->supplier ? [
                            'id' => $this->hashId($transaction->supplier->id, 'supplier-id'),
                            'company_name' => $transaction->supplier->company_name,
                        ] : null,
                        'crew_member_id' => $transaction->crew_member_id ? $this->hashId($transaction->crew_member_id, 'user-id') : null,
                        'crew_member' => $transaction->crewMember ? [
                            'id' => $this->hashId($transaction->crewMember->id, 'user-id'),
                            'name' => $transaction->crewMember->name,
                            'email' => $transaction->crewMember->email,
                        ] : null,
                    ];
                }),
                'created_at' => $marea->created_at?->format('Y-m-d H:i:s'),
                'created_by' => $marea->createdBy ? [
                    'id' => $this->hashId($marea->createdBy->id, 'user-id'),
                    'name' => $marea->createdBy->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified marea.
     */
    public function edit(Request $request, $vessel, $mareaId)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        if (!$vesselId) {
            // Fallback: try to get from route parameter
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
        }

        // CRITICAL: Get marea ID directly from route parameter
        $mareaIdFromRoute = $request->route('mareaId');
        // Unhash marea ID if it's a hashed string
        if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
            $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
        } else {
            $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
        }

        // Force fresh query with both vessel_id and id to ensure correct marea
        $marea = Marea::where('vessel_id', $vesselId)
            ->where('id', $mareaId)
            ->firstOrFail();

        // Check permissions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        $userRole = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (!($permissions['mareas.edit'] ?? false)) {
            abort(403, 'You do not have permission to edit mareas.');
        }

        // Cannot edit closed or cancelled mareas
        if ($marea->status === 'closed' || $marea->status === 'cancelled') {
            abort(403, 'Cannot edit a closed or cancelled marea.');
        }

        // Get distribution profiles
        $distributionProfiles = MareaDistributionProfile::orderBy('name')->get();

        return Inertia::render('Mareas/Edit', [
            'marea' => [
                'id' => $this->hashId($marea->id, 'marea-id'),
                'marea_number' => $marea->marea_number,
                'name' => $marea->name,
                'description' => $marea->description,
                'estimated_departure_date' => $marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : null,
                'estimated_return_date' => $marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : null,
                'distribution_profile_id' => $marea->distribution_profile_id ? $this->hashId($marea->distribution_profile_id, 'mareadistributionprofile-id') : null,
                'use_calculation' => $marea->use_calculation ?? true,
                'currency' => $marea->currency,
                'house_of_zeros' => $marea->house_of_zeros ?? 2,
                'status' => $marea->status,
            ],
            'distributionProfiles' => $distributionProfiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'is_default' => $profile->is_default,
                ];
            }),
        ]);
    }

    /**
     * Update the specified marea.
     */
    public function update(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vessel = $request->route('vessel');

                // Handle both route model binding (object) and hashed ID (string)
                if (is_object($vessel)) {
                    $vesselId = $vessel->id;
                } elseif (is_numeric($vessel)) {
                    $vesselId = (int) $vessel;
                } else {
                    // Decode hashed vessel ID
                    $decoded = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                    $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                    if (!$vesselId) {
                        abort(404, 'Vessel not found.');
                    }
                }
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Cannot edit closed or cancelled mareas
            if ($marea->status === 'closed' || $marea->status === 'cancelled') {
                abort(403, 'Cannot edit a closed or cancelled marea.');
            }

            // Store original state for change detection
            $originalMarea = $marea->replicate();

            // Validate request
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'estimated_departure_date' => 'nullable|date',
                'estimated_return_date' => 'nullable|date|after_or_equal:estimated_departure_date',
                'distribution_profile_id' => 'nullable|exists:marea_distribution_profiles,id',
                'use_calculation' => 'nullable|boolean',
                'currency' => 'nullable|string|size:3',
                'house_of_zeros' => 'nullable|integer|min:0|max:4',
            ]);

            $marea->update([
                'name' => $validated['name'] ?? null,
                'description' => $validated['description'] ?? null,
                'estimated_departure_date' => $validated['estimated_departure_date'] ?? null,
                'estimated_return_date' => $validated['estimated_return_date'] ?? null,
                'distribution_profile_id' => $validated['distribution_profile_id'] ?? null,
                'use_calculation' => $validated['use_calculation'] ?? $marea->use_calculation ?? true,
                'currency' => $validated['currency'] ?? $marea->currency,
                'house_of_zeros' => $validated['house_of_zeros'] ?? $marea->house_of_zeros ?? 2,
            ]);

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($marea, $originalMarea);
            AuditLogAction::logUpdate(
                $marea,
                $changedFields,
                'Marea',
                $marea->marea_number,
                $vesselId
            );

            return redirect()
                ->route('panel.mareas.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'mareaId' => $marea->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Marea ':number' has been updated successfully.", [
                    'number' => $marea->marea_number
                ]));
        } catch (\Exception $e) {
            Log::error('Marea update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to update marea: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Remove the specified marea.
     */
    public function destroy(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.delete'] ?? false)) {
                abort(403, 'You do not have permission to delete mareas.');
            }

            $mareaNumber = $marea->marea_number;

            // Count transactions before deletion
            $transactionCount = \App\Models\Transaction::where('marea_id', $marea->id)->count();

            // Log the delete action BEFORE deletion
            AuditLogAction::logDelete(
                $marea,
                'Marea',
                $mareaNumber,
                $vesselId
            );

            // Soft delete all transactions associated with this marea (they will appear in recycle bin)
            \App\Models\Transaction::where('marea_id', $marea->id)->delete();

            // Soft delete the marea (will appear in recycle bin)
            $marea->delete();

            $message = $this->transFrom('notifications', "Marea ':number' has been deleted successfully.", [
                'number' => $mareaNumber
            ]);
            if ($transactionCount > 0) {
                $message = $this->transFrom('notifications', "Marea ':number' has been deleted successfully. :count transaction(s) associated with this marea have also been deleted.", [
                    'number' => $mareaNumber,
                    'count' => $transactionCount
                ]);
            }

            return redirect()
                ->route('panel.mareas.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Marea deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to delete marea: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Mark marea as at sea.
     */
    public function markAtSea(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.manage-status'] ?? false)) {
                abort(403, 'You do not have permission to manage marea status.');
            }

            $validated = $request->validate([
                'date' => 'nullable|date',
            ]);

            $marea->markAsAtSea($validated['date'] ?? null);

            // Reload marea to get updated dates
            $marea->refresh();

            // Create email notification for other users (not the user who marked it as at sea)
            try {
                EmailNotificationAction::createNotification(
                    type: 'marea_started',
                    subjectType: Marea::class,
                    subjectId: $marea->id,
                    vesselId: $vesselId,
                    actionByUserId: $user->id,
                    subjectData: [
                        'marea_number' => $marea->marea_number,
                        'name' => $marea->name,
                        'started_at' => $marea->actual_departure_date?->toIso8601String(),
                        'expected_return_date' => $marea->estimated_return_date?->toIso8601String(),
                    ]
                );
            } catch (\Exception $e) {
                // Log error but don't fail the status change
                Log::warning('Failed to create email notification for marea started', [
                    'marea_id' => $marea->id,
                    'vessel_id' => $vesselId,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()
                ->route('panel.mareas.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'mareaId' => $marea->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Marea ':number' has been marked as at sea.", [
                    'number' => $marea->marea_number
                ]));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to mark marea as at sea: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Mark marea as returned.
     */
    public function markReturned(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.manage-status'] ?? false)) {
                abort(403, 'You do not have permission to manage marea status.');
            }

            $validated = $request->validate([
                'date' => 'nullable|date',
            ]);

            $marea->markAsReturned($validated['date'] ?? null);

            // Reload marea to get updated dates
            $marea->refresh();

            // Create email notification for other users (not the user who marked it as returned)
            try {
                EmailNotificationAction::createNotification(
                    type: 'marea_completed',
                    subjectType: Marea::class,
                    subjectId: $marea->id,
                    vesselId: $vesselId,
                    actionByUserId: $user->id,
                    subjectData: [
                        'marea_number' => $marea->marea_number,
                        'name' => $marea->name,
                        'started_at' => $marea->actual_departure_date?->toIso8601String(),
                        'returned_at' => $marea->actual_return_date?->toIso8601String(),
                    ]
                );
            } catch (\Exception $e) {
                // Log error but don't fail the status change
                Log::warning('Failed to create email notification for marea completed', [
                    'marea_id' => $marea->id,
                    'vessel_id' => $vesselId,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()
                ->route('panel.mareas.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'mareaId' => $marea->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Marea ':number' has been marked as returned.", [
                    'number' => $marea->marea_number
                ]));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to mark marea as returned: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Close the marea.
     */
    public function close(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.manage-status'] ?? false)) {
                abort(403, 'You do not have permission to manage marea status.');
            }

            $marea->close();

            return redirect()
                ->route('panel.mareas.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'mareaId' => $marea->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Marea ':number' has been closed.", [
                    'number' => $marea->marea_number
                ]));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to close marea: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Cancel the marea.
     */
    public function cancel(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.manage-status'] ?? false)) {
                abort(403, 'You do not have permission to manage marea status.');
            }

            $marea->cancel();

            return redirect()
                ->route('panel.mareas.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'mareaId' => $marea->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Marea ':number' has been cancelled.", [
                    'number' => $marea->marea_number
                ]));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to cancel marea: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Add a transaction to the marea.
     */
    public function addTransaction(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }
            $vesselId = (int) $vesselId; // Ensure integer

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Cannot add transactions to closed or cancelled mareas
            if ($marea->status === 'closed' || $marea->status === 'cancelled') {
                abort(403, 'Cannot add transactions to a closed or cancelled marea.');
            }

            // Unhash transaction_id from request before validation
            $transactionIdHashed = $request->input('transaction_id');
            $transactionId = $this->unhashId($transactionIdHashed, 'transaction-id');
            if (!$transactionId) {
                return back()->with('error', $this->transFrom('notifications', 'Invalid transaction ID.'));
            }

            $validated = $request->validate([
                'transaction_id' => [
                    'required',
                    Rule::exists('transactions', 'id')->where(function ($query) use ($vesselId) {
                        $query->where('vessel_id', $vesselId);
                    }),
                ],
            ]);

            // Merge unhashed ID for validation
            $request->merge(['transaction_id' => $transactionId]);

            // CRITICAL: Ensure we're querying with the correct vessel_id (as integer)
            $transaction = Transaction::where('id', $transactionId)
                ->where('vessel_id', $vesselId)
                ->firstOrFail();

            // Update transaction with marea_id
            $transaction->update(['marea_id' => $marea->id]);

            return back()
                ->with('success', $this->transFrom('notifications', 'Transaction has been added to the marea.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to add transaction: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Remove a transaction from the marea.
     */
    public function removeTransaction(Request $request, $vessel, $mareaId, $transaction)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Cannot remove transactions from closed or cancelled mareas
            if ($marea->status === 'closed' || $marea->status === 'cancelled') {
                abort(403, 'Cannot remove transactions from a closed or cancelled marea.');
            }

            // Get transaction ID from route parameter and unhash it
            $transactionParam = is_object($transaction) ? $transaction->id : $transaction;
            if (!is_numeric($transactionParam)) {
                $transactionId = $this->unhashId($transactionParam, 'transaction-id');
            } else {
                $transactionId = (int) $transactionParam;
            }
            if (!$transactionId) {
                abort(404, 'Transaction not found.');
            }
            $transaction = Transaction::where('marea_id', $marea->id)->findOrFail($transactionId);

            // Remove marea_id from transaction
            $transaction->update(['marea_id' => null]);

            return back()
                ->with('success', $this->transFrom('notifications', 'Transaction has been removed from the marea.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to remove transaction: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Add a crew member to the marea.
     */
    public function addCrew(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Cannot add crew to closed or cancelled mareas
            if ($marea->status === 'closed' || $marea->status === 'cancelled') {
                abort(403, 'Cannot add crew to a closed or cancelled marea.');
            }

            // Unhash user_id from request before validation
            $userIdHashed = $request->input('user_id');
            $userId = $this->unhashId($userIdHashed, 'user-id');
            if (!$userId) {
                return back()->with('error', $this->transFrom('notifications', 'Invalid user ID.'));
            }

            $validated = $request->validate([
                'user_id' => [
                    'required',
                    Rule::exists('users', 'id'),
                ],
                'notes' => 'nullable|string',
            ]);

            // Merge unhashed ID for validation
            $request->merge(['user_id' => $userId]);

            // Verify user has access to this vessel (either through vessel_id or vessel_user_roles)
            $crewUser = User::findOrFail($userId);

            // Check if user has access to vessel through roles OR is a direct crew member
            $hasAccessThroughRoles = $crewUser->hasAccessToVessel($vesselId);
            $isDirectCrewMember = (int) $crewUser->vessel_id === (int) $vesselId;

            if (!$hasAccessThroughRoles && !$isDirectCrewMember) {
                abort(403, 'User does not belong to this vessel.');
            }

            // Check if already added
            if ($marea->crew()->where('user_id', $userId)->exists()) {
                return back()
                    ->with('error', $this->transFrom('notifications', 'Crew member is already assigned to this marea.'));
            }

            // Add crew member using MareaCrew model
            \App\Models\MareaCrew::create([
                'marea_id' => $marea->id,
                'user_id' => $userId,
                'notes' => $validated['notes'] ?? null,
            ]);

            return back()
                ->with('success', $this->transFrom('notifications', 'Crew member has been added to the marea.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to add crew member: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Remove a crew member from the marea.
     */
    public function removeCrew(Request $request, $vessel, $mareaId, $crewMember)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Cannot remove crew from closed or cancelled mareas
            if ($marea->status === 'closed' || $marea->status === 'cancelled') {
                abort(403, 'Cannot remove crew from a closed or cancelled marea.');
            }

            // Get user ID from route parameter and unhash it
            $userParam = is_object($crewMember) ? $crewMember->id : $crewMember;
            if (!is_numeric($userParam)) {
                $userId = $this->unhashId($userParam, 'user-id');
            } else {
                $userId = (int) $userParam;
            }
            if (!$userId) {
                abort(404, 'Crew member not found.');
            }

            // Remove crew member
            $marea->crewMembers()->detach($userId);

            return back()
                ->with('success', $this->transFrom('notifications', 'Crew member has been removed from the marea.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to remove crew member: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Add a quantity return to the marea.
     */
    public function addQuantityReturn(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Can only add quantity returns to returned mareas (not closed)
            if ($marea->status !== 'returned') {
                abort(403, 'Can only add quantity returns to returned mareas. Closed mareas cannot be modified.');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'quantity' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            // Create quantity return
            MareaQuantityReturn::create([
                'marea_id' => $marea->id,
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
            ]);

            return back()
                ->with('success', $this->transFrom('notifications', 'Quantity return has been added to the marea.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to add quantity return: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Remove a quantity return from the marea.
     */
    public function removeQuantityReturn(Request $request, $vessel, $mareaId, $quantityReturn)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Cannot remove quantity returns from closed mareas
            if ($marea->status === 'closed') {
                abort(403, 'Cannot remove quantity returns from a closed marea.');
            }

            // Get quantity return ID from route parameter and unhash it
            $quantityReturnParam = is_object($quantityReturn) ? $quantityReturn->id : $quantityReturn;
            if (!is_numeric($quantityReturnParam)) {
                $quantityReturnId = $this->unhashId($quantityReturnParam, 'mareaquantityreturn-id');
            } else {
                $quantityReturnId = (int) $quantityReturnParam;
            }
            if (!$quantityReturnId) {
                abort(404, 'Quantity return not found.');
            }
            $quantityReturn = MareaQuantityReturn::where('marea_id', $marea->id)->findOrFail($quantityReturnId);

            $quantityReturn->delete();

            return back()
                ->with('success', $this->transFrom('notifications', 'Quantity return has been removed from the marea.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to remove quantity return: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Get available transactions for adding to marea.
     */
    public function getAvailableTransactions(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }
            $vesselId = (int) $vesselId; // Ensure integer

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            // Get available transactions (not linked to any marea, or linked to this marea to allow re-linking)
            // CRITICAL: Ensure we're filtering by the correct vessel_id (as integer)
            $availableTransactions = Transaction::where('vessel_id', $vesselId)
                ->where(function ($query) use ($marea) {
                    $query->whereNull('marea_id')
                        ->orWhere('marea_id', $marea->id);
                })
                ->with(['category:id,name,type,color'])
                ->orderBy('transaction_date', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $this->hashId($transaction->id, 'transaction-id'),
                        'transaction_number' => $transaction->transaction_number,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'total_amount' => $transaction->total_amount,
                        'currency' => $transaction->currency,
                        'transaction_date' => $transaction->transaction_date?->format('Y-m-d'),
                        'description' => $transaction->description,
                        'category' => $transaction->category ? [
                            'id' => $this->hashId($transaction->category->id, 'transactioncategory-id'),
                            'name' => $transaction->category->name,
                            'type' => $transaction->category->type,
                            'color' => $transaction->category->color,
                        ] : null,
                    ];
                });

            return response()->json([
                'transactions' => $availableTransactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch available transactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available crew members for adding to marea.
     */
    public function getAvailableCrew(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            // Get crew members already assigned to this marea
            $assignedCrewIds = $marea->crew()->pluck('user_id')->toArray();

            // Get available crew members:
            // 1. Users who are crew members (have vessel_id and position_id) for this vessel
            // 2. Users who have access to this vessel through vessel_user_roles
            $crewMembersFromVessel = User::where('vessel_id', $vesselId)
                ->whereNotNull('position_id')
                ->whereNotIn('id', $assignedCrewIds)
                ->get(['id', 'name', 'email']);

            // Get users with access through vessel_user_roles
            $vessel = \App\Models\Vessel::find($vesselId);
            $usersThroughRoles = $vessel ? $vessel->usersThroughRoles()
                ->whereNotIn('users.id', $assignedCrewIds)
                ->get(['users.id', 'users.name', 'users.email']) : collect();

            // Merge and deduplicate
            $availableCrewMembers = $crewMembersFromVessel->concat($usersThroughRoles)
                ->unique('id')
                ->sortBy('name')
                ->values()
                ->map(function ($user) {
                    return [
                        'id' => $this->hashId($user->id, 'user-id'),
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });

            return response()->json([
                'crew_members' => $availableCrewMembers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch available crew members: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store distribution items for a marea (custom overrides).
     */
    public function storeDistributionItems(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from request attributes
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // Get marea ID from route
            $mareaIdFromRoute = $request->route('mareaId');
            $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);

            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Validate request
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.order_index' => 'required|integer|min:1',
                'items.*.name' => 'required|string|max:255',
                'items.*.description' => 'nullable|string|max:500',
                'items.*.value_type' => 'required|in:base_total_income,base_total_expense,fixed_amount,percentage_of_income,percentage_of_expense,reference_item',
                'items.*.value_amount' => 'nullable|numeric',
                'items.*.reference_item_id' => 'nullable|integer',
                'items.*.reference_item_order_index' => 'nullable|integer', // Alternative: use order_index
                'items.*.operation' => 'required|in:set,add,subtract,multiply,divide',
                'items.*.reference_operation_item_id' => 'nullable|integer',
                'items.*.reference_operation_item_order_index' => 'nullable|integer', // Alternative: use order_index
                'items.*.profile_item_id' => 'nullable|integer|exists:marea_distribution_profile_items,id',
            ]);

            // Delete existing distribution items for this marea
            $marea->distributionItems()->delete();

            // Create items in two passes: first create all, then update references
            $createdItems = [];
            $orderToIdMap = [];

            // First pass: create all items
            foreach ($validated['items'] as $itemData) {
                $item = $marea->distributionItems()->create([
                    'order_index' => $itemData['order_index'],
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'value_type' => $itemData['value_type'],
                    'value_amount' => $itemData['value_amount'] ?? null,
                    'reference_item_id' => null, // Will be set in second pass
                    'operation' => $itemData['operation'],
                    'reference_operation_item_id' => null, // Will be set in second pass
                    'profile_item_id' => $itemData['profile_item_id'] ?? null,
                ]);
                $createdItems[] = ['item' => $item, 'data' => $itemData];
                $orderToIdMap[$itemData['order_index']] = $item->id;
            }

            // Second pass: update references using order_index mapping
            foreach ($createdItems as $createdItemData) {
                $item = $createdItemData['item'];
                $data = $createdItemData['data'];
                $updates = [];

                // Map reference_item_id from order_index (preferred) or use direct ID
                if (isset($data['reference_item_order_index']) && $data['reference_item_order_index']) {
                    $refOrderIndex = $data['reference_item_order_index'];
                    if (isset($orderToIdMap[$refOrderIndex])) {
                        $updates['reference_item_id'] = $orderToIdMap[$refOrderIndex];
                    }
                } elseif (isset($data['reference_item_id']) && $data['reference_item_id']) {
                    // Direct ID (legacy support)
                    $updates['reference_item_id'] = $data['reference_item_id'];
                }

                // Map reference_operation_item_id from order_index (preferred) or use direct ID
                if (isset($data['reference_operation_item_order_index']) && $data['reference_operation_item_order_index']) {
                    $refOrderIndex = $data['reference_operation_item_order_index'];
                    if (isset($orderToIdMap[$refOrderIndex])) {
                        $updates['reference_operation_item_id'] = $orderToIdMap[$refOrderIndex];
                    }
                } elseif (isset($data['reference_operation_item_id']) && $data['reference_operation_item_id']) {
                    // Direct ID (legacy support)
                    $updates['reference_operation_item_id'] = $data['reference_operation_item_id'];
                }

                // Update item with references if any
                if (!empty($updates)) {
                    $item->update($updates);
                }
            }

            return redirect()
                ->route('panel.mareas.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'mareaId' => $marea->getRouteKey()])
                ->with('success', $this->transFrom('notifications', 'Distribution calculation override has been saved successfully.'));
        } catch (\Exception $e) {
            Log::error('Failed to store distribution items', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to save distribution items: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Create a salary payment transaction for a crew member.
     */
    public function createSalaryPayment(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (!($permissions['mareas.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit mareas.');
            }

            // Cannot add salary payments to closed or cancelled mareas
            if ($marea->status === 'closed' || $marea->status === 'cancelled') {
                abort(403, 'Cannot add salary payments to a closed or cancelled marea.');
            }

            // Unhash crew_member_id from request before validation
            $crewMemberIdHashed = $request->input('crew_member_id');
            $crewMemberId = $this->unhashId($crewMemberIdHashed, 'user-id');
            if (!$crewMemberId) {
                return back()->with('error', $this->transFrom('notifications', 'Invalid crew member ID.'));
            }

            // Validate request
            $validated = $request->validate([
                'crew_member_id' => [
                    'required',
                    Rule::exists('users', 'id')->where(function ($query) use ($vesselId) {
                        $query->where('vessel_id', $vesselId);
                    }),
                ],
                'amount' => ['required', 'integer', 'min:1'],
                'transaction_date' => ['required', 'date', 'before_or_equal:today'],
                'description' => ['nullable', 'string', 'max:500'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);

            // Merge unhashed ID for use in creation
            $validated['crew_member_id'] = $crewMemberId;

            // Get salary category
            $salaryCategory = \App\Models\TransactionCategory::where('name', 'Salários')
                ->where('type', 'expense')
                ->firstOrFail();

            // Get vessel settings for currency
            $vesselSetting = \App\Models\VesselSetting::getForVessel($vesselId);
            $vessel = \App\Models\Vessel::find($vesselId);
            $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

            // Create salary payment transaction
            $transaction = Transaction::create([
                'vessel_id' => $vesselId,
                'marea_id' => $marea->id,
                'category_id' => $salaryCategory->id,
                'type' => 'expense',
                'amount' => $validated['amount'],
                'vat_amount' => 0,
                'total_amount' => $validated['amount'],
                'currency' => $defaultCurrency,
                'house_of_zeros' => $marea->house_of_zeros ?? 2,
                'vat_profile_id' => null, // Expenses don't have VAT
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? 'Salary payment',
                'notes' => $validated['notes'] ?? null,
                'crew_member_id' => $validated['crew_member_id'],
                'status' => 'completed',
                'created_by' => $user->id,
            ]);

            return back()
                ->with('success', $this->transFrom('notifications', 'Salary payment has been created successfully.'));
        } catch (\Exception $e) {
            Log::error('Salary payment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to create salary payment: :message', [
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Get salary compensation data for a crew member.
     */
    public function getCrewSalaryData(Request $request, $vessel, $mareaId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (!$vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }

            // CRITICAL: Get marea ID directly from route parameter
            $mareaIdFromRoute = $request->route('mareaId');
            // Unhash marea ID if it's a hashed string
            if ($mareaIdFromRoute && !is_numeric($mareaIdFromRoute)) {
                $mareaId = $this->unhashId($mareaIdFromRoute, 'marea-id');
            } else {
                $mareaId = (int) ($mareaIdFromRoute ?? $mareaId);
            }

            // Force fresh query with both vessel_id and id to ensure correct marea
            $marea = Marea::where('vessel_id', $vesselId)
                ->where('id', $mareaId)
                ->firstOrFail();

            // Check permissions
            if (!$user || !$user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $crewMemberIdHashed = $request->input('crew_member_id');
            if (!$crewMemberIdHashed) {
                return response()->json(['error' => 'Crew member ID is required.'], 400);
            }

            // Unhash crew member ID from request
            $crewMemberId = $this->unhashId($crewMemberIdHashed, 'user-id');
            if (!$crewMemberId) {
                return response()->json(['error' => 'Invalid crew member ID.'], 400);
            }

            // Get salary compensation
            $salaryCompensation = \App\Models\SalaryCompensation::where('user_id', $crewMemberId)
                ->where('is_active', true)
                ->first();

            if (!$salaryCompensation) {
                return response()->json([
                    'compensation_type' => null,
                    'fixed_amount' => null,
                    'percentage' => null,
                ]);
            }

            // Calculate amount based on compensation type
            $amount = null;
            if ($salaryCompensation->compensation_type === 'fixed') {
                $amount = $salaryCompensation->fixed_amount;
            } elseif ($salaryCompensation->compensation_type === 'percentage' && $salaryCompensation->percentage) {
                // Calculate percentage of marea total income
                $totalIncome = $marea->total_income;
                $percentage = (float) $salaryCompensation->percentage;
                $amount = (int) round(($totalIncome * $percentage) / 100);
            }

            return response()->json([
                'compensation_type' => $salaryCompensation->compensation_type,
                'fixed_amount' => $salaryCompensation->fixed_amount,
                'percentage' => $salaryCompensation->percentage ? (float) $salaryCompensation->percentage : null,
                'calculated_amount' => $amount,
                'currency' => $salaryCompensation->currency,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get crew salary data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to get crew salary data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
