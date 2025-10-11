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
```php
public function store(StoreTransactionRequest $request)
{
    $transaction = Transaction::create($request->validated());
    
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

## Error Handling

### Validation Errors
```php
public function store(StoreTransactionRequest $request)
{
    try {
        $transaction = Transaction::create($request->validated());
        
        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction created successfully.');
            
    } catch (Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Failed to create transaction. Please try again.');
    }
}
```

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
        $transaction = $this->transactionService->create($request->validated());
        
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

## Authorization

### Use Middleware and Policies
```php
class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view,transaction')->only(['show']);
        $this->middleware('can:create,App\Models\Transaction')->only(['create', 'store']);
        $this->middleware('can:update,transaction')->only(['edit', 'update']);
        $this->middleware('can:delete,transaction')->only(['destroy']);
    }
}
```

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
        $transaction = $this->transactionService->create($request->validated());

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
        $this->transactionService->update($transaction, $request->validated());

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
