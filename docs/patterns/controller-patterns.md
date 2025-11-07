# Controller Patterns

## Structure and Naming Conventions

### Controller Naming
- Use descriptive names: `TransactionController`, `VesselController`, `CrewMemberController`
- Follow Laravel conventions: `{Entity}Controller`
- Place in `app/Http/Controllers/`

### Action Methods
Standard CRUD actions plus domain-specific actions:

```php
class TransactionController extends Controller
{
    public function index()     // List with filters
    public function create()     // Show create form
    public function store()      // Store new record
    public function show()       // Show single record
    public function edit()       // Show edit form
    public function update()     // Update record
    public function destroy()    // Delete record
    
    // Domain-specific actions
    public function duplicate()   // Duplicate transaction
    public function attach()     // Attach files
    public function search()     // API search endpoint
}
```

## Response Format with Inertia

### Always Return Data in Variables
Never inline data in Inertia responses. Always organize data in variables:

```php
// ✅ Good
public function index(Request $request)
{
    $transactions = Transaction::with(['vessel', 'category', 'bankAccount'])
        ->when($request->vessel_id, fn($q) => $q->where('vessel_id', $request->vessel_id))
        ->when($request->type, fn($q) => $q->where('type', $request->type))
        ->orderBy('transaction_date', 'desc')
        ->paginate(15);

    $vessels = Vessel::active()->get();
    $categories = TransactionCategory::orderBy('name')->get();
    $bankAccounts = BankAccount::active()->get();
    
    $filters = [
        'vessel_id' => $request->vessel_id,
        'type' => $request->type,
        'date_from' => $request->date_from,
        'date_to' => $request->date_to,
    ];

    return inertia('Transactions/Index', [
        'transactions' => TransactionResource::collection($transactions),
        'vessels' => VesselResource::collection($vessels),
        'categories' => TransactionCategoryResource::collection($categories),
        'bankAccounts' => BankAccountResource::collection($bankAccounts),
        'filters' => $filters,
    ]);
}

// ❌ Bad
public function index(Request $request)
{
    return inertia('Transactions/Index', [
        'transactions' => TransactionResource::collection(
            Transaction::with(['vessel', 'category'])->paginate(15)
        ),
        'vessels' => VesselResource::collection(Vessel::all()),
    ]);
}
```

### Data Organization Patterns

#### Index Actions
```php
public function index(Request $request)
{
    // Main data query
    $entities = Model::with(['relations'])
        ->when($request->filter, fn($q) => $q->where('field', $request->filter))
        ->orderBy('created_at', 'desc')
        ->paginate(15);

    // Related data for filters/forms
    $relatedData = RelatedModel::active()->get();
    
    // Current filters
    $filters = $request->only(['field1', 'field2', 'field3']);

    return inertia('Entity/Index', [
        'entities' => EntityResource::collection($entities),
        'relatedData' => RelatedResource::collection($relatedData),
        'filters' => $filters,
    ]);
}
```

#### Create Actions
```php
public function create()
{
    $vessels = Vessel::active()->get();
    $categories = TransactionCategory::orderBy('name')->get();
    $bankAccounts = BankAccount::active()->get();
    $vatRates = VatRate::active()->get();

    return inertia('Transactions/Create', [
        'vessels' => VesselResource::collection($vessels),
        'categories' => TransactionCategoryResource::collection($categories),
        'bankAccounts' => BankAccountResource::collection($bankAccounts),
        'vatRates' => VatRateResource::collection($vatRates),
    ]);
}
```

#### Store Actions

**IMPORTANT: Never use `$request->validated()` in controllers. Always access validated values directly as properties.**

**For Multi-Tenant Resources (vessel-scoped):**
```php
public function store(StoreBankAccountRequest $request)
{
    try {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        // This ensures vessel_id comes from middleware-validated route, not from form/frontend
        $vesselId = $request->attributes->get('vessel_id');

        // Access validated values directly as properties (elegant and clean)
        $bankAccount = BankAccount::create([
            'name' => $request->name,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'iban' => $request->iban,
            'country_id' => $request->country_id,
            'vessel_id' => $vesselId,
            'initial_balance' => $request->initial_balance ?? 0,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return redirect()
            ->route('panel.bank-accounts.index', ['vessel' => $vesselId])
            ->with('success', "Bank account '{$bankAccount->name}' has been created successfully.")
            ->with('notification_delay', 3);
    } catch (\Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Failed to create bank account. Please try again.')
            ->with('notification_delay', 0);
    }
}
```

**For Non-Tenant Resources:**
```php
public function store(StoreTransactionRequest $request)
{
    // Access validated values directly as properties
    $transaction = Transaction::create([
        'vessel_id' => $request->vessel_id,
        'category_id' => $request->category_id,
        'bank_account_id' => $request->bank_account_id,
        'amount' => $request->amount,
        'description' => $request->description,
        'transaction_date' => $request->transaction_date,
        // ... other fields
    ]);
    
    $transaction->load(['vessel', 'category', 'bankAccount', 'vatRate']);

    return redirect()
        ->route('transactions.show', $transaction)
        ->with('success', 'Transaction created successfully.');
}
```

#### Show Actions
```php
public function show(Transaction $transaction)
{
    $transaction->load([
        'vessel',
        'category', 
        'bankAccount',
        'supplier',
        'crewMember',
        'vatRate',
        'attachments'
    ]);

    return inertia('Transactions/Show', [
        'transaction' => new TransactionResource($transaction),
    ]);
}
```

## Error Handling and Notifications

### Flash Message Patterns with Try-Catch Blocks

Controllers should use comprehensive error handling with specific flash messages for user feedback:

```php
public function store(StoreTransactionRequest $request)
{
    try {
        // Access validated values directly as properties (never use validated())
        $transaction = Transaction::create([
            'vessel_id' => $request->vessel_id,
            'category_id' => $request->category_id,
            'bank_account_id' => $request->bank_account_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            // ... other fields
        ]);
        
        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', "Transaction '{$transaction->transaction_number}' has been created successfully.");
            
    } catch (Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Failed to create transaction. Please try again.');
    }
}

public function update(UpdateTransactionRequest $request, Transaction $transaction)
{
    try {
        // Access validated values directly as properties (never use validated())
        $transaction->update([
            'vessel_id' => $request->vessel_id,
            'category_id' => $request->category_id,
            'bank_account_id' => $request->bank_account_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            // ... other fields
        ]);
        
        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', "Transaction '{$transaction->transaction_number}' has been updated successfully.");
            
    } catch (Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Failed to update transaction. Please try again.');
    }
}

public function destroy(Transaction $transaction)
{
    try {
        // Check constraints before deletion
        if ($transaction->attachments()->count() > 0) {
            return back()->with('error', 
                "Cannot delete transaction '{$transaction->transaction_number}' because it has attachments. Please remove all attachments first.");
        }

        $transactionNumber = $transaction->transaction_number;
        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', "Transaction '{$transactionNumber}' has been deleted successfully.");
            
    } catch (Exception $e) {
        return back()
            ->with('error', 'Failed to delete transaction. Please try again.');
    }
}
```

### Notification Best Practices

#### 1. Specific Success Messages
- Include entity names in success messages
- Use descriptive action descriptions
- Provide context about what was accomplished

#### 2. Constraint Checks for Destructive Operations
- Always check for related data before deletion
- Provide specific error messages explaining constraints
- Guide users on how to resolve issues

#### 3. Error Handling Patterns
- Use try-catch blocks for all database operations
- Provide generic error messages for unexpected failures
- Use `back()` with `withInput()` for validation errors
- Use `redirect()` with `route()` for successful operations

### Not Found Handling
```php
public function show($id)
{
    $transaction = Transaction::with(['vessel', 'category'])
        ->findOrFail($id);

    return inertia('Transactions/Show', [
        'transaction' => new TransactionResource($transaction),
    ]);
}
```

## Multi-Tenancy and Vessel Scoping (**CRITICAL**)

### ⚠️ **CRITICAL: Always Get `vessel_id` from Request Attributes**

For all vessel-scoped resources, `vessel_id` MUST come from request attributes set by `EnsureVesselAccess` middleware, NOT from form requests.

### How It Works

1. **Middleware (`EnsureVesselAccess`)**:
   - Validates user access to vessel from route parameter
   - Sets `vessel` and `vessel_id` in request attributes: `$request->attributes->set('vessel_id', $vesselId)`

2. **Controller**:
   - Gets `vessel_id` from request attributes: `$request->attributes->get('vessel_id')`
   - Adds it to validated data before creating/updating models

### Getting Vessel ID in Controllers

**Option 1: Direct from Request Attributes (Recommended)**
```php
public function store(StoreBankAccountRequest $request)
{
    // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
    $vesselId = $request->attributes->get('vessel_id');
    
    // Access validated values directly as properties (never use validated())
    $bankAccount = BankAccount::create([
        'name' => $request->name,
        'bank_name' => $request->bank_name,
        'vessel_id' => $vesselId,
        'status' => $request->status,
        // ... other fields
    ]);
    // ...
}
```

**Option 2: Using BaseController Helper Methods**
```php
use App\Http\Controllers\BaseController;

class BankAccountController extends BaseController
{
    public function store(StoreBankAccountRequest $request)
    {
        // Use BaseController helper method
        $vesselId = $this->getCurrentVesselId($request);
        
        // Access validated values directly as properties (never use validated())
        $bankAccount = BankAccount::create([
            'name' => $request->name,
            'bank_name' => $request->bank_name,
            'vessel_id' => $vesselId,
            'status' => $request->status,
            // ... other fields
        ]);
        // ...
    }
}
```

### Complete Multi-Tenant Controller Pattern

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        // Get vessel_id from request attributes
        $vesselId = $request->attributes->get('vessel_id');
        
        // Always filter by vessel_id
        $query = BankAccount::query()->where('vessel_id', $vesselId);
        
        // Apply filters, search, sorting...
        $bankAccounts = $query->paginate(15);
        
        return Inertia::render('BankAccounts/Index', [
            'bankAccounts' => BankAccountResource::collection($bankAccounts),
        ]);
    }

    public function store(StoreBankAccountRequest $request)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            $vesselId = $request->attributes->get('vessel_id');
            
            // Access validated values directly as properties (never use validated())
            $bankAccount = BankAccount::create([
                'name' => $request->name,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'iban' => $request->iban,
                'country_id' => $request->country_id,
                'vessel_id' => $vesselId,
                'initial_balance' => $request->initial_balance ?? 0,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);
            
            return redirect()
                ->route('panel.bank-accounts.index', ['vessel' => $vesselId])
                ->with('success', "Bank account '{$bankAccount->name}' has been created successfully.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create bank account. Please try again.');
        }
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount)
    {
        try {
            // Verify bank account belongs to current vessel
            $vesselId = $request->attributes->get('vessel_id');
            if ($bankAccount->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to bank account.');
            }
            
            // Access validated values directly as properties (never use validated())
            $bankAccount->update([
                'name' => $request->name,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'iban' => $request->iban,
                'country_id' => $request->country_id,
                'initial_balance' => $request->initial_balance ?? $bankAccount->initial_balance ?? 0,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);
            
            return redirect()
                ->route('panel.bank-accounts.index', ['vessel' => $vesselId])
                ->with('success', "Bank account '{$bankAccount->name}' has been updated successfully.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update bank account. Please try again.');
        }
    }

    public function destroy(Request $request, BankAccount $bankAccount)
    {
        try {
            // Verify bank account belongs to current vessel
            $vesselId = $request->attributes->get('vessel_id');
            if ($bankAccount->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to bank account.');
            }
            
            $bankAccountName = $bankAccount->name;
            $bankAccount->delete();
            
            return redirect()
                ->route('panel.bank-accounts.index', ['vessel' => $vesselId])
                ->with('success', "Bank account '{$bankAccountName}' has been deleted successfully.");
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete bank account. Please try again.');
        }
    }
}
```

### Key Principles

1. **Always Filter by Vessel**: Every query must include `where('vessel_id', $vesselId)`
2. **Get from Attributes**: Use `$request->attributes->get('vessel_id')` not `$request->route('vessel')`
3. **Verify Ownership**: In update/destroy, verify resource belongs to current vessel
4. **Never Trust Form Input**: `vessel_id` never comes from form requests

### Common Mistakes to Avoid

❌ **DON'T:**
```php
// ❌ WRONG - Getting from route directly
$vesselId = $request->route('vessel');

// ❌ WRONG - Getting from form input
$vesselId = $request->input('vessel_id');

// ❌ WRONG - Not filtering by vessel
$bankAccounts = BankAccount::all();

// ❌ WRONG - Using validated() method
$data = $request->validated();
$data['vessel_id'] = $vesselId;
$bankAccount = BankAccount::create($data);
```

✅ **DO:**
```php
// ✅ CORRECT - Getting from request attributes
$vesselId = $request->attributes->get('vessel_id');

// ✅ CORRECT - Always filter by vessel
$bankAccounts = BankAccount::where('vessel_id', $vesselId)->get();

// ✅ CORRECT - Verify ownership
if ($bankAccount->vessel_id !== $vesselId) {
    abort(403, 'Unauthorized access.');
}

// ✅ CORRECT - Access validated values directly as properties
$bankAccount = BankAccount::create([
    'name' => $request->name,
    'bank_name' => $request->bank_name,
    'vessel_id' => $vesselId,
    'status' => $request->status,
]);
```

## Service Integration

### Use Services for Complex Logic
```php
class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private BalanceService $balanceService
    ) {}

    public function store(StoreTransactionRequest $request)
    {
        // Access validated values directly as properties (never use validated())
        // If service needs array, pass individual values or build array from properties
        $transaction = $this->transactionService->create([
            'vessel_id' => $request->vessel_id,
            'category_id' => $request->category_id,
            'bank_account_id' => $request->bank_account_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            // ... other fields
        ]);
        
        // Update balances
        $this->balanceService->recalculateBalances($transaction);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction created successfully.');
    }
}
```

## API Endpoints

### Search Endpoints
```php
public function search(Request $request)
{
    $query = $request->get('q', '');
    
    $results = Transaction::where('transaction_number', 'like', "%{$query}%")
        ->orWhere('description', 'like', "%{$query}%")
        ->limit(10)
        ->get();

    return response()->json([
        'data' => TransactionResource::collection($results)
    ]);
}
```

### Details API Endpoints (For Show Modals)
```php
/**
 * Get detailed entity information for show modal
 * This endpoint provides full data with relationships for modal display
 */
public function details(Entity $entity)
{
    // Load all relationships needed for detailed view
    $entity->load([
        'relationship1',
        'relationship2',
        'relationship3.nestedRelationship'
    ]);

    return response()->json([
        'entity' => new EntityResource($entity),
    ]);
}
```

**Route Definition:**
```php
// routes/web.php
Route::get('api/entities/{entity}/details', [EntityController::class, 'details'])
    ->name('api.entities.details');
```

**Usage in Show Modal:**
```javascript
const fetchEntityDetails = async () => {
    const response = await fetch(`/api/entities/${entityId}/details`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
    });
    
    const data = await response.json();
    return data.entity;
};
```

## Authorization and Permissions

### Vessel-Based Role Access Control (RBAC)

The system implements a sophisticated vessel-specific RBAC system with four main roles: `Administrator`, `Supervisor`, `Moderator`, and `Normal User`. All authorization is vessel-scoped, meaning users can have different roles for different vessels.

#### Role Definitions

- **Administrator**: Full access to all features and settings for the specific vessel
- **Supervisor**: Full access to operational features, limited access to settings
- **Moderator**: Limited access to edit operations, no creation or deletion
- **Normal User**: Read-only access to most features

#### Vessel-Specific Authorization

All controllers must implement vessel-based authorization using the `BaseController` methods:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreCrewMemberRequest;
use App\Http\Requests\UpdateCrewMemberRequest;
use App\Http\Resources\CrewMemberResource;
use App\Models\CrewMember;
use App\Models\CrewPosition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrewMemberController extends BaseController
{
    /**
     * Display a listing of crew members for the current vessel.
     */
    public function index(Request $request)
    {
        $vesselId = $this->getCurrentVesselId(); // From BaseController
        
        $query = CrewMember::query()
            ->where('vessel_id', $vesselId) // Always filter by vessel
            ->with(['position', 'vessel']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        // Apply sorting
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $query->orderBy($sort, $direction);

        $crewMembers = $query->paginate(15);

        return Inertia::render('CrewMembers/Index', [
            'crewMembers' => CrewMemberResource::collection($crewMembers),
            'filters' => $request->only(['search', 'status', 'position_id', 'sort', 'direction']),
            'positions' => CrewPosition::where('vessel_id', $vesselId)->select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a newly created crew member for the current vessel.
     */
    public function store(StoreCrewMemberRequest $request)
    {
        try {
            $vesselId = $this->getCurrentVesselId(); // From BaseController
            
            // Access validated values directly as properties (never use validated())
            $crewMember = CrewMember::create([
                'position_id' => $request->position_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'vessel_id' => $vesselId,
                'status' => $request->status,
                // ... other fields
            ]);
            $crewMember->load(['position', 'vessel']);

            return redirect()
                ->route('crew-members.index', ['vessel' => $vesselId])
                ->with('success', "Crew member '{$crewMember->name}' has been created successfully.");
                
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create crew member. Please try again.');
        }
    }

    /**
     * Update the specified crew member.
     */
    public function update(UpdateCrewMemberRequest $request, CrewMember $crewMember)
    {
        try {
            // Verify crew member belongs to current vessel
            $vesselId = $this->getCurrentVesselId();
            if ($crewMember->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew member.');
            }

            // Access validated values directly as properties (never use validated())
            $crewMember->update([
                'position_id' => $request->position_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => $request->status,
                // ... other fields
            ]);
            $crewMember->load(['position', 'vessel']);

            return redirect()
                ->route('crew-members.index', ['vessel' => $vesselId])
                ->with('success', "Crew member '{$crewMember->name}' has been updated successfully.");
                
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update crew member. Please try again.');
        }
    }

    /**
     * Remove the specified crew member from storage.
     */
    public function destroy(CrewMember $crewMember)
    {
        try {
            // Verify crew member belongs to current vessel
            $vesselId = $this->getCurrentVesselId();
            if ($crewMember->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew member.');
            }

            $crewMemberName = $crewMember->name;
            $crewMember->delete();

            return redirect()
                ->route('crew-members.index', ['vessel' => $vesselId])
                ->with('success', "Crew member '{$crewMemberName}' has been deleted successfully.");
                
        } catch (Exception $e) {
            return back()
                ->with('error', 'Failed to delete crew member. Please try again.');
        }
    }
}

#### Middleware Protection

Use the `role` middleware to protect routes based on user roles:

```php
// routes/web.php
Route::middleware('role:admin,manager')->group(function () {
    Route::post('vessels', [VesselController::class, 'store'])->name('vessels.store');
    Route::put('vessels/{vessel}', [VesselController::class, 'update'])->name('vessels.update');
    Route::delete('vessels/{vessel}', [VesselController::class, 'destroy'])->name('vessels.destroy');
});

// All authenticated users can view
Route::get('vessels', [VesselController::class, 'index'])->name('vessels.index');
Route::get('vessels/{vessel}', [VesselController::class, 'show'])->name('vessels.show');
```

#### Request Authorization

Always implement authorization in Form Request classes:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVesselRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:255'],
            'vessel_type' => ['required', 'string', 'in:passenger,cargo,fishing,pleasure'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'status' => ['required', 'string', 'in:active,maintenance,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

#### User Model Role Methods

The User model includes methods for role checking:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the specified roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has all of the specified roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->count() === count($roles);
    }
}
```

#### Sharing Permissions with Frontend

The `HandleInertiaRequests` middleware shares user roles and permissions with the frontend:

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    public function rootView(Request $request): string
    {
        return 'app';
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->roles->pluck('name')->toArray(),
                    'permissions' => $this->getUserPermissions($request->user()),
                ] : null,
            ],
        ];
    }

    /**
     * Get user permissions based on roles.
     */
    private function getUserPermissions($user): array
    {
        $permissions = [];
        
        if ($user->hasRole('admin')) {
            $permissions = [
                'vessels.create' => true,
                'vessels.edit' => true,
                'vessels.delete' => true,
                'vessels.view' => true,
                'crew.create' => true,
                'crew.edit' => true,
                'crew.delete' => true,
                'crew.view' => true,
                'suppliers.create' => true,
                'suppliers.edit' => true,
                'suppliers.delete' => true,
                'suppliers.view' => true,
                'transactions.create' => true,
                'transactions.edit' => true,
                'transactions.delete' => true,
                'transactions.view' => true,
                'reports.access' => true,
                'settings.access' => true,
                'users.manage' => true,
            ];
        } elseif ($user->hasRole('manager')) {
            $permissions = [
                'vessels.create' => true,
                'vessels.edit' => true,
                'vessels.delete' => true,
                'vessels.view' => true,
                'crew.create' => true,
                'crew.edit' => true,
                'crew.delete' => true,
                'crew.view' => true,
                'suppliers.create' => true,
                'suppliers.edit' => true,
                'suppliers.delete' => true,
                'suppliers.view' => true,
                'transactions.create' => true,
                'transactions.edit' => true,
                'transactions.delete' => true,
                'transactions.view' => true,
                'reports.access' => true,
                'settings.access' => false,
                'users.manage' => false,
            ];
        } elseif ($user->hasRole('viewer')) {
            $permissions = [
                'vessels.create' => false,
                'vessels.edit' => false,
                'vessels.delete' => false,
                'vessels.view' => true,
                'crew.create' => false,
                'crew.edit' => false,
                'crew.delete' => false,
                'crew.view' => true,
                'suppliers.create' => false,
                'suppliers.edit' => false,
                'suppliers.delete' => false,
                'suppliers.view' => true,
                'transactions.create' => false,
                'transactions.edit' => false,
                'transactions.delete' => false,
                'transactions.view' => true,
                'reports.access' => true,
                'settings.access' => false,
                'users.manage' => false,
            ];
        }
        
        return $permissions;
    }
}
```

#### Controller Authorization Examples

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVesselRequest;
use App\Http\Requests\UpdateVesselRequest;
use App\Http\Resources\VesselResource;
use App\Models\Vessel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VesselController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // All authenticated users can view vessels
        $vessels = Vessel::with(['crewMembers', 'transactions'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Vessels/Index', [
            'vessels' => VesselResource::collection($vessels),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVesselRequest $request)
    {
        // Authorization is handled in the request class
        // Access validated values directly as properties (never use validated())
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

        return redirect()
            ->route('vessels.index')
            ->with('success', 'Vessel created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVesselRequest $request, Vessel $vessel)
    {
        // Authorization is handled in the request class
        // Access validated values directly as properties (never use validated())
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

        return redirect()
            ->route('vessels.index')
            ->with('success', 'Vessel updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vessel $vessel)
    {
        // Check authorization directly in controller
        if (!$request->user()->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Unauthorized action.');
        }

        $vessel->delete();

        return redirect()
            ->route('vessels.index')
            ->with('success', 'Vessel deleted successfully.');
    }
}
```

### Best Practices for Authorization

#### 1. Always Implement Authorization
- Never skip authorization checks
- Use middleware for route-level protection
- Implement request-level authorization
- Check permissions in controllers when needed

#### 2. Consistent Permission Naming
- Use `resource.action` format (e.g., `vessels.create`)
- Keep permission names consistent across frontend and backend
- Use descriptive permission names

#### 3. Security First
- Remember that frontend permissions are for UX only
- Always implement proper backend authorization
- Never rely solely on frontend permission checks
- Use HTTPS in production

#### 4. Error Handling
- Return appropriate HTTP status codes (403 for unauthorized)
- Provide clear error messages
- Log unauthorized access attempts
- Handle edge cases gracefully

## Examples

### Complete TransactionController Example
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    public function index(Request $request)
    {
        $transactions = Transaction::with(['vessel', 'category', 'bankAccount'])
            ->when($request->vessel_id, fn($q) => $q->where('vessel_id', $request->vessel_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->date_from, fn($q) => $q->where('transaction_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->where('transaction_date', '<=', $request->date_to))
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);

        $vessels = Vessel::active()->get();
        $categories = TransactionCategory::orderBy('name')->get();
        $bankAccounts = BankAccount::active()->get();
        
        $filters = $request->only(['vessel_id', 'type', 'date_from', 'date_to']);

        return Inertia::render('Transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'vessels' => VesselResource::collection($vessels),
            'categories' => TransactionCategoryResource::collection($categories),
            'bankAccounts' => BankAccountResource::collection($bankAccounts),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $vessels = Vessel::active()->get();
        $categories = TransactionCategory::orderBy('name')->get();
        $bankAccounts = BankAccount::active()->get();
        $vatRates = VatRate::active()->get();

        return Inertia::render('Transactions/Create', [
            'vessels' => VesselResource::collection($vessels),
            'categories' => TransactionCategoryResource::collection($categories),
            'bankAccounts' => BankAccountResource::collection($bankAccounts),
            'vatRates' => VatRateResource::collection($vatRates),
        ]);
    }

    public function store(StoreTransactionRequest $request)
    {
        // Access validated values directly as properties (never use validated())
        $transaction = $this->transactionService->create([
            'vessel_id' => $request->vessel_id,
            'category_id' => $request->category_id,
            'bank_account_id' => $request->bank_account_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            // ... other fields
        ]);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction created successfully.');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load([
            'vessel',
            'category', 
            'bankAccount',
            'supplier',
            'crewMember',
            'vatRate',
            'attachments'
        ]);

        return Inertia::render('Transactions/Show', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }

    public function edit(Transaction $transaction)
    {
        $vessels = Vessel::active()->get();
        $categories = TransactionCategory::orderBy('name')->get();
        $bankAccounts = BankAccount::active()->get();
        $vatRates = VatRate::active()->get();

        return Inertia::render('Transactions/Edit', [
            'transaction' => new TransactionResource($transaction),
            'vessels' => VesselResource::collection($vessels),
            'categories' => TransactionCategoryResource::collection($categories),
            'bankAccounts' => BankAccountResource::collection($bankAccounts),
            'vatRates' => VatRateResource::collection($vatRates),
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        // Access validated values directly as properties (never use validated())
        $this->transactionService->update($transaction, [
            'vessel_id' => $request->vessel_id,
            'category_id' => $request->category_id,
            'bank_account_id' => $request->bank_account_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            // ... other fields
        ]);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        $this->transactionService->delete($transaction);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
```
