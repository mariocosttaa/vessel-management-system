<?php
namespace App\Http\Controllers;

use App\Actions\AuditLogAction;
use App\Actions\MoneyAction;
use App\Http\Controllers\Concerns\HashesIds;
use App\Models\Maintenance;
use App\Models\Movimentation;
use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    use HasTranslations, HashesIds;
    /**
     * Display a listing of maintenances for the current vessel.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view maintenances using config permissions
        // Note: Vessel access is already checked by EnsureVesselAccess middleware

        // Check maintenances.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['maintenances.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Main data query - filter by vessel
        $query = Maintenance::query()->where('vessel_id', $vesselId);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('maintenance_number', 'like', "%{$search}%")
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
                $q->where('start_date', '>=', $request->date_from)
                    ->orWhere('end_date', '>=', $request->date_from);
            });
        }

        if ($request->filled('date_to')) {
            $query->where(function ($q) use ($request) {
                $q->where('start_date', '<=', $request->date_to)
                    ->orWhere('end_date', '<=', $request->date_to);
            });
        }

        // Sorting - default to created_at descending (newest first)
        $sortField     = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $query->orderBy($sortField, $sortDirection);

        // Eager load relationships for performance
        $maintenances = $query->with([
            'vessel:id,name',
            'createdBy:id,name',
        ])
        ->withCount('transactions')
        ->withSum(['transactions as total_expenses' => function ($query) {
            $query->where('type', 'expense');
        }], 'total_amount')
        ->paginate(15)
        ->withQueryString();

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
            'open'      => 'Open',
            'closed'    => 'Closed',
            'cancelled' => 'Cancelled',
        ];

        // Get vessel settings for default currency
        $vesselSetting   = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel          = \App\Models\Vessel::find($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        return Inertia::render('Maintenances/Index', [
            'maintenances'    => $maintenances->through(function ($maintenance) {
                return [
                    'id'                 => $this->hashId($maintenance->id, 'maintenance'),
                    'maintenance_number' => $maintenance->maintenance_number,
                    'name'               => $maintenance->name,
                    'description'        => $maintenance->description,
                    'status'             => $maintenance->status,
                    'start_date'         => $maintenance->start_date ? $maintenance->start_date->format('Y-m-d') : null,
                    'end_date'           => $maintenance->end_date ? $maintenance->end_date->format('Y-m-d') : null,
                    'total_expenses'     => $maintenance->total_expenses,
                    'created_at'         => $maintenance->created_at ? $maintenance->created_at->format('Y-m-d H:i:s') : null,
                    'transaction_count'  => $maintenance->transactions_count,
                ];
            }),
            'statuses'        => $statuses,
            'filters'         => $filters,
            'defaultCurrency' => $defaultCurrency,
        ]);
    }

    /**
     * Show the form for creating a new maintenance.
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['maintenances.create'] ?? false)) {
            abort(403, 'You do not have permission to create maintenances.');
        }

        // Get next maintenance number for this vessel with details
        $nextMaintenanceDetails = Maintenance::getNextMaintenanceNumber($vesselId, true);

        return response()->json([
            'next_maintenance_number' => $nextMaintenanceDetails['next_maintenance_number'],
            'has_soft_deleted_conflict' => $nextMaintenanceDetails['has_soft_deleted_conflict'] ?? false,
            'suggested_number' => $nextMaintenanceDetails['suggested_number'] ?? $nextMaintenanceDetails['next_maintenance_number'],
        ]);
    }

    /**
     * Store a newly created maintenance.
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
                $decoded  = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                if (! $vesselId) {
                    abort(404, 'Vessel not found.');
                }
            }

            // Check permissions
            if (! $user || ! $user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (! ($permissions['maintenances.create'] ?? false)) {
                abort(403, 'You do not have permission to create maintenances.');
            }

            // Validate request - only required fields
            // Accept numbers only or letters+numbers format
            $validated = $request->validate([
                'maintenance_number' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^([A-Za-z]*\d+[A-Za-z]*|\d+)$/', // Numbers only or letters+numbers (must contain at least one digit)
                    Rule::unique('maintenances', 'maintenance_number')->whereNull('deleted_at'),
                ],
                'start_date'         => 'required|date',
            ]);

            // Check if maintenance_number already exists (including soft-deleted)
            // Extract numeric part to check for conflicts
            $inputNumber = $validated['maintenance_number'];
            $inputNumericPart = $this->extractNumericPart($inputNumber);

            // Check for exact match first
            $existingMaintenance = Maintenance::withTrashed()->where('maintenance_number', $inputNumber)->first();
            if ($existingMaintenance) {
                if ($existingMaintenance->trashed()) {
                    // If it's soft-deleted, suggest using the auto-generated number instead
                    $nextMaintenanceDetails = Maintenance::getNextMaintenanceNumber($vesselId, true);
                    return back()
                        ->withInput()
                        ->withErrors([
                            'maintenance_number' => $this->transFrom('notifications', 'This maintenance number is already in use (even if deleted). Suggested number: :number', [
                                'number' => $nextMaintenanceDetails['suggested_number'],
                            ]),
                        ]);
                } else {
                    // If it's not deleted, it's a duplicate
                    return back()
                        ->withInput()
                        ->withErrors([
                            'maintenance_number' => $this->transFrom('notifications', 'This maintenance number is already in use. Please use a different number.'),
                        ]);
                }
            }

            // Check for numeric conflicts with soft-deleted records
            $softDeletedWithSameNumber = Maintenance::onlyTrashed()
                ->where('vessel_id', $vesselId)
                ->get()
                ->filter(function ($maintenance) use ($inputNumericPart) {
                    return $this->extractNumericPart($maintenance->maintenance_number) === $inputNumericPart;
                })
                ->first();

            if ($softDeletedWithSameNumber) {
                // Suggest next available number
                $nextMaintenanceDetails = Maintenance::getNextMaintenanceNumber($vesselId, true);
                return back()
                    ->withInput()
                    ->withErrors([
                        'maintenance_number' => $this->transFrom('notifications', 'A soft-deleted maintenance with this number exists. Suggested number: :number', [
                            'number' => $nextMaintenanceDetails['suggested_number'],
                        ]),
                    ]);
            }

            $vessel          = \App\Models\Vessel::find($vesselId);
            $vesselSetting   = \App\Models\VesselSetting::getForVessel($vesselId);
            $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

            $maintenance = Maintenance::create([
                'vessel_id'          => $vesselId,
                'maintenance_number' => $validated['maintenance_number'],
                'start_date'         => $validated['start_date'],
                'end_date'           => null, // End date will be set when finalizing
                'currency'           => $defaultCurrency,
                'house_of_zeros'     => 2, // Default
                'status'             => 'open',
                'created_by'         => $user->id,
            ]);

            // Log the create action
            AuditLogAction::logCreate(
                $maintenance,
                'Maintenance',
                $maintenance->maintenance_number,
                $vesselId
            );

            return redirect()
                ->route('panel.maintenances.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'maintenanceId' => $maintenance->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Maintenance ':number' has been created successfully.", [
                    'number' => $maintenance->maintenance_number,
                ]));
        } catch (\Exception $e) {
            Log::error('Maintenance creation failed', [
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to create maintenance: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Display the specified maintenance.
     */
    public function show(Request $request, $vessel, $maintenanceId)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        if (! $vesselId) {
            // Fallback: try to get from route parameter
            if (is_object($vessel)) {
                $vesselId = $vessel->id;
            } elseif (is_numeric($vessel)) {
                $vesselId = (int) $vessel;
            } else {
                // Decode hashed vessel ID
                $decoded  = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                if (! $vesselId) {
                    abort(404, 'Vessel not found.');
                }
            }
        }

        // CRITICAL: Get maintenance ID directly from route parameter
        $maintenanceIdFromRoute = $request->route('maintenanceId');
        // Unhash maintenance ID if it's a hashed string
        if ($maintenanceIdFromRoute && ! is_numeric($maintenanceIdFromRoute)) {
            $maintenanceId = $this->unhashId($maintenanceIdFromRoute, 'maintenance');
            if (! $maintenanceId) {
                abort(404, 'Maintenance not found.');
            }
        } else {
            $maintenanceId = (int) ($maintenanceIdFromRoute ?? $maintenanceId);
        }

        // Force fresh query with both vessel_id and id to ensure correct maintenance
        $maintenance = Maintenance::where('vessel_id', $vesselId)
            ->where('id', $maintenanceId)
            ->withCount('transactions')
            ->withSum(['transactions as total_expenses' => function ($query) {
                $query->where('type', 'expense');
            }], 'total_amount')
            ->firstOrFail();

        // Check permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['maintenances.view'] ?? false)) {
            abort(403, 'You do not have permission to view maintenances.');
        }

        // Load all relationships
        $maintenance->load([
            'vessel:id,name,currency_code',
            'createdBy:id,name',
            // Don't load transactions here - we'll load them separately with pagination
        ]);

        // Get related data for transaction creation modal
        // Get categories: system categories (vessel_id = null) + vessel-specific categories
        $categories  = \App\Models\MovimentationCategory::forVessel($vesselId)->orderBy('name')->get();
        $suppliers   = \App\Models\Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = \App\Models\User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $vatProfiles       = \App\Models\VatProfile::active()->orderBy('name')->get();
        $vesselSetting     = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel            = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? \App\Models\VatProfile::find($vesselSetting->vat_profile_id)
            : \App\Models\VatProfile::where('is_default', true)->first();
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Use the count from withCount to avoid N+1 query
        $transactionCount = $maintenance->transactions_count;

        // Cache total_expenses to avoid duplicate queries when accessing formatted_total_expenses
        $totalExpenses = $maintenance->total_expenses;

        // Load transactions with pagination and search
        $transactionsQuery = \App\Models\Movimentation::where('maintenance_id', $maintenance->id)
            ->with([
                'category:id,name,type,color',
                'supplier:id,company_name',
                'crewMember:id,name,email',
                'maintenance:id,maintenance_number',
            ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $transactionsQuery->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $transactions = $transactionsQuery->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Map transactions for frontend
        $transactionsData = $transactions->through(function ($transaction) {
            return [
                'id'                 => $this->hashId($transaction->id, 'movimentation'),
                'transaction_number' => $transaction->transaction_number,
                'type'               => $transaction->type,
                'amount'             => $transaction->amount,
                'amount_per_unit'    => $transaction->amount_per_unit,
                'quantity'           => $transaction->quantity,
                'vat_amount'         => $transaction->vat_amount,
                'total_amount'       => $transaction->total_amount,
                'currency'           => $transaction->currency,
                'transaction_date'   => $transaction->transaction_date?->format('Y-m-d'),
                'description'        => $transaction->description,
                'category'           => $transaction->category ? [
                    'id'    => $this->hashId($transaction->category->id, 'transactioncategory'),
                    'name'  => $transaction->category->translated_name,
                    'type'  => $transaction->category->type,
                    'color' => $transaction->category->color,
                ] : null,
                'supplier'           => $transaction->supplier ? [
                    'id'           => $this->hashId($transaction->supplier->id, 'supplier'),
                    'company_name' => $transaction->supplier->company_name,
                ] : null,
                'maintenance'        => $transaction->maintenance ? [
                    'id'                 => $this->hashId($transaction->maintenance->id, 'maintenance'),
                    'maintenance_number' => $transaction->maintenance->maintenance_number,
                ] : null,
            ];
        });

        return Inertia::render('Maintenances/Show', [
            'transactionCount'  => $transactionCount,
            'defaultCurrency'   => $defaultCurrency,
            'categories'        => $categories->map(function ($category) {
                return [
                    'id'    => $this->hashId($category->id, 'transactioncategory'),
                    'name'  => $category->translated_name,
                    'type'  => $category->type,
                    'color' => $category->color,
                ];
            }),
            'suppliers'         => $suppliers->map(function ($supplier) {
                return [
                    'id'           => $this->hashId($supplier->id, 'supplier-id'),
                    'company_name' => $supplier->company_name,
                    'description'  => $supplier->description ?? null,
                ];
            }),
            'crewMembers'       => $crewMembers->map(function ($member) {
                return [
                    'id'    => $member->id,
                    'name'  => $member->name,
                    'email' => $member->email,
                ];
            }),
            'vatProfiles'       => $vatProfiles->map(function ($profile) {
                return [
                    'id'         => $profile->id,
                    'name'       => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'country_id' => $profile->country_id,
                ];
            }),
            'defaultVatProfile' => $defaultVatProfile ? [
                'id'         => $defaultVatProfile->id,
                'name'       => $defaultVatProfile->name,
                'percentage' => (float) $defaultVatProfile->percentage,
                'country_id' => $defaultVatProfile->country_id,
            ] : null,
            'maintenance'       => [
                'id'                       => $this->hashId($maintenance->id, 'maintenance'),
                'maintenance_number'       => $maintenance->maintenance_number,
                'name'                     => $maintenance->name,
                'description'              => $maintenance->description,
                'status'                   => $maintenance->status,
                'start_date'               => $maintenance->start_date ? $maintenance->start_date->format('Y-m-d') : null,
                'end_date'                 => $maintenance->end_date ? $maintenance->end_date->format('Y-m-d') : null,
                'closed_at'                => $maintenance->closed_at?->format('Y-m-d H:i:s'),
                'currency'                 => $maintenance->currency ?? $defaultCurrency,
                'house_of_zeros'           => $maintenance->house_of_zeros ?? 2,
                'total_expenses'           => $totalExpenses,
                'formatted_total_expenses' => MoneyAction::format(
                    $totalExpenses,
                    $maintenance->getHouseOfZeros(),
                    $maintenance->getCurrency(),
                    true
                ),
                // Transactions are loaded separately with pagination
                'created_at'               => $maintenance->created_at?->format('Y-m-d H:i:s'),
                'created_by'               => $maintenance->createdBy ? [
                    'id'   => $maintenance->createdBy->id,
                    'name' => $maintenance->createdBy->name,
                ] : null,
            ],
            'transactions'      => $transactionsData, // Paginated transactions
        ]);
    }

    /**
     * Download maintenance PDF report.
     */
    public function downloadPdf(Request $request, $vessel, $maintenanceId)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view maintenances for this vessel
        // Note: Vessel access is already checked by EnsureVesselAccess middleware

        // Check maintenances.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['maintenances.view'] ?? false)) {
            abort(403, 'You do not have permission to view maintenances.');
        }

        // Get maintenance ID directly from route parameter
        $maintenanceIdFromRoute = $request->route('maintenanceId');
        // Unhash maintenance ID if it's a hashed string
        if ($maintenanceIdFromRoute && ! is_numeric($maintenanceIdFromRoute)) {
            $maintenanceIdInt = $this->unhashId($maintenanceIdFromRoute, 'maintenance');
        } else {
            $maintenanceIdInt = (int) ($maintenanceIdFromRoute ?? $maintenanceId);
        }

        // Force fresh query with both vessel_id and id to ensure correct maintenance
        $maintenance = Maintenance::where('vessel_id', $vesselId)
            ->where('id', $maintenanceIdInt)
            ->firstOrFail();

        // Get enable_colors from request (default to false)
        $enableColors = $request->boolean('enable_colors', false);

        return \App\Pdf\MaintenancePdf::download($maintenance, null, $enableColors, $user);
    }

    /**
     * Remove the specified maintenance.
     */
    public function destroy(Request $request, $vessel, $maintenanceId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (! $vesselId) {
                // Fallback: try to get from route parameter
                if (is_object($vessel)) {
                    $vesselId = $vessel->id;
                } elseif (is_numeric($vessel)) {
                    $vesselId = (int) $vessel;
                } else {
                    // Decode hashed vessel ID
                    $decoded  = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                    $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                    if (! $vesselId) {
                        abort(404, 'Vessel not found.');
                    }
                }
            }

            // CRITICAL: Get maintenance ID directly from route parameter
            $maintenanceIdFromRoute = $request->route('maintenanceId');
            // Unhash maintenance ID if it's a hashed string
            if ($maintenanceIdFromRoute && ! is_numeric($maintenanceIdFromRoute)) {
                $maintenanceId = $this->unhashId($maintenanceIdFromRoute, 'maintenance');
                if (! $maintenanceId) {
                    abort(404, 'Maintenance not found.');
                }
            } else {
                $maintenanceId = (int) ($maintenanceIdFromRoute ?? $maintenanceId);
            }

            // Force fresh query with both vessel_id and id to ensure correct maintenance
            $maintenance = Maintenance::where('vessel_id', $vesselId)
                ->where('id', $maintenanceId)
                ->firstOrFail();

            // Check permissions
            if (! $user || ! $user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (! ($permissions['maintenances.delete'] ?? false)) {
                abort(403, 'You do not have permission to delete maintenances.');
            }

            $maintenanceNumber = $maintenance->maintenance_number;

            // Count transactions before deletion
            $transactionCount = \App\Models\Movimentation::where('maintenance_id', $maintenance->id)->count();

            // Log the delete action BEFORE deletion
            AuditLogAction::logDelete(
                $maintenance,
                'Maintenance',
                $maintenanceNumber,
                $vesselId
            );

            // Soft delete all transactions associated with this maintenance (they will appear in recycle bin)
            \App\Models\Movimentation::where('maintenance_id', $maintenance->id)->delete();

            // Soft delete the maintenance (will appear in recycle bin)
            $maintenance->delete();

            $message = "Maintenance '{$maintenanceNumber}' has been deleted successfully.";
            if ($transactionCount > 0) {
                $message .= " {$transactionCount} transaction(s) associated with this maintenance have also been deleted.";
            }

            return redirect()
                ->route('panel.maintenances.index', ['vessel' => $vesselId])
                ->with('success', $this->transFrom('notifications', "Maintenance ':number' has been deleted successfully.", [
                    'number' => $maintenance->maintenance_number,
                ]));
        } catch (\Exception $e) {
            Log::error('Maintenance deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to delete maintenance: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Update the specified maintenance.
     */
    public function update(Request $request, $vessel, $maintenanceId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (! $vesselId) {
                $vessel = $request->route('vessel');

                // Handle both route model binding (object) and hashed ID (string)
                if (is_object($vessel)) {
                    $vesselId = $vessel->id;
                } elseif (is_numeric($vessel)) {
                    $vesselId = (int) $vessel;
                } else {
                    // Decode hashed vessel ID
                    $decoded  = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                    $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                    if (! $vesselId) {
                        abort(404, 'Vessel not found.');
                    }
                }
            }

            // CRITICAL: Get maintenance ID directly from route parameter
            $maintenanceIdFromRoute = $request->route('maintenanceId');
            // Unhash maintenance ID if it's a hashed string
            if ($maintenanceIdFromRoute && ! is_numeric($maintenanceIdFromRoute)) {
                $maintenanceId = $this->unhashId($maintenanceIdFromRoute, 'maintenance');
                if (! $maintenanceId) {
                    abort(404, 'Maintenance not found.');
                }
            } else {
                $maintenanceId = (int) ($maintenanceIdFromRoute ?? $maintenanceId);
            }

            // Force fresh query with both vessel_id and id to ensure correct maintenance
            $maintenance = Maintenance::where('vessel_id', $vesselId)
                ->where('id', $maintenanceId)
                ->firstOrFail();

            // Check permissions
            // Note: Vessel access is already checked by EnsureVesselAccess middleware

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (! ($permissions['maintenances.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit maintenances.');
            }

            // Cannot update closed or cancelled maintenances
            if ($maintenance->status === 'closed' || $maintenance->status === 'cancelled') {
                abort(403, 'Cannot update a closed or cancelled maintenance.');
            }

            // Validate request
            $validated = $request->validate([
                'start_date'  => 'nullable|date',
                'end_date'    => [
                    'nullable',
                    'date',
                    function ($attribute, $value, $fail) use ($request, $maintenance) {
                        // Use updated start_date if provided, otherwise use existing start_date
                        $startDate = $request->input('start_date') ?? $maintenance->start_date;
                        if ($value && $startDate && $value < $startDate) {
                            $fail('The end date must be after or equal to the start date.');
                        }
                    },
                ],
                'name'        => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]);

            // Store original state for change detection
            $originalMaintenance = $maintenance->replicate();

            // Update maintenance
            $maintenance->update($validated);

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($maintenance, $originalMaintenance);
            AuditLogAction::logUpdate(
                $maintenance,
                $changedFields,
                'Maintenance',
                $maintenance->maintenance_number,
                $vesselId
            );

            return back()
                ->with('success', $this->transFrom('notifications', "Maintenance ':number' has been updated successfully.", [
                    'number' => $maintenance->maintenance_number,
                ]));
        } catch (\Exception $e) {
            Log::error('Maintenance update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to update maintenance: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Finalize (close) the maintenance.
     */
    public function finalize(Request $request, $vessel, $maintenanceId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (! $vesselId) {
                // Fallback: try to get from route parameter
                if (is_object($vessel)) {
                    $vesselId = $vessel->id;
                } elseif (is_numeric($vessel)) {
                    $vesselId = (int) $vessel;
                } else {
                    // Decode hashed vessel ID
                    $decoded  = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                    $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                    if (! $vesselId) {
                        abort(404, 'Vessel not found.');
                    }
                }
            }

            // CRITICAL: Get maintenance ID directly from route parameter
            $maintenanceIdFromRoute = $request->route('maintenanceId');
            // Unhash maintenance ID if it's a hashed string
            if ($maintenanceIdFromRoute && ! is_numeric($maintenanceIdFromRoute)) {
                $maintenanceId = $this->unhashId($maintenanceIdFromRoute, 'maintenance');
                if (! $maintenanceId) {
                    abort(404, 'Maintenance not found.');
                }
            } else {
                $maintenanceId = (int) ($maintenanceIdFromRoute ?? $maintenanceId);
            }

            // Force fresh query with both vessel_id and id to ensure correct maintenance
            $maintenance = Maintenance::where('vessel_id', $vesselId)
                ->where('id', $maintenanceId)
                ->firstOrFail();

            // Check permissions
            // Note: Vessel access is already checked by EnsureVesselAccess middleware

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (! ($permissions['maintenances.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit maintenances.');
            }

            // Validate that end_date is set
            $validated = $request->validate([
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            // Store original state before updates
            $originalMaintenance = $maintenance->replicate();

            // Update end_date if provided
            if (isset($validated['end_date'])) {
                $maintenance->update(['end_date' => $validated['end_date']]);
            }

            // Close the maintenance
            $maintenance->close();

            // Refresh to get updated values
            $maintenance->refresh();

            // Get changed fields using the service method
            $changedFields = AuditLogAction::getChangedFields($maintenance, $originalMaintenance);

            // Log the finalize action
            AuditLogAction::logUpdate(
                $maintenance,
                $changedFields,
                'Maintenance',
                $maintenance->maintenance_number,
                $vesselId
            );

            return redirect()
                ->route('panel.maintenances.show', ['vessel' => $this->hashId($vesselId, 'vessel'), 'maintenanceId' => $maintenance->getRouteKey()])
                ->with('success', $this->transFrom('notifications', "Maintenance ':number' has been finalized.", [
                    'number' => $maintenance->maintenance_number,
                ]));
        } catch (\Exception $e) {
            Log::error('Maintenance finalization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to finalize maintenance: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Remove a transaction from the maintenance.
     */
    public function removeTransaction(Request $request, $vessel, $maintenanceId, $transaction)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Get vessel_id from route parameter or request attributes
            $vesselId = $request->attributes->get('vessel_id');
            if (! $vesselId) {
                // Fallback: try to get from route parameter
                if (is_object($vessel)) {
                    $vesselId = $vessel->id;
                } elseif (is_numeric($vessel)) {
                    $vesselId = (int) $vessel;
                } else {
                    // Decode hashed vessel ID
                    $decoded  = \App\Actions\General\EasyHashAction::decode($vessel, 'vessel-id');
                    $vesselId = $decoded && is_numeric($decoded) ? (int) $decoded : null;

                    if (! $vesselId) {
                        abort(404, 'Vessel not found.');
                    }
                }
            }

            // CRITICAL: Get maintenance ID directly from route parameter
            $maintenanceIdFromRoute = $request->route('maintenanceId');
            // Unhash maintenance ID if it's a hashed string
            if ($maintenanceIdFromRoute && ! is_numeric($maintenanceIdFromRoute)) {
                $maintenanceId = $this->unhashId($maintenanceIdFromRoute, 'maintenance');
                if (! $maintenanceId) {
                    abort(404, 'Maintenance not found.');
                }
            } else {
                $maintenanceId = (int) ($maintenanceIdFromRoute ?? $maintenanceId);
            }

            // Force fresh query with both vessel_id and id to ensure correct maintenance
            $maintenance = Maintenance::where('vessel_id', $vesselId)
                ->where('id', $maintenanceId)
                ->firstOrFail();

            // Check permissions
            if (! $user || ! $user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (! ($permissions['maintenances.edit'] ?? false)) {
                abort(403, 'You do not have permission to edit maintenances.');
            }

            // Cannot remove transactions from closed or cancelled maintenances
            if ($maintenance->status === 'closed' || $maintenance->status === 'cancelled') {
                abort(403, 'Cannot remove transactions from a closed or cancelled maintenance.');
            }

            // Get transaction ID from route parameter and unhash it
            $transactionParam = is_object($transaction) ? $transaction->id : $transaction;
            if (! is_numeric($transactionParam)) {
                $transactionId = $this->unhashId($transactionParam, 'movimentation');
            } else {
                $transactionId = (int) $transactionParam;
            }
            if (! $transactionId) {
                abort(404, 'Transaction not found.');
            }
            $transaction = Movimentation::where('maintenance_id', $maintenance->id)->findOrFail($transactionId);

            // Remove maintenance_id from transaction
            $transaction->update(['maintenance_id' => null]);

            return back()
                ->with('success', $this->transFrom('notifications', 'Transaction has been removed from the maintenance.'));
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to remove transaction from maintenance: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Extract numeric part from a maintenance number (supports numbers only or letters+numbers).
     * Examples: "12345" -> 12345, "MANT2025000001" -> 1, "A123" -> 123
     */
    private function extractNumericPart(string $number): int
    {
        // Extract all digits from the string
        preg_match_all('/\d+/', $number, $matches);
        if (!empty($matches[0])) {
            // Get the last numeric sequence (most significant)
            $numericPart = end($matches[0]);
            return (int) $numericPart;
        }
        return 0;
    }
}
