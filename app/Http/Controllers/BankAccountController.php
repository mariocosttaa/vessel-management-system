<?php

namespace App\Http\Controllers;

use App\Actions\General\DetectCountryFromIbanAction;
use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Http\Resources\BankAccountResource;
use App\Http\Resources\CountryResource;
use App\Models\BankAccount;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Main data query - only essential data for table display
        $query = BankAccount::query()->where('vessel_id', $vesselId);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('iban', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $bankAccounts = $query->paginate(15)->withQueryString();

        // Related data for filters/forms
        $countries = Country::orderBy('name')->get();
        $currencies = Currency::active()->orderBy('name')->get();

        // Current filters
        $filters = $request->only(['search', 'status', 'country_id', 'sort', 'direction']);

        // Status options for filter dropdown
        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        return Inertia::render('BankAccounts/Index', [
            'bankAccounts' => BankAccountResource::collection($bankAccounts),
            'countries' => CountryResource::collection($countries)->resolve(),
            'currencies' => $currencies->map(function ($currency) {
                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'formatted_display' => $currency->formatted_display,
                ];
            }),
            'statuses' => $statuses,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        // Related data for form
        $countries = Country::orderBy('name')->get();
        $currencies = Currency::active()->orderBy('name')->get();

        // Status options for form
        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        return Inertia::render('BankAccounts/Create', [
            'countries' => CountryResource::collection($countries)->resolve(),
            'currencies' => $currencies->map(function ($currency) {
                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'formatted_display' => $currency->formatted_display,
                ];
            }),
            'statuses' => $statuses,
        ]);
    }

    public function store(StoreBankAccountRequest $request)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            // This ensures vessel_id comes from middleware-validated route, not from form/frontend
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');

            // Auto-detect country from IBAN if provided
            $countryId = null;
            if (!empty($request->iban)) {
                $countryId = DetectCountryFromIbanAction::execute($request->iban);
            }

            $bankAccount = BankAccount::create([
                'name' => $request->name,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'iban' => $request->iban,
                'country_id' => $countryId ?? $request->country_id,
                'vessel_id' => $vesselId,
                'initial_balance' => $request->initial_balance ?? 0,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            return redirect()
                ->route('panel.bank-accounts.index', ['vessel' => $vesselId])
                ->with('success', "Bank account '{$bankAccount->name}' has been created successfully.")
                ->with('notification_delay', 3); // 3 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create bank account. Please try again.')
                ->with('notification_delay', 0); // Persistent error (0 = no auto-dismiss)
        }
    }

    public function show(BankAccount $bankAccount)
    {
        return Inertia::render('BankAccounts/Show', [
            'bankAccount' => new BankAccountResource($bankAccount),
        ]);
    }

    /**
     * Get bank account details for modal display (API endpoint)
     */
    public function details($vessel, BankAccount $bankAccount)
    {
        $bankAccount->load('country');

        return response()->json([
            'bankAccount' => new BankAccountResource($bankAccount),
        ]);
    }

    public function edit(BankAccount $bankAccount)
    {
        // Load bank account with country relationship
        $bankAccount->load('country');

        // Related data for form
        $countries = Country::orderBy('name')->get();
        $currencies = Currency::active()->orderBy('name')->get();

        // Status options for form
        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        return Inertia::render('BankAccounts/Edit', [
            'bankAccount' => new BankAccountResource($bankAccount),
            'countries' => CountryResource::collection($countries)->resolve(),
            'currencies' => $currencies->map(function ($currency) {
                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'formatted_display' => $currency->formatted_display,
                ];
            }),
            'statuses' => $statuses,
        ]);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount)
    {
        try {
            // Verify bank account belongs to current vessel
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if ($bankAccount->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to bank account.');
            }

            // Auto-detect country from IBAN if provided
            $countryId = null;
            if (!empty($request->iban)) {
                $countryId = DetectCountryFromIbanAction::execute($request->iban);
            }

            $bankAccount->update([
                'name' => $request->name,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'iban' => $request->iban,
                'country_id' => $countryId ?? $request->country_id,
                'initial_balance' => $request->initial_balance ?? $bankAccount->initial_balance ?? 0,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            return redirect()
                ->route('panel.bank-accounts.index', ['vessel' => $vesselId])
                ->with('success', "Bank account '{$bankAccount->name}' has been updated successfully.")
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update bank account. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function destroy(Request $request, BankAccount $bankAccount)
    {
        try {
            // Verify bank account belongs to current vessel
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if ($bankAccount->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to bank account.');
            }

            // Check if bank account has transactions
            if ($bankAccount->transactions()->count() > 0) {
                return back()->with('error', "Cannot delete bank account '{$bankAccount->name}' because it has transactions. Please remove all transactions first.")
                    ->with('notification_delay', 0); // Persistent error
            }

            $bankAccountName = $bankAccount->name;
            $bankAccount->delete();

            return redirect()
                ->route('panel.bank-accounts.index', ['vessel' => $vesselId])
                ->with('success', "Bank account '{$bankAccountName}' has been deleted successfully.")
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete bank account. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $bankAccounts = BankAccount::where('name', 'like', "%{$query}%")
                                ->orWhere('bank_name', 'like', "%{$query}%")
                                ->orWhere('account_number', 'like', "%{$query}%")
                                ->limit(10)
                                ->get(['id', 'name', 'bank_name', 'account_number']);

        return response()->json($bankAccounts);
    }
}
