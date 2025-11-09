<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VatProfile;
use App\Models\VesselSetting;
use App\Services\MoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TransactionController extends Controller
{
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

        // Check if user has permission to view transactions
        // All users with vessel access can view transactions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have permission to view transactions.');
        }

        // Main data query - filter by vessel
        $query = Transaction::query()->where('vessel_id', $vesselId);

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

        // Filter by bank account
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        // Sorting
        $sortField = $request->get('sort', 'transaction_date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Eager load relationships for performance
        $transactions = $query->with([
            'category:id,name,type,color',
            'bankAccount:id,name,bank_name',
            'supplier:id,company_name,description',
            'crewMember:id,name,email',
        ])->paginate(15)->withQueryString();

        // Related data for filters/forms
        // Eager load country relationship for getCurrency() method to work
        $bankAccounts = BankAccount::where('vessel_id', $vesselId)
            ->with('country')
            ->active()
            ->orderBy('name')
            ->get();
        $categories = TransactionCategory::orderBy('name')->get();
        $suppliers = Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get VAT profiles and default VAT profile from vessel settings
        $vatProfiles = VatProfile::active()->orderBy('name')->get();
        $vesselSetting = VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
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
            'bank_account_id',
            'date_from',
            'date_to',
            'sort',
            'direction',
        ]);

        // Options for filter dropdowns
        $types = [
            'income' => 'Income',
            'expense' => 'Expense',
            'transfer' => 'Transfer',
        ];

        $statuses = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return Inertia::render('Transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'defaultCurrency' => $defaultCurrency, // Pass default currency from vessel_settings to frontend
            'bankAccounts' => $bankAccounts->map(function ($account) use ($defaultCurrency) {
                // Get currency from bank account (via getCurrency() method) or use default
                // getCurrency() returns currency from country or IBAN, or null if neither exists
                $accountCurrency = $account->getCurrency();
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'bank_name' => $account->bank_name,
                    'currency' => $accountCurrency ?? $defaultCurrency,
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
                    'description' => $supplier->description,
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
            'transactionTypes' => $types,
            'statuses' => $statuses,
            'filters' => $filters,
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

        // Check if user has permission to create transactions
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, 'You do not have permission to create transactions.');
        }

        // Related data for form
        // Eager load country relationship for getCurrency() method to work
        $bankAccounts = BankAccount::where('vessel_id', $vesselId)
            ->with('country')
            ->active()
            ->orderBy('name')
            ->get();
        $categories = TransactionCategory::orderBy('name')->get();
        $suppliers = Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get VAT profiles and default VAT profile from vessel settings
        $vatProfiles = VatProfile::active()->orderBy('name')->get();
        $vesselSetting = VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? VatProfile::find($vesselSetting->vat_profile_id)
            : VatProfile::where('is_default', true)->first();

        // Get default currency: vessel_settings > vessel currency_code > EUR
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Options for form dropdowns
        $types = [
            'income' => 'Income',
            'expense' => 'Expense',
            'transfer' => 'Transfer',
        ];

        $statuses = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return Inertia::render('Transactions/Create', [
            'defaultCurrency' => $defaultCurrency, // Pass default currency from vessel_settings to frontend
            'bankAccounts' => $bankAccounts->map(function ($account) use ($defaultCurrency) {
                // Get currency from bank account (via getCurrency() method) or use default
                // getCurrency() returns currency from country or IBAN, or null if neither exists
                $accountCurrency = $account->getCurrency();
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'bank_name' => $account->bank_name,
                    'currency' => $accountCurrency ?? $defaultCurrency,
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
                    'description' => $supplier->description,
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
            'transactionTypes' => $types,
            'statuses' => $statuses,
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
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

            // Validate bank account exists if provided
            if (!$request->bank_account_id) {
                // If no bank account, we can't create a transaction
                return back()
                    ->withInput()
                    ->with('error', 'Please select a bank account to create a transaction.')
                    ->with('notification_delay', 0);
            }

            // Get currency priority: request currency (from form) > bank account > vessel_settings > vessel currency_code > EUR
            // IMPORTANT: Always prioritize the currency sent from the frontend form, as it reflects user's intent and vessel settings
            $bankAccount = BankAccount::find($request->bank_account_id);
            $vesselSetting = VesselSetting::getForVessel($vesselId);
            $vessel = \App\Models\Vessel::find($vesselId);

            // Priority: form currency (user's explicit choice from vessel_settings) > bank account currency > vessel_settings > vessel currency_code > EUR
            $currency = $request->currency
                ?? $bankAccount?->getCurrency()
                ?? $vesselSetting->currency_code
                ?? $vessel?->currency_code
                ?? 'EUR';

            // Access validated values directly as properties (never use validated())
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Handle VAT calculation
            $amount = $request->amount;
            $vatAmount = 0;
            $vatProfileId = $request->vat_profile_id;
            $amountIncludesVat = $request->amount_includes_vat ?? false;

            // For income transactions, always get VAT profile from vessel settings or default
            // For expense transactions, vat_profile_id should be null (handled in model boot)
            if ($request->type === 'income') {
                if (!$vatProfileId) {
                    $vesselSetting = VesselSetting::getForVessel($vesselId);
                    $vatProfileId = $vesselSetting->vat_profile_id
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
                        $calculation = MoneyService::calculateFromTotalIncludingVat($amount, $vatRate);
                        $amount = $calculation['base']; // Store base amount
                        $vatAmount = $calculation['vat']; // Store VAT amount
                    } else {
                        // Amount excludes VAT - calculate VAT on top
                        // vat = amount * (vat_rate/100)
                        // total = amount + vat
                        $vatAmount = MoneyService::calculateVat($amount, $vatRate);
                        // amount stays as is (base amount)
                    }
                }
            }

            $totalAmount = $amount + $vatAmount;

            $transaction = Transaction::create([
                'vessel_id' => $vesselId,
                'bank_account_id' => $request->bank_account_id,
                'category_id' => $request->category_id,
                'type' => $request->type,
                'amount' => $amount, // Base amount (after VAT separation if amount includes VAT)
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'currency' => $currency,
                'house_of_zeros' => $bankAccount?->house_of_zeros ?? $request->house_of_zeros ?? 2,
                'vat_profile_id' => $vatProfileId,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'notes' => $request->notes,
                // Reference is auto-generated in model boot method
                'supplier_id' => $request->supplier_id,
                'crew_member_id' => $request->crew_member_id,
                'status' => $request->status,
                'created_by' => $user->id,
            ]);

            // Reload with relationships
            $transaction->load([
                'category',
                'bankAccount',
                'supplier',
                'crewMember',
            'vatProfile',
            ]);

            return back()
                ->with('success', "Transaction '{$transaction->transaction_number}' has been created successfully.")
                ->with('notification_delay', 0);
        } catch (\Exception $e) {
            Log::error('Transaction creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create transaction: ' . $e->getMessage())
                ->with('notification_delay', 0);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(Request $request, Transaction $transaction)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Verify transaction belongs to current vessel
        if ($transaction->vessel_id !== $vesselId) {
            abort(403, 'Unauthorized access to transaction.');
        }

        // Check if user has permission to view transactions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have permission to view transactions.');
        }

        // Load all relationships
        $transaction->load([
            'vessel',
            'category',
            'bankAccount',
            'supplier',
            'crewMember',
            'vatRate',
            'createdBy',
            'attachments',
        ]);

        return Inertia::render('Transactions/Show', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Get transaction details for modal display (API endpoint)
     */
    public function details(Request $request, $vessel, Transaction $transaction)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Verify transaction belongs to current vessel
        if ($transaction->vessel_id !== $vesselId) {
            abort(403, 'Unauthorized access to transaction.');
        }

        // Check if user has permission to view transactions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have permission to view transaction details.');
        }

        // Load all relationships
        $transaction->load([
            'category',
            'bankAccount',
            'supplier',
            'crewMember',
            'vatProfile',
            'createdBy',
            'attachments',
        ]);

        return response()->json([
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Request $request, Transaction $transaction)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Verify transaction belongs to current vessel
        if ($transaction->vessel_id !== $vesselId) {
            abort(403, 'Unauthorized access to transaction.');
        }

        // Check if user has permission to edit transactions
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, 'You do not have permission to edit transactions.');
        }

        // Load transaction with relationships
        $transaction->load([
            'category',
            'bankAccount',
            'supplier',
            'crewMember',
            'vatRate',
        ]);

        // Related data for form
        // Eager load country relationship for getCurrency() method to work
        $bankAccounts = BankAccount::where('vessel_id', $vesselId)
            ->with('country')
            ->active()
            ->orderBy('name')
            ->get();
        $categories = TransactionCategory::orderBy('name')->get();
        $suppliers = Supplier::where('vessel_id', $vesselId)->orderBy('company_name')->get();
        $crewMembers = User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get VAT profiles and default VAT profile from vessel settings
        $vatProfiles = VatProfile::active()->orderBy('name')->get();
        $vesselSetting = VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
        $defaultVatProfile = $vesselSetting->vat_profile_id
            ? VatProfile::find($vesselSetting->vat_profile_id)
            : VatProfile::where('is_default', true)->first();

        // Options for form dropdowns
        $types = [
            'income' => 'Income',
            'expense' => 'Expense',
            'transfer' => 'Transfer',
        ];

        $statuses = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        // Get default currency: vessel_settings > vessel currency_code > EUR
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        return Inertia::render('Transactions/Edit', [
            'transaction' => new TransactionResource($transaction),
            'defaultCurrency' => $defaultCurrency, // Pass default currency from vessel_settings to frontend
            'bankAccounts' => $bankAccounts->map(function ($account) use ($defaultCurrency) {
                // Get currency from bank account (via getCurrency() method) or use default
                // getCurrency() returns currency from country or IBAN, or null if neither exists
                $accountCurrency = $account->getCurrency();
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'bank_name' => $account->bank_name,
                    'currency' => $accountCurrency ?? $defaultCurrency,
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
                    'description' => $supplier->description,
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
            'transactionTypes' => $types,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(UpdateTransactionRequest $request, $vessel, Transaction $transaction)
    {
        try {
            // Get vessel_id from route parameter
            $vessel = $request->route('vessel');
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

            // Verify transaction belongs to current vessel
            if ($transaction->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to transaction.');
            }

            // Get currency priority: request currency (from form) > bank account > vessel_settings > vessel currency_code > EUR
            // IMPORTANT: Always prioritize the currency sent from the frontend form, as it reflects user's intent and vessel settings
            $bankAccount = BankAccount::find($request->bank_account_id);
            $vesselSetting = VesselSetting::getForVessel($vesselId);
            $vessel = \App\Models\Vessel::find($vesselId);

            // Priority: form currency (user's explicit choice from vessel_settings) > bank account currency > vessel_settings > vessel currency_code > EUR
            $currency = $request->currency
                ?? $bankAccount?->getCurrency()
                ?? $vesselSetting->currency_code
                ?? $vessel?->currency_code
                ?? 'EUR';

            // Handle VAT calculation (same as store method)
            $amount = $request->amount;
            $vatAmount = 0;
            $vatProfileId = $request->vat_profile_id;
            $amountIncludesVat = $request->amount_includes_vat ?? false;

            // For income transactions, always get VAT profile from vessel settings or default
            // For expense transactions, vat_profile_id should be null
            if ($request->type === 'income') {
                if (!$vatProfileId) {
                    $vesselSetting = VesselSetting::getForVessel($vesselId);
                    $vatProfileId = $vesselSetting->vat_profile_id
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
                        $calculation = MoneyService::calculateFromTotalIncludingVat($amount, $vatRate);
                        $amount = $calculation['base']; // Store base amount
                        $vatAmount = $calculation['vat']; // Store VAT amount
                    } else {
                        // Amount excludes VAT - calculate VAT on top
                        $vatAmount = MoneyService::calculateVat($amount, $vatRate);
                        // amount stays as is (base amount)
                    }
                }
            }

            $totalAmount = $amount + $vatAmount;

            // Access validated values directly as properties (never use validated())
            $transaction->update([
                'bank_account_id' => $request->bank_account_id,
                'category_id' => $request->category_id,
                'type' => $request->type,
                'amount' => $amount, // Base amount (after VAT separation if amount includes VAT)
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'currency' => $currency,
                'house_of_zeros' => $request->house_of_zeros,
                'vat_profile_id' => $vatProfileId,
                'amount_includes_vat' => $amountIncludesVat,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'notes' => $request->notes,
                'reference' => $request->reference,
                'supplier_id' => $request->supplier_id,
                'crew_member_id' => $request->crew_member_id,
                'status' => $request->status,
            ]);

            // Reload with relationships
            $transaction->load([
                'category',
                'bankAccount',
                'supplier',
                'crewMember',
            'vatProfile',
            ]);

            return redirect()
                ->route('panel.transactions.index', ['vessel' => $vesselId])
                ->with('success', "Transaction '{$transaction->transaction_number}' has been updated successfully.")
                ->with('notification_delay', 4);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update transaction. Please try again.')
                ->with('notification_delay', 0);
        }
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Request $request, $vessel, Transaction $transaction)
    {
        try {
            $user = $request->user();

            // Get vessel_id from route parameter
            $vessel = $request->route('vessel');
            $vesselId = is_object($vessel) ? $vessel->id : (int) $vessel;

            // Verify transaction belongs to current vessel
            if ($transaction->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to transaction.');
            }

            // Check vessel-specific permissions for deletion
            // Only Administrator and Supervisor can delete transactions
            if (!$user->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor'])) {
                abort(403, 'You do not have permission to delete transactions for this vessel.');
            }

            // Check if transaction has attachments
            if ($transaction->attachments()->count() > 0) {
                return back()->with('error', "Cannot delete transaction '{$transaction->transaction_number}' because it has attachments. Please remove all attachments first.")
                    ->with('notification_delay', 0);
            }

            $transactionNumber = $transaction->transaction_number;
            $transaction->delete();

            return redirect()
                ->route('panel.transactions.index', ['vessel' => $vesselId])
                ->with('success', "Transaction '{$transactionNumber}' has been deleted successfully.")
                ->with('notification_delay', 5);
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete transaction. Please try again.')
                ->with('notification_delay', 0);
        }
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

        // Check if user has permission to search transactions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have permission to search transactions.');
        }

        $query = $request->get('q');

        $transactions = Transaction::where('vessel_id', $vesselId)
            ->where(function ($q) use ($query) {
                $q->where('transaction_number', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('reference', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'transaction_number', 'description', 'type', 'amount', 'currency']);

        return response()->json($transactions);
    }
}

