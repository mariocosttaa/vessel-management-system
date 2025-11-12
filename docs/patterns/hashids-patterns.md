# Hashids Patterns

## Overview

This system uses Hashids to obfuscate database IDs before sending them to the frontend. All IDs are hashed when sent to the frontend and unhashed when received from the frontend.

## Hash Type Pattern

All hash types follow the pattern: `{model-name}-id`

Examples:
- `vessel-id` - For vessel model IDs
- `transaction-id` - For transaction model IDs
- `user-id` - For user model IDs
- `supplier-id` - For supplier model IDs
- `transactioncategory-id` - For transaction category model IDs
- `crewposition-id` - For crew position model IDs

## EasyHashAction

The `EasyHashAction` class provides static methods for encoding and decoding IDs:

```php
use App\Actions\General\EasyHashAction;

// Encode an ID
$hashedId = EasyHashAction::encode($id, 'vessel-id');

// Decode a hashed ID
$id = EasyHashAction::decode($hashedId, 'vessel-id');
```

### Method Signatures

```php
public static function encode(string|int|null $valueToBeEncode, string $type = '', int $minReturnEncode = 21): ?string
public static function decode(string $valueEncoded, string $type = '', int $minReturnEncode = 21): int|string|null
```

### Parameters

- `$valueToBeEncode`: The ID to hash (integer or string)
- `$type`: The hash type (e.g., `'vessel-id'`, `'transaction-id'`)
- `$minReturnEncode`: Minimum length of the hashed string (default: 21)

## Controller Pattern: HashesIds Trait

### Using the Trait

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HashesIds;

class VesselController extends Controller
{
    use HashesIds;

    public function index(Request $request)
    {
        $vessels = Vessel::all()->map(function ($vessel) {
            return [
                'id' => $this->hashId($vessel->id, 'vessel'), // ✅ Pass 'vessel', not 'vessel-id'
                'name' => $vessel->name,
            ];
        });

        return Inertia::render('Vessels/Index', [
            'vessels' => $vessels,
        ]);
    }

    public function store(StoreVesselRequest $request)
    {
        // Unhash ID from frontend
        $categoryId = $this->unhashId($request->category_id, 'transactioncategory'); // ✅ Pass 'transactioncategory', not 'transactioncategory-id'
        
        // ... create logic
    }
}
```

### Important: Model Name Only

**CRITICAL**: The `HashesIds` trait automatically appends `-id` to the model name. Always pass only the model name, not the full hash type.

```php
// ✅ CORRECT
$this->hashId($vessel->id, 'vessel'); // Becomes 'vessel-id'
$this->unhashId($request->vessel_id, 'vessel'); // Becomes 'vessel-id'

// ❌ WRONG
$this->hashId($vessel->id, 'vessel-id'); // Becomes 'vessel-id-id' (WRONG!)
$this->unhashId($request->vessel_id, 'vessel-id'); // Becomes 'vessel-id-id' (WRONG!)
```

### Trait Methods

```php
// Hash an ID for sending to frontend
protected function hashId(?int $id, string $modelName): ?string

// Unhash an ID received from frontend
protected function unhashId(?string $hashedId, string $modelName): ?int
```

## Resource Pattern: BaseResource

### Using BaseResource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TransactionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            // Hash main model ID (automatically uses 'transaction-id')
            'id' => $this->hashId($this->id),
            
            // Hash foreign key IDs
            'vessel_id' => $this->hashIdForModel($this->vessel_id, 'vessel'), // ✅ Pass 'vessel', not 'vessel-id'
            'category_id' => $this->hashIdForModel($this->category_id, 'transactioncategory'),
            'supplier_id' => $this->hashIdForModel($this->supplier_id, 'supplier'),
        ];
    }
}
```

### Important: Model Name Only

**CRITICAL**: The `hashIdForModel()` method automatically appends `-id` to the model name. Always pass only the model name, not the full hash type.

```php
// ✅ CORRECT
$this->hashIdForModel($this->vessel_id, 'vessel'); // Becomes 'vessel-id'

// ❌ WRONG
$this->hashIdForModel($this->vessel_id, 'vessel-id'); // Becomes 'vessel-id-id' (WRONG!)
```

### BaseResource Methods

```php
// Hash main model ID (uses class name to determine model)
protected function hashId(string|int|null $id): ?string

// Hash ID for a specific model type
protected function hashIdForModel(string|int|null $id, string $modelName): ?string
```

## Model Route Binding

Models that appear in URLs should implement `getRouteKey()` and `resolveRouteBinding()`:

```php
<?php

namespace App\Models;

use App\Actions\General\EasyHashAction;

class Vessel extends Model
{
    /**
     * Get the route key for the model (hashed ID).
     */
    public function getRouteKey(): string
    {
        return EasyHashAction::encode($this->id, 'vessel-id');
    }

    /**
     * Retrieve the model by hashed route key value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (empty($value)) {
            return null;
        }

        // Try to decode as hashed ID first
        $decoded = EasyHashAction::decode($value, 'vessel-id');
        if ($decoded && is_numeric($decoded)) {
            return $this->where($field ?: $this->getRouteKeyName(), (int) $decoded)->first();
        }

        // Fallback to numeric ID for backward compatibility
        if (is_numeric($value)) {
            return $this->where($field ?: $this->getRouteKeyName(), (int) $value)->first();
        }

        return null;
    }
}
```

## Middleware: Direct EasyHashAction Usage

In middleware, use `EasyHashAction` directly with the full hash type:

```php
<?php

namespace App\Http\Middleware;

use App\Actions\General\EasyHashAction;

class EnsureVesselAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $vesselParam = $request->route('vessel');
        
        // Decode hashed ID
        $vesselId = EasyHashAction::decode($vesselParam, 'vessel-id'); // ✅ Use full hash type
        
        if (!$vesselId || !is_numeric($vesselId)) {
            abort(404, 'Vessel not found.');
        }
        
        // ... rest of logic
    }
}
```

## Complete Examples

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HashesIds;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    use HashesIds;

    public function index(Request $request)
    {
        $vesselId = $request->attributes->get('vessel_id');
        
        $transactions = Transaction::where('vessel_id', $vesselId)
            ->with(['category', 'supplier'])
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $this->hashId($transaction->id, 'transaction'),
                    'category_id' => $this->hashId($transaction->category_id, 'transactioncategory'),
                    'supplier_id' => $this->hashId($transaction->supplier_id, 'supplier'),
                    'amount' => $transaction->amount,
                ];
            });

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function store(StoreTransactionRequest $request)
    {
        // Unhash IDs from frontend
        $categoryId = $this->unhashId($request->category_id, 'transactioncategory');
        $supplierId = $this->unhashId($request->supplier_id, 'supplier');
        
        $transaction = Transaction::create([
            'vessel_id' => $request->attributes->get('vessel_id'),
            'category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'amount' => $request->amount,
        ]);

        $vesselId = $request->attributes->get('vessel_id');
        
        return redirect()
            ->route('panel.transactions.show', [
                'vessel' => $this->hashId($vesselId, 'vessel'),
                'transaction' => $transaction->getRouteKey(),
            ])
            ->with('success', 'Transaction created successfully.');
    }

    public function show(Request $request, $transactionId)
    {
        // Unhash transaction ID from route
        $transactionId = $this->unhashId($transactionId, 'transaction');
        
        if (!$transactionId) {
            abort(404, 'Transaction not found.');
        }
        
        $transaction = Transaction::findOrFail($transactionId);
        
        return Inertia::render('Transactions/Show', [
            'transaction' => new TransactionResource($transaction),
        ]);
    }
}
```

### Resource Example

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TransactionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            // Main model ID
            'id' => $this->hashId($this->id),
            
            // Foreign key IDs
            'vessel_id' => $this->hashIdForModel($this->vessel_id, 'vessel'),
            'category_id' => $this->hashIdForModel($this->category_id, 'transactioncategory'),
            'supplier_id' => $this->hashIdForModel($this->supplier_id, 'supplier'),
            'crew_member_id' => $this->hashIdForModel($this->crew_member_id, 'user'),
            
            // Relationships with hashed IDs
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->hashIdForModel($this->category->id, 'transactioncategory'),
                    'name' => $this->category->name,
                ];
            }),
            
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->hashIdForModel($this->supplier->id, 'supplier'),
                    'name' => $this->supplier->name,
                ];
            }),
            
            // Or use nested resources (recommended)
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
        ];
    }
}
```

## Common Mistakes to Avoid

### ❌ Wrong: Passing Full Hash Type to Trait Methods

```php
// ❌ WRONG - Don't pass 'vessel-id' to trait methods
$this->hashId($vessel->id, 'vessel-id'); // Becomes 'vessel-id-id'
$this->unhashId($request->vessel_id, 'vessel-id'); // Becomes 'vessel-id-id'
$this->hashIdForModel($this->vessel_id, 'vessel-id'); // Becomes 'vessel-id-id'
```

### ✅ Correct: Passing Model Name Only

```php
// ✅ CORRECT - Pass only the model name
$this->hashId($vessel->id, 'vessel'); // Becomes 'vessel-id'
$this->unhashId($request->vessel_id, 'vessel'); // Becomes 'vessel-id'
$this->hashIdForModel($this->vessel_id, 'vessel'); // Becomes 'vessel-id'
```

### ❌ Wrong: Using Numeric IDs in Responses

```php
// ❌ WRONG - Don't send numeric IDs to frontend
return [
    'id' => $vessel->id,
    'vessel_id' => $transaction->vessel_id,
];
```

### ✅ Correct: Always Hash IDs in Responses

```php
// ✅ CORRECT - Always hash IDs before sending to frontend
return [
    'id' => $this->hashId($vessel->id, 'vessel'),
    'vessel_id' => $this->hashIdForModel($transaction->vessel_id, 'vessel'),
];
```

### ❌ Wrong: Using Hashed IDs in Database Queries

```php
// ❌ WRONG - Don't use hashed IDs directly in queries
Transaction::where('category_id', $request->category_id)->get();
```

### ✅ Correct: Always Unhash Before Database Queries

```php
// ✅ CORRECT - Always unhash IDs before database queries
$categoryId = $this->unhashId($request->category_id, 'transactioncategory');
Transaction::where('category_id', $categoryId)->get();
```

## Best Practices

1. **Always hash IDs in responses** - Never send numeric IDs to the frontend
2. **Always unhash IDs from requests** - Never use hashed IDs in database queries
3. **Use model name only** - When using trait methods, pass only the model name (e.g., `'vessel'`), not the full hash type (e.g., `'vessel-id'`)
4. **Use full hash type in middleware** - When using `EasyHashAction` directly, use the full hash type (e.g., `'vessel-id'`)
5. **Implement route binding** - Models that appear in URLs should implement `getRouteKey()` and `resolveRouteBinding()`
6. **Consistent naming** - Use lowercase model names consistently (e.g., `'transactioncategory'`, not `'TransactionCategory'`)

## Model Name Reference

Common model names for hashing:

- `'vessel'` → `'vessel-id'`
- `'transaction'` → `'transaction-id'`
- `'user'` → `'user-id'`
- `'supplier'` → `'supplier-id'`
- `'transactioncategory'` → `'transactioncategory-id'`
- `'crewposition'` → `'crewposition-id'`
- `'marea'` → `'marea-id'`
- `'maintenance'` → `'maintenance-id'`
- `'vatprofile'` → `'vatprofile-id'`
- `'vesselroleaccess'` → `'vesselroleaccess-id'`

