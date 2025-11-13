<?php
namespace App\Http\Controllers;

use App\Actions\AuditLogAction;
use App\Actions\EmailNotificationAction;
use App\Actions\MoneyAction;
use App\Http\Controllers\Concerns\HashesIds;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Movimentation;
use App\Models\MovimentationCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\VesselSetting;
use App\Pdf\TransactionPdf;
use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TransactionController extends Controller
{
    use HasTranslations, HashesIds;
    /**
     * Display a listing of transactions for the current vessel.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Main data query - filter by vessel
        $query = Movimentation::query()->where('vessel_id', $vesselId);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $categoryId = $this->unhashId($request->category_id, 'transactioncategory');
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        // Sorting - default to created_at descending (newest first)
        $sortField     = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // If sorting by created_at, also add transaction_date as secondary sort for consistency
        if ($sortField === 'created_at') {
            $query->orderBy('created_at', $sortDirection)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('transaction_number', 'desc');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Eager load relationships for performance
        $transactions = $query->with([
            'category:id,name,type,color',
            'supplier:id,company_name,description',
            'crewMember:id,name,email',
            'files:id,transaction_id,src,name,size,type',
        ])->paginate(20)->withQueryString();

        // Transform the data manually to preserve pagination metadata
        $transactions->through(function ($transaction) {
            return (new TransactionResource($transaction))->resolve();
        });

        // Related data for filters/forms
        // Get categories: system categories (vessel_id = null) + vessel-specific categories
        $categories  = MovimentationCategory::forVessel($vesselId)->orderBy('name')->get();
        $suppliers   = Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get VAT profiles and default VAT profile from vessel settings
        $vatProfiles       = VatProfile::active()->orderBy('name')->get();
        $vesselSetting     = VesselSetting::getForVessel($vesselId);
        $vessel            = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? VatProfile::find($vesselSetting->vat_profile_id)
            : VatProfile::where('is_default', true)->first();

        // Get default currency: vessel_settings > vessel currency_code > EUR
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Current filters
        $filters = $request->only([
            'search',
            'type',
            'status',
            'category_id',
            'date_from',
            'date_to',
            'sort',
            'direction',
        ]);

        // Options for filter dropdowns
        $types = [
            'income'   => 'Income',
            'expense'  => 'Expense',
            'transfer' => 'Transfer',
        ];

        $statuses = [
            'pending'   => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return Inertia::render('Transactions/Index', [
            'transactions'      => $transactions,
            'defaultCurrency'   => $defaultCurrency, // Pass default currency from vessel_settings to frontend
            'categories'        => $categories->map(function ($category) {
                return [
                    'id'    => $this->hashId($category->id, 'transactioncategory'),
                    'name'  => $category->name,
                    'type'  => $category->type,
                    'color' => $category->color,
                ];
            }),
            'suppliers'         => $suppliers->map(function ($supplier) {
                return [
                    'id'           => $this->hashId($supplier->id, 'supplier'),
                    'company_name' => $supplier->company_name,
                    'description'  => $supplier->description,
                ];
            }),
            'crewMembers'       => $crewMembers->map(function ($member) {
                return [
                    'id'    => $this->hashId($member->id, 'user'),
                    'name'  => $member->name,
                    'email' => $member->email,
                ];
            }),
            'vatProfiles'       => $vatProfiles->map(function ($profile) {
                return [
                    'id'         => $this->hashId($profile->id, 'vatprofile'),
                    'name'       => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'country_id' => $this->hashId($profile->country_id, 'country'),
                ];
            }),
            'defaultVatProfile' => $defaultVatProfile ? [
                'id'         => $this->hashId($defaultVatProfile->id, 'vatprofile'),
                'name'       => $defaultVatProfile->name,
                'percentage' => (float) $defaultVatProfile->percentage,
                'country_id' => $this->hashId($defaultVatProfile->country_id, 'country'),
            ] : null,
            'transactionTypes'  => $types,
            'statuses'          => $statuses,
            'filters'           => $filters,
        ]);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to create transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.create permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.create'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Related data for form
        // Get categories: system categories (vessel_id = null) + vessel-specific categories
        $categories  = MovimentationCategory::forVessel($vesselId)->orderBy('name')->get();
        $suppliers   = Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get VAT profiles and default VAT profile from vessel settings
        $vatProfiles       = VatProfile::active()->orderBy('name')->get();
        $vesselSetting     = VesselSetting::getForVessel($vesselId);
        $vessel            = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? VatProfile::find($vesselSetting->vat_profile_id)
            : VatProfile::where('is_default', true)->first();

        // Get default currency: vessel_settings > vessel currency_code > EUR
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Options for form dropdowns
        $types = [
            'income'   => 'Income',
            'expense'  => 'Expense',
            'transfer' => 'Transfer',
        ];

        $statuses = [
            'pending'   => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return Inertia::render('Transactions/Create', [
            'defaultCurrency'   => $defaultCurrency, // Pass default currency from vessel_settings to frontend
            'categories'        => $categories->map(function ($category) {
                return [
                    'id'    => $this->hashId($category->id, 'transactioncategory'),
                    'name'  => $category->name,
                    'type'  => $category->type,
                    'color' => $category->color,
                ];
            }),
            'suppliers'         => $suppliers->map(function ($supplier) {
                return [
                    'id'           => $this->hashId($supplier->id, 'supplier'),
                    'company_name' => $supplier->company_name,
                    'description'  => $supplier->description,
                ];
            }),
            'crewMembers'       => $crewMembers->map(function ($member) {
                return [
                    'id'    => $this->hashId($member->id, 'user'),
                    'name'  => $member->name,
                    'email' => $member->email,
                ];
            }),
            'vatProfiles'       => $vatProfiles->map(function ($profile) {
                return [
                    'id'         => $this->hashId($profile->id, 'vatprofile'),
                    'name'       => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'country_id' => $this->hashId($profile->country_id, 'country'),
                ];
            }),
            'defaultVatProfile' => $defaultVatProfile ? [
                'id'         => $this->hashId($defaultVatProfile->id, 'vatprofile'),
                'name'       => $defaultVatProfile->name,
                'percentage' => (float) $defaultVatProfile->percentage,
                'country_id' => $this->hashId($defaultVatProfile->country_id, 'country'),
            ] : null,
            'transactionTypes'  => $types,
            'statuses'          => $statuses,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
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

            // Get currency priority: request currency (from form) > vessel_settings > vessel currency_code > EUR
            // IMPORTANT: Always prioritize the currency sent from the frontend form, as it reflects user's intent and vessel settings
            $vesselSetting = VesselSetting::getForVessel($vesselId);
            $vessel        = \App\Models\Vessel::find($vesselId);

            // Priority: form currency (user's explicit choice from vessel_settings) > vessel_settings > vessel currency_code > EUR
            $currency = $request->currency ?? $vesselSetting->currency_code ?? $vessel?->currency_code ?? 'EUR';

            // Access validated values directly as properties (never use validated())
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Handle VAT calculation
            $amount            = $request->amount;
            $vatAmount         = 0;
            $vatProfileId      = $request->vat_profile_id ? $this->unhashId($request->vat_profile_id, 'vatprofile') : null;
            $amountIncludesVat = $request->amount_includes_vat ?? false;

            // For income transactions, always get VAT profile from vessel settings or default
            // For expense transactions, vat_profile_id should be null (handled in model boot)
            if ($request->type === 'income') {
                if (! $vatProfileId) {
                    $vesselSetting = VesselSetting::getForVessel($vesselId);
                    $vatProfileId  = $vesselSetting->vat_profile_id
                        ?: (VatProfile::where('is_default', true)->first()?->id);
                }
            } else {
                // Expense transactions don't use VAT
                $vatProfileId = null;
            }

            if ($vatProfileId) {
                $vatProfile = VatProfile::find($vatProfileId);
                if ($vatProfile) {
                    $vatRate = (float) $vatProfile->percentage;

                    if ($amountIncludesVat) {
                        // Amount includes VAT - separate it
                        // base = total / (1 + vat_rate/100)
                        // vat = total - base
                        $calculation = MoneyAction::calculateFromTotalIncludingVat($amount, $vatRate);
                        $amount      = $calculation['base']; // Store base amount
                        $vatAmount   = $calculation['vat'];  // Store VAT amount
                    } else {
                        // Amount excludes VAT - calculate VAT on top
                        // vat = amount * (vat_rate/100)
                        // total = amount + vat
                        $vatAmount = MoneyAction::calculateVat($amount, $vatRate);
                        // amount stays as is (base amount)
                    }
                }
            }

            $totalAmount = $amount + $vatAmount;

            // Helper function to safely unhash or use numeric ID
            $getNumericId = function ($value, $modelName) {
                if (! $value) {
                    return null;
                }
                // If already numeric, use it directly (from prepareForValidation)
                if (is_numeric($value)) {
                    return (int) $value;
                }
                // Otherwise, unhash it
                return $this->unhashId($value, $modelName);
            };

            $transaction = Movimentation::create([
                'vessel_id'        => $vesselId,
                'marea_id'         => $getNumericId($request->marea_id, 'marea'),
                'maintenance_id'   => $getNumericId($request->maintenance_id, 'maintenance'),
                // category_id is already decoded in prepareForValidation(), use it directly
                'category_id'      => $request->category_id ? (int) $request->category_id : null,
                'type'             => $request->type,
                'amount'           => $amount, // Base amount (after VAT separation if amount includes VAT)
                'amount_per_unit'  => $request->amount_per_unit ?? null,
                'quantity'         => $request->quantity ?? null,
                'vat_amount'       => $vatAmount,
                'total_amount'     => $totalAmount,
                'currency'         => $currency,
                'house_of_zeros'   => $request->house_of_zeros ?? 2,
                'vat_profile_id'   => $vatProfileId,
                'transaction_date' => $request->transaction_date,
                'description'      => $request->description,
                'notes'            => $request->notes,
                // Reference is auto-generated in model boot method
                'supplier_id'      => $getNumericId($request->supplier_id, 'supplier'),
                'crew_member_id'   => $getNumericId($request->crew_member_id, 'user'),
                'status'           => $request->status,
                'created_by'       => $user->id,
            ]);

            // Handle file uploads if any
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // Handle both single file and array of files
                if (! is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if (! $file) {
                        continue;
                    }
                    try {
                        // Save file using TenantFileAction
                        $fileInfo = \App\Actions\Tenant\TenantFileAction::save(
                            vesselId: $vesselId,
                            file: $file,
                            isPublic: false,
                            path: 'transactions',
                            fileName: null,
                            extension: null
                        );

                        // Create transaction file record
                        \App\Models\MovimentationFile::create([
                            'transaction_id' => $transaction->id,
                            'src'            => $fileInfo->url,
                            'name'           => $file->getClientOriginalName(),
                            'size'           => $fileInfo->size,
                            'type'           => $fileInfo->extension,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to upload file for transaction', [
                            'transaction_id' => $transaction->id,
                            'file_name'      => $file->getClientOriginalName(),
                            'error'          => $e->getMessage(),
                        ]);
                        // Continue with other files even if one fails
                    }
                }
            }

            // Reload with relationships
            $transaction->load([
                'category',
                'supplier',
                'crewMember',
                'vatProfile',
                'files',
            ]);

            // Log the create action
            AuditLogAction::logCreate(
                $transaction,
                'Transaction',
                $transaction->transaction_number,
                $vesselId
            );

            // Create email notification for other users (not the user who created it)
            try {
                $currencyModel  = \App\Models\Currency::where('code', $currency)->first();
                $currencySymbol = $currencyModel->symbol ?? '€';

                EmailNotificationAction::createNotification(
                    type: 'transaction_created',
                    subjectType: Movimentation::class,
                    subjectId: $transaction->id,
                    vesselId: $vesselId,
                    actionByUserId: $user->id,
                    subjectData: [
                        'transaction_number' => $transaction->transaction_number,
                        'type'               => $transaction->type,
                        'amount'             => $transaction->total_amount,
                        'currency_symbol'    => $currencySymbol,
                        'description'        => $transaction->description,
                        'category_name'      => $transaction->category->name ?? null,
                        'created_at'         => $transaction->created_at->toIso8601String(),
                    ]
                );
            } catch (\Exception $e) {
                // Log error but don't fail the transaction creation
                Log::warning('Failed to create email notification for transaction', [
                    'transaction_id' => $transaction->id,
                    'vessel_id'      => $vesselId,
                    'error'          => $e->getMessage(),
                ]);
            }

            return back()
                ->with('success', $this->transFrom('notifications', "Transaction ':number' has been created successfully.", [
                    'number' => $transaction->transaction_number,
                ]))
                ->with('notification_delay', 0);
        } catch (\Exception $e) {
            Log::error('Transaction creation failed', [
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to create transaction: :message', [
                    'message' => $e->getMessage(),
                ]))
                ->with('notification_delay', 0);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(Request $request, $vessel, $transactionId)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');
        if (! $vesselId) {
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
        }
        $vesselId = (int) $vesselId; // Ensure integer

        // CRITICAL: Get transaction ID directly from route parameter and unhash it
        $transactionIdFromRoute = $request->route('transactionId');
        // Unhash transaction ID if it's a hashed string
        if ($transactionIdFromRoute && ! is_numeric($transactionIdFromRoute)) {
            $transactionId = $this->unhashId($transactionIdFromRoute, 'transaction-id');
        } else {
            $transactionId = (int) ($transactionIdFromRoute ?? $transactionId);
        }
        if (! $transactionId) {
            abort(404, 'Transaction not found.');
        }

        // Force fresh query with both vessel_id and id to ensure correct transaction
        $transaction = Movimentation::where('vessel_id', $vesselId)
            ->where('id', $transactionId)
            ->firstOrFail();

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Load all relationships
        $transaction->load([
            'vessel',
            'category',
            'supplier',
            'crewMember',
            'vatProfile',
            'createdBy',
            'files',
        ]);

        return Inertia::render('Transactions/Show', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Get transaction details for modal display (API endpoint)
     */
    public function details(Request $request, $vessel, $transactionId)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');
        if (! $vesselId) {
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
        }
        $vesselId = (int) $vesselId; // Ensure integer

        // CRITICAL: Get transaction ID directly from route parameter and unhash it
        $transactionIdFromRoute = $request->route('transactionId');
        // Unhash transaction ID if it's a hashed string
        if ($transactionIdFromRoute && ! is_numeric($transactionIdFromRoute)) {
            $transactionId = $this->unhashId($transactionIdFromRoute, 'transaction-id');
        } else {
            $transactionId = (int) ($transactionIdFromRoute ?? $transactionId);
        }
        if (! $transactionId) {
            abort(404, 'Transaction not found.');
        }

        // Force fresh query with both vessel_id and id to ensure correct transaction
        $transaction = Movimentation::where('vessel_id', $vesselId)
            ->where('id', $transactionId)
            ->firstOrFail();

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Load all relationships
        $transaction->load([
            'category',
            'supplier',
            'crewMember',
            'vatProfile',
            'createdBy',
            'files',
        ]);

        return response()->json([
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Request $request, $vessel, $transactionId)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');
        if (! $vesselId) {
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
        }
        $vesselId = (int) $vesselId; // Ensure integer

        // CRITICAL: Get transaction ID directly from route parameter and unhash it
        $transactionIdFromRoute = $request->route('transactionId');
        // Unhash transaction ID if it's a hashed string
        if ($transactionIdFromRoute && ! is_numeric($transactionIdFromRoute)) {
            $transactionId = $this->unhashId($transactionIdFromRoute, 'transaction-id');
        } else {
            $transactionId = (int) ($transactionIdFromRoute ?? $transactionId);
        }
        if (! $transactionId) {
            abort(404, 'Transaction not found.');
        }

        // Force fresh query with both vessel_id and id to ensure correct transaction
        $transaction = Movimentation::where('vessel_id', $vesselId)
            ->where('id', $transactionId)
            ->firstOrFail();

        // Check if user has permission to edit transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.edit permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.edit'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Load transaction with relationships
        $transaction->load([
            'category',
            'supplier',
            'crewMember',
            'vatProfile',
            'files',
        ]);

        // Related data for form
        // Get categories: system categories (vessel_id = null) + vessel-specific categories
        $categories  = MovimentationCategory::forVessel($vesselId)->orderBy('name')->get();
        $suppliers   = Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get VAT profiles and default VAT profile from vessel settings
        $vatProfiles       = VatProfile::active()->orderBy('name')->get();
        $vesselSetting     = VesselSetting::getForVessel($vesselId);
        $vessel            = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? VatProfile::find($vesselSetting->vat_profile_id)
            : VatProfile::where('is_default', true)->first();

        // Options for form dropdowns
        $types = [
            'income'   => 'Income',
            'expense'  => 'Expense',
            'transfer' => 'Transfer',
        ];

        $statuses = [
            'pending'   => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        // Get default currency: vessel_settings > vessel currency_code > EUR
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        return Inertia::render('Transactions/Edit', [
            'transaction'       => new TransactionResource($transaction),
            'defaultCurrency'   => $defaultCurrency, // Pass default currency from vessel_settings to frontend
            'categories'        => $categories->map(function ($category) {
                return [
                    'id'    => $this->hashId($category->id, 'transactioncategory'),
                    'name'  => $category->name,
                    'type'  => $category->type,
                    'color' => $category->color,
                ];
            }),
            'suppliers'         => $suppliers->map(function ($supplier) {
                return [
                    'id'           => $this->hashId($supplier->id, 'supplier'),
                    'company_name' => $supplier->company_name,
                    'description'  => $supplier->description,
                ];
            }),
            'crewMembers'       => $crewMembers->map(function ($member) {
                return [
                    'id'    => $this->hashId($member->id, 'user'),
                    'name'  => $member->name,
                    'email' => $member->email,
                ];
            }),
            'vatProfiles'       => $vatProfiles->map(function ($profile) {
                return [
                    'id'         => $this->hashId($profile->id, 'vatprofile'),
                    'name'       => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'country_id' => $this->hashId($profile->country_id, 'country'),
                ];
            }),
            'defaultVatProfile' => $defaultVatProfile ? [
                'id'         => $this->hashId($defaultVatProfile->id, 'vatprofile'),
                'name'       => $defaultVatProfile->name,
                'percentage' => (float) $defaultVatProfile->percentage,
                'country_id' => $this->hashId($defaultVatProfile->country_id, 'country'),
            ] : null,
            'transactionTypes'  => $types,
            'statuses'          => $statuses,
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(UpdateTransactionRequest $request, $vessel, $transactionId)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
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
            $vesselId = (int) $vesselId; // Ensure integer

            // CRITICAL: Get transaction ID directly from route parameter and unhash it
            $transactionIdFromRoute = $request->route('transactionId');
            $hashedId               = $transactionIdFromRoute ?? $transactionId;
            $transactionId          = $this->unhashId($hashedId, 'transaction');
            if (! $transactionId) {
                abort(404, 'Transaction not found.');
            }

            // Force fresh query with both vessel_id and id to ensure correct transaction
            $transaction = Movimentation::where('vessel_id', $vesselId)
                ->where('id', $transactionId)
                ->firstOrFail();

            // Get currency priority: request currency (from form) > vessel_settings > vessel currency_code > EUR
            // IMPORTANT: Always prioritize the currency sent from the frontend form, as it reflects user's intent and vessel settings
            $vesselSetting = VesselSetting::getForVessel($vesselId);
            $vessel        = \App\Models\Vessel::find($vesselId);

            // Priority: form currency (user's explicit choice from vessel_settings) > vessel_settings > vessel currency_code > EUR
            $currency = $request->currency ?? $vesselSetting->currency_code ?? $vessel?->currency_code ?? 'EUR';

            // Handle VAT calculation (same as store method)
            $amount            = $request->amount;
            $vatAmount         = 0;
            $vatProfileId      = $request->vat_profile_id ? $this->unhashId($request->vat_profile_id, 'vatprofile') : null;
            $amountIncludesVat = $request->amount_includes_vat ?? false;

            // For income transactions, always get VAT profile from vessel settings or default
            // For expense transactions, vat_profile_id should be null
            if ($request->type === 'income') {
                if (! $vatProfileId) {
                    $vesselSetting = VesselSetting::getForVessel($vesselId);
                    $vatProfileId  = $vesselSetting->vat_profile_id
                        ?: (VatProfile::where('is_default', true)->first()?->id);
                }
            } else {
                // Expense transactions don't use VAT
                $vatProfileId = null;
            }

            if ($vatProfileId) {
                $vatProfile = VatProfile::find($vatProfileId);
                if ($vatProfile) {
                    $vatRate = (float) $vatProfile->percentage;

                    if ($amountIncludesVat) {
                        // Amount includes VAT - separate it
                        $calculation = MoneyAction::calculateFromTotalIncludingVat($amount, $vatRate);
                        $amount      = $calculation['base']; // Store base amount
                        $vatAmount   = $calculation['vat'];  // Store VAT amount
                    } else {
                        // Amount excludes VAT - calculate VAT on top
                        $vatAmount = MoneyAction::calculateVat($amount, $vatRate);
                        // amount stays as is (base amount)
                    }
                }
            }

            $totalAmount = $amount + $vatAmount;

            // Store original state for change detection
            $originalTransaction = $transaction->replicate();

            // Access validated values directly as properties (never use validated())
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Helper function to safely unhash or use numeric ID
            $getNumericId = function ($value, $modelName) {
                if (! $value) {
                    return null;
                }
                // If already numeric, use it directly (from prepareForValidation)
                if (is_numeric($value)) {
                    return (int) $value;
                }
                // Otherwise, unhash it
                return $this->unhashId($value, $modelName);
            };

            $transaction->update([
                // category_id is already decoded in prepareForValidation(), use it directly
                'category_id'         => $request->category_id,
                'type'                => $request->type,
                'amount'              => $amount, // Base amount (after VAT separation if amount includes VAT)
                'amount_per_unit'     => $request->amount_per_unit ?? null,
                'quantity'            => $request->quantity ?? null,
                'vat_amount'          => $vatAmount,
                'total_amount'        => $totalAmount,
                'currency'            => $currency,
                'house_of_zeros'      => $request->house_of_zeros,
                'vat_profile_id'      => $vatProfileId,
                'amount_includes_vat' => $amountIncludesVat,
                'transaction_date'    => $request->transaction_date,
                'description'         => $request->description,
                'notes'               => $request->notes,
                'reference'           => $request->reference,
                'supplier_id'         => $getNumericId($request->supplier_id, 'supplier'),
                'crew_member_id'      => $getNumericId($request->crew_member_id, 'user'),
                'status'              => $request->status,
            ]);

            // Handle file uploads if any
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // Handle both single file and array of files
                if (! is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    if (! $file) {
                        continue;
                    }
                    try {
                        // Save file using TenantFileAction
                        $fileInfo = \App\Actions\Tenant\TenantFileAction::save(
                            vesselId: $vesselId,
                            file: $file,
                            isPublic: false,
                            path: 'transactions',
                            fileName: null,
                            extension: null
                        );

                        // Create transaction file record
                        \App\Models\MovimentationFile::create([
                            'transaction_id' => $transaction->id,
                            'src'            => $fileInfo->url,
                            'name'           => $file->getClientOriginalName(),
                            'size'           => $fileInfo->size,
                            'type'           => $fileInfo->extension,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to upload file for transaction', [
                            'transaction_id' => $transaction->id,
                            'file_name'      => $file->getClientOriginalName(),
                            'error'          => $e->getMessage(),
                        ]);
                        // Continue with other files even if one fails
                    }
                }
            }

            // Reload with relationships
            $transaction->load([
                'category',
                'supplier',
                'crewMember',
                'vatProfile',
                'files',
            ]);

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($transaction, $originalTransaction);
            AuditLogAction::logUpdate(
                $transaction,
                $changedFields,
                'Transaction',
                $transaction->transaction_number,
                $vesselId
            );

            return back()
                ->with('success', $this->transFrom('notifications', "Transaction ':number' has been updated successfully.", [
                    'number' => $transaction->transaction_number,
                ]))
                ->with('notification_delay', 4);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to update transaction: :message', [
                    'message' => $e->getMessage(),
                ]))
                ->with('notification_delay', 0);
        }
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Request $request, $vessel, $transactionId)
    {
        try {
            $user = $request->user();

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if (! $vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }
            $vesselId = (int) $vesselId; // Ensure integer

            // CRITICAL: Get transaction ID directly from route parameter
            $transactionIdFromRoute = $request->route('transactionId');
            // Unhash transaction ID if it's a hashed string
            if ($transactionIdFromRoute && ! is_numeric($transactionIdFromRoute)) {
                $transactionId = $this->unhashId($transactionIdFromRoute, 'transaction-id');
            } else {
                $transactionId = (int) ($transactionIdFromRoute ?? $transactionId);
            }

            // Force fresh query with both vessel_id and id to ensure correct transaction
            $transaction = Movimentation::where('vessel_id', $vesselId)
                ->where('id', $transactionId)
                ->firstOrFail();

            // Check vessel-specific permissions for deletion using config permissions
            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (! ($permissions['transactions.delete'] ?? false)) {
                abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
            }

            // Delete associated files and their physical files
            $files = $transaction->files;
            foreach ($files as $file) {
                // Delete physical file from storage using TenantFileAction
                try {
                    \App\Actions\Tenant\TenantFileAction::delete(
                        vesselId: $vesselId,
                        fileUrl: $file->src,
                        isPublic: false
                    );
                } catch (\Exception $e) {
                    // Log error but continue with deletion
                    \Illuminate\Support\Facades\Log::warning('Failed to delete physical file', [
                        'file_id' => $file->id,
                        'path'    => $file->src,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }

            $transactionNumber = $transaction->transaction_number;

            // Create email notification for other users BEFORE deletion (so we can capture the data)
            try {
                $currencyModel  = \App\Models\Currency::where('code', $transaction->currency)->first();
                $currencySymbol = $currencyModel->symbol ?? '€';

                EmailNotificationAction::createNotification(
                    type: 'transaction_deleted',
                    subjectType: Movimentation::class,
                    subjectId: $transaction->id,
                    vesselId: $vesselId,
                    actionByUserId: $user->id,
                    subjectData: [
                        'transaction_number' => $transaction->transaction_number,
                        'type'               => $transaction->type,
                        'amount'             => $transaction->total_amount,
                        'currency_symbol'    => $currencySymbol,
                        'description'        => $transaction->description,
                        'category_name'      => $transaction->category->name ?? null,
                        'deleted_at'         => now()->toIso8601String(),
                    ]
                );
            } catch (\Exception $e) {
                // Log error but don't fail the transaction deletion
                Log::warning('Failed to create email notification for deleted transaction', [
                    'transaction_id' => $transaction->id,
                    'vessel_id'      => $vesselId,
                    'error'          => $e->getMessage(),
                ]);
            }

            // Log the delete action BEFORE deletion
            AuditLogAction::logDelete(
                $transaction,
                'Transaction',
                $transactionNumber,
                $vesselId
            );

            $transaction->delete(); // Cascade delete will remove TransactionFile records

            return redirect()
                ->route('panel.transactions.index', ['vessel' => $this->hashId($vesselId, 'vessel')])
                ->with('success', $this->transFrom('notifications', "Transaction ':number' has been deleted successfully.", [
                    'number' => $transactionNumber,
                ]))
                ->with('notification_delay', 5);
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to delete transaction: :message', [
                    'message' => $e->getMessage(),
                ]))
                ->with('notification_delay', 0);
        }
    }

    /**
     * Delete a transaction file.
     */
    public function deleteFile(Request $request, $vessel, $transactionId, $fileId)
    {
        try {
            $user = $request->user();

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if (! $vesselId) {
                $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;
            }
            $vesselId = (int) $vesselId; // Ensure integer

            // CRITICAL: Get transaction ID directly from route parameter and unhash it
            $transactionIdFromRoute = $request->route('transactionId');
            $hashedId               = $transactionIdFromRoute ?? $transactionId;
            $transactionId          = $this->unhashId($hashedId, 'transaction');
            if (! $transactionId) {
                abort(404, 'Transaction not found.');
            }

            // Force fresh query with both vessel_id and id to ensure correct transaction
            $transaction = Movimentation::where('vessel_id', $vesselId)
                ->where('id', $transactionId)
                ->firstOrFail();

            // Get file ID from route parameter and unhash it
            $fileIdFromRoute = $request->route('fileId');
            $hashedFileId    = $fileIdFromRoute ?? $fileId;
            $fileId          = $this->unhashId($hashedFileId, 'transactionfile');
            if (! $fileId) {
                abort(404, 'File not found.');
            }

            // Verify file belongs to transaction
            $transactionFile = \App\Models\MovimentationFile::where('transaction_id', $transaction->id)
                ->where('id', $fileId)
                ->firstOrFail();

            // Check vessel-specific permissions for deletion using config permissions
            // File deletion requires transactions.edit permission (users who can edit can delete files)
            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));
            if (! ($permissions['transactions.edit'] ?? false)) {
                abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
            }

            // Delete physical file from storage
            try {
                \App\Actions\Tenant\TenantFileAction::delete(
                    vesselId: $vesselId,
                    fileUrl: $transactionFile->src,
                    isPublic: false
                );
            } catch (\Exception $e) {
                Log::warning('Failed to delete physical file', [
                    'file_id' => $transactionFile->id,
                    'path'    => $transactionFile->src,
                    'error'   => $e->getMessage(),
                ]);
            }

            // Delete database record
            $fileName = $transactionFile->name;
            $transactionFile->delete();

            // Return redirect for Inertia (not JSON)
            return back()->with('success', "File '{$fileName}' has been deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to delete transaction file', [
                'file_id'        => $transactionFile->id,
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete file. Please try again.');
        }
    }

    /**
     * Display transaction history page with month/year cards.
     */
    public function history(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Get all month/year combinations from transactions (only those with transactions)
        $monthYearCombinations = Movimentation::where('vessel_id', $vesselId)
            ->selectRaw('DISTINCT transaction_month as month, transaction_year as year, COUNT(*) as count')
            ->whereNotNull('transaction_month')
            ->whereNotNull('transaction_year')
            ->groupBy('transaction_month', 'transaction_year')
            ->orderBy('transaction_year', 'desc')
            ->orderBy('transaction_month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'month'       => (int) $item->month,
                    'year'        => (int) $item->year,
                    'month_label' => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'count'       => (int) $item->count,
                ];
            })
            ->values();

        return Inertia::render('Transactions/History', [
            'monthYearCombinations' => $monthYearCombinations,
        ]);
    }

    /**
     * Display transactions for a specific month and year.
     */
    public function historyMonth(Request $request, $year, $month)
    {
        // Get parameters from route (ensures correct binding)
        $year  = (int) $request->route('year');
        $month = (int) $request->route('month');
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Validate month and year
        if ($month < 1 || $month > 12) {
            abort(404, $this->transFrom('notifications', 'Invalid month.'));
        }

        if ($year < 2000 || $year > 2100) {
            abort(404, $this->transFrom('notifications', 'Invalid year.'));
        }

        // Main data query - filter by vessel, month and year
        $query = Movimentation::query()->where('vessel_id', $vesselId)
            ->where('transaction_month', $month)
            ->where('transaction_year', $year);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Sorting - default to transaction_date descending (newest first)
        $sortField     = $request->get('sort', 'transaction_date');
        $sortDirection = $request->get('direction', 'desc');

        $query->orderBy($sortField, $sortDirection)
            ->orderBy('created_at', 'desc');

        // Eager load relationships for performance
        $transactions = $query->with([
            'category:id,name,type,color',
            'supplier:id,company_name,description',
            'crewMember:id,name,email',
            'files:id,transaction_id,src,name,size,type',
        ])->paginate(20)->withQueryString();

        // Transform the data manually to preserve pagination metadata
        $transactions->through(function ($transaction) {
            return (new TransactionResource($transaction))->resolve();
        });

        // Related data for filters/forms
        // Get categories: system categories (vessel_id = null) + vessel-specific categories
        $categories  = MovimentationCategory::forVessel($vesselId)->orderBy('name')->get();
        $suppliers   = Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get VAT profiles and default VAT profile from vessel settings
        $vatProfiles       = VatProfile::active()->orderBy('name')->get();
        $vesselSetting     = VesselSetting::getForVessel($vesselId);
        $vessel            = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? VatProfile::find($vesselSetting->vat_profile_id)
            : VatProfile::where('is_default', true)->first();

        // Get default currency: vessel_settings > vessel currency_code > EUR
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Current filters
        $filters = $request->only([
            'search',
            'type',
            'status',
            'category_id',
            'sort',
            'direction',
        ]);

        // Options for filter dropdowns
        $types = [
            'income'   => 'Income',
            'expense'  => 'Expense',
            'transfer' => 'Transfer',
        ];

        $statuses = [
            'pending'   => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        // Get month label
        $monthLabel = date('F', mktime(0, 0, 0, $month, 1));

        return Inertia::render('Transactions/HistoryMonth', [
            'transactions'      => $transactions,
            'defaultCurrency'   => $defaultCurrency,
            'categories'        => $categories->map(function ($category) {
                return [
                    'id'    => $this->hashId($category->id, 'transactioncategory'),
                    'name'  => $category->name,
                    'type'  => $category->type,
                    'color' => $category->color,
                ];
            }),
            'suppliers'         => $suppliers->map(function ($supplier) {
                return [
                    'id'           => $this->hashId($supplier->id, 'supplier'),
                    'company_name' => $supplier->company_name,
                    'description'  => $supplier->description,
                ];
            }),
            'crewMembers'       => $crewMembers->map(function ($member) {
                return [
                    'id'    => $this->hashId($member->id, 'user'),
                    'name'  => $member->name,
                    'email' => $member->email,
                ];
            }),
            'vatProfiles'       => $vatProfiles->map(function ($profile) {
                return [
                    'id'         => $this->hashId($profile->id, 'vatprofile'),
                    'name'       => $profile->name,
                    'percentage' => (float) $profile->percentage,
                    'country_id' => $this->hashId($profile->country_id, 'country'),
                ];
            }),
            'defaultVatProfile' => $defaultVatProfile ? [
                'id'         => $this->hashId($defaultVatProfile->id, 'vatprofile'),
                'name'       => $defaultVatProfile->name,
                'percentage' => (float) $defaultVatProfile->percentage,
                'country_id' => $this->hashId($defaultVatProfile->country_id, 'country'),
            ] : null,
            'transactionTypes'  => $types,
            'statuses'          => $statuses,
            'filters'           => $filters,
            'month'             => $month,
            'year'              => $year,
            'monthLabel'        => $monthLabel,
        ]);
    }

    /**
     * Search transactions for autocomplete.
     */
    public function search(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to search transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config (search requires view permission)
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        $query = $request->get('q');

        $transactions = Movimentation::where('vessel_id', $vesselId)
            ->where(function ($q) use ($query) {
                $q->where('transaction_number', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('reference', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'transaction_number', 'description', 'type', 'amount', 'currency'])
            ->map(function ($transaction) {
                return [
                    'id'                 => $this->hashId($transaction->id, 'transaction-id'),
                    'transaction_number' => $transaction->transaction_number,
                    'description'        => $transaction->description,
                    'type'               => $transaction->type,
                    'amount'             => $transaction->amount,
                    'currency'           => $transaction->currency,
                ];
            });

        return response()->json($transactions);
    }

    /**
     * Download PDF for all transactions (history page).
     */
    public function downloadPdf(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        $vessel = \App\Models\Vessel::find($vesselId);
        if (! $vessel) {
            abort(404, $this->transFrom('notifications', 'Vessel not found.'));
        }

        // Get all transactions for the vessel
        $query = Movimentation::where('vessel_id', $vesselId);

        // Filter by transaction type if provided
        $transactionType = $request->get('transaction_type');
        if ($transactionType && in_array($transactionType, ['income', 'expense'])) {
            $query->where('type', $transactionType);
        }

        $transactions = $query->with(['category'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Calculate summary
        $totalIncome   = $transactions->where('type', 'income')->sum('total_amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('total_amount');
        $netBalance    = $totalIncome - $totalExpenses;

        $summary = [
            'total_income'   => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance'    => $netBalance,
            'total_count'    => $transactions->count(),
        ];

        // Calculate start and end dates
        $startDate = null;
        $endDate   = null;
        if ($transactions->count() > 0) {
            $startDate = $transactions->min('transaction_date')->format('Y-m-d');
            $endDate   = $transactions->max('transaction_date')->format('Y-m-d');
        }

        $period   = 'All Transactions';
        $filename = "transaction_report_{$vessel->id}_all_" . date('Y-m-d') . '.pdf';

        // Check if colors should be enabled (default to false - colors disabled)
        // If 'enable_colors=1' is present, enable colors; otherwise disable (default)
        $enableColors = $request->get('enable_colors') === '1';

        $pdf = TransactionPdf::generate(
            $vessel,
            $transactions,
            $summary,
            $period,
            $startDate,
            $endDate,
            'Transaction Report',
            'Movements and Transactions Overview',
            $enableColors
        );

        return $pdf->download($filename);
    }

    /**
     * Download PDF for a specific month/year (history month page).
     */
    public function downloadPdfMonth(Request $request, $year, $month)
    {
        // Get parameters from route
        $year  = (int) $request->route('year');
        $month = (int) $request->route('month');

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        // Validate month and year
        if ($month < 1 || $month > 12) {
            abort(404, $this->transFrom('notifications', 'Invalid month.'));
        }

        if ($year < 2000 || $year > 2100) {
            abort(404, $this->transFrom('notifications', 'Invalid year.'));
        }

        $vessel = \App\Models\Vessel::find($vesselId);
        if (! $vessel) {
            abort(404, $this->transFrom('notifications', 'Vessel not found.'));
        }

        // Get transactions for the month/year
        $query = Movimentation::where('vessel_id', $vesselId)
            ->where('transaction_month', $month)
            ->where('transaction_year', $year);

        // Filter by transaction type if provided
        $transactionType = $request->get('transaction_type');
        if ($transactionType && in_array($transactionType, ['income', 'expense'])) {
            $query->where('type', $transactionType);
        }

        $transactions = $query->with(['category'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Calculate summary
        $totalIncome   = $transactions->where('type', 'income')->sum('total_amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('total_amount');
        $netBalance    = $totalIncome - $totalExpenses;

        $summary = [
            'total_income'   => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance'    => $netBalance,
            'total_count'    => $transactions->count(),
        ];

        // Calculate start and end dates for the month
        $startDate = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
        $endDate   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year)); // Last day of month

        $monthLabel = date('F', mktime(0, 0, 0, $month, 1));
        $period     = "{$monthLabel} {$year}";
        $filename   = "transaction_report_{$vessel->id}_{$year}_{$month}_" . date('Y-m-d') . '.pdf';

        // Check if colors should be enabled (default to false - colors disabled)
        // If 'enable_colors=1' is present, enable colors; otherwise disable (default)
        $enableColors = $request->get('enable_colors') === '1';

        $pdf = TransactionPdf::generate(
            $vessel,
            $transactions,
            $summary,
            $period,
            $startDate,
            $endDate,
            'Transaction Report',
            'Movements and Transactions Overview',
            $enableColors
        );

        return $pdf->download($filename);
    }

    /**
     * Download PDF for transactions filtered by date range.
     */
    public function downloadPdfFiltered(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        // Check transactions.view permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['transactions.view'] ?? false)) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        $vessel = \App\Models\Vessel::find($vesselId);
        if (! $vessel) {
            abort(404, $this->transFrom('notifications', 'Vessel not found.'));
        }

        // Get date range from request
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');

        if (! $startDate || ! $endDate) {
            abort(400, $this->transFrom('notifications', 'Start date and end date are required.'));
        }

        // Validate dates
        try {
            $start = \Carbon\Carbon::parse($startDate);
            $end   = \Carbon\Carbon::parse($endDate);

            if ($start->gt($end)) {
                abort(400, $this->transFrom('notifications', 'Start date must be before or equal to end date.'));
            }
        } catch (\Exception $e) {
            abort(400, $this->transFrom('notifications', 'Invalid date format.'));
        }

        // Get transactions for the date range
        $query = Movimentation::where('vessel_id', $vesselId)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Filter by transaction type if provided
        $transactionType = $request->get('transaction_type');
        if ($transactionType && in_array($transactionType, ['income', 'expense'])) {
            $query->where('type', $transactionType);
        }

        $transactions = $query->with(['category'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Calculate summary
        $totalIncome   = $transactions->where('type', 'income')->sum('total_amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('total_amount');
        $netBalance    = $totalIncome - $totalExpenses;

        $summary = [
            'total_income'   => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance'    => $netBalance,
            'total_count'    => $transactions->count(),
        ];

        $period   = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
        $filename = "transaction_report_{$vessel->id}_" . str_replace('/', '-', $period) . '_' . date('Y-m-d') . '.pdf';

        // Check if colors should be enabled (default to false - colors disabled)
        // If 'enable_colors=1' is present, enable colors; otherwise disable (default)
        $enableColors = $request->get('enable_colors') === '1';

        $pdf = TransactionPdf::generate(
            $vessel,
            $transactions,
            $summary,
            $period,
            $startDate,
            $endDate,
            'Transaction Report',
            'Movements and Transactions Overview',
            $enableColors
        );

        return $pdf->download($filename);
    }
}
