# Resource Conditional Loading Pattern

## Overview

This pattern allows API resources to conditionally include relationships only when they are explicitly loaded, avoiding N+1 queries and providing flexible API responses.

## Key Benefits

1. **Performance** - Avoid N+1 queries by only loading relationships when needed
2. **Flexibility** - API consumers can choose what data to include
3. **Clean Code** - Use dedicated Resource classes instead of manual arrays
4. **Consistency** - Same pattern across all resources

---

## Pattern Implementation

### 1. Resource Pattern

Use `whenLoaded()` with a dedicated Resource class:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_id' => $this->country_id,
            
            // ✅ CORRECT: Use CountryResource with whenLoaded
            'country' => new CountryResource($this->whenLoaded('country')),
            
            // ❌ WRONG: Don't create manual arrays
            // 'country' => $this->country ? [
            //     'id' => $this->country->id,
            //     'name' => $this->country->name,
            // ] : null,
        ];
    }
}
```

### 2. Controller Pattern - Explicit Loading

#### Option A: Always Load (Show/Edit pages)

```php
public function show(BankAccount $bankAccount)
{
    // Explicitly load the relationship
    $bankAccount->load('country');
    
    return Inertia::render('BankAccounts/Show', [
        'bankAccount' => new BankAccountResource($bankAccount),
    ]);
}
```

#### Option B: Query Parameter Loading (Index/List pages)

```php
public function index(Request $request)
{
    $query = BankAccount::query();
    
    // Apply filters...
    
    // ✅ Load relationships based on ?include parameter
    if ($request->has('include')) {
        $includes = explode(',', $request->include);
        $query->with($includes);
    }
    
    $bankAccounts = $query->paginate(15);
    
    return BankAccountResource::collection($bankAccounts);
}
```

#### Option C: Always Eager Load (When Always Needed)

```php
public function index(Request $request)
{
    $query = BankAccount::with('country')->get();
    
    return BankAccountResource::collection($query);
}
```

---

## Usage Examples

### Example 1: BankAccount with Country

**Resource:**
```php
class BankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_id' => $this->country_id,
            'country' => new CountryResource($this->whenLoaded('country')),
        ];
    }
}
```

**Controller:**
```php
// Without country
$accounts = BankAccount::all();
// country field will NOT be included

// With country
$accounts = BankAccount::with('country')->all();
// country field WILL be included with full CountryResource data

// Dynamic loading via query param
// GET /api/bank-accounts?include=country
if ($request->has('include')) {
    $accounts = BankAccount::with(explode(',', $request->include))->get();
}
```

**API Response Without Loading:**
```json
{
  "id": 1,
  "name": "Main Account",
  "country_id": 178
  // country field is NOT included
}
```

**API Response With Loading:**
```json
{
  "id": 1,
  "name": "Main Account",
  "country_id": 178,
  "country": {
    "id": 178,
    "name": "Portugal",
    "code": "PT",
    "formatted_display": "Portugal (PT)"
  }
}
```

### Example 2: CrewMember with Vessel and Position

**Resource:**
```php
class CrewMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vessel_id' => $this->vessel_id,
            'position_id' => $this->position_id,
            
            // Full nested resource
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
            
            // Or just a field (if no resource exists)
            'position_name' => $this->whenLoaded('position', fn() => $this->position->name),
        ];
    }
}
```

**Controller:**
```php
// Load multiple relationships
$crewMembers = CrewMember::with(['vessel', 'position'])->get();

// Or dynamic loading
// GET /api/crew-members?include=vessel,position
if ($request->has('include')) {
    $includes = explode(',', $request->include);
    $crewMembers = CrewMember::with($includes)->get();
}
```

### Example 3: Transaction with Multiple Relationships

**Resource:**
```php
class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            
            // Conditional relationships
            'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
        ];
    }
}
```

**Controller:**
```php
// Load only what's needed
$transactions = Transaction::with(['bankAccount', 'category'])->get();

// Or all relationships
$transaction = Transaction::with([
    'bankAccount.country',
    'category',
    'supplier',
    'vessel'
])->find($id);

// Or dynamic
// GET /api/transactions?include=bankAccount,category,supplier,vessel
if ($request->has('include')) {
    $includes = explode(',', $request->include);
    $transactions = Transaction::with($includes)->get();
}
```

---

## API Usage Patterns

### Pattern 1: Query Parameter Loading

**URL:**
```
GET /api/bank-accounts?include=country
GET /api/crew-members?include=vessel,position
GET /api/transactions?include=bankAccount,category,supplier
```

**Controller:**
```php
public function index(Request $request)
{
    $query = Model::query();
    
    if ($request->has('include')) {
        $includes = explode(',', $request->include);
        $query->with($includes);
    }
    
    return ResourceClass::collection($query->get());
}
```

### Pattern 2: Always Load Specific Relationships

**For Detail Pages:**
```php
public function show(Model $model)
{
    $model->load(['relationship1', 'relationship2']);
    
    return new ResourceClass($model);
}
```

**For Lists Where Always Needed:**
```php
public function index()
{
    $models = Model::with('essentialRelationship')->get();
    
    return ResourceClass::collection($models);
}
```

### Pattern 3: Nested Relationship Loading

```php
// Load country through bankAccount
$transactions = Transaction::with('bankAccount.country')->get();

// In TransactionResource
'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),

// BankAccountResource will automatically include country if loaded
'country' => new CountryResource($this->whenLoaded('country')),
```

---

## Best Practices

### ✅ DO

```php
// Use dedicated Resource classes
'country' => new CountryResource($this->whenLoaded('country')),

// Load relationships explicitly in controllers
$model->load('relationship');

// Use with() for eager loading
Model::with('relationship')->get();

// Support dynamic loading via query params
if ($request->has('include')) {
    $query->with(explode(',', $request->include));
}

// Document available includes in API
// GET /api/bank-accounts?include=country
// GET /api/crew-members?include=vessel,position
```

### ❌ DON'T

```php
// Don't create manual arrays in resources
'country' => $this->country ? [
    'id' => $this->country->id,
    'name' => $this->country->name,
] : null,

// Don't always load relationships (causes N+1)
public function toArray($request): array
{
    return [
        'country' => new CountryResource($this->country), // BAD!
    ];
}

// Don't forget to eager load in controllers
$models = Model::all(); // BAD! Will cause N+1 if resource uses relationships
foreach ($models as $model) {
    $model->country; // N+1 query here!
}

// Don't nest resources without whenLoaded
'country' => new CountryResource($this->country), // BAD! Always tries to load
```

---

## Common Patterns by Use Case

### List Pages (Index)

```php
// Option 1: Dynamic loading
if ($request->has('include')) {
    $query->with(explode(',', $request->include));
}

// Option 2: Always load essential data
$query->with('essentialRelationship');
```

### Detail Pages (Show/Edit)

```php
// Always load what you need
$model->load(['relationship1', 'relationship2']);
```

### API Endpoints

```php
// Support flexible includes
// GET /api/resources?include=rel1,rel2
if ($request->has('include')) {
    $includes = explode(',', $request->include);
    $query->with($includes);
}
```

### Nested Resources

```php
// Load nested relationships
Model::with('parent.child')->get();

// Each resource uses whenLoaded
'parent' => new ParentResource($this->whenLoaded('parent')),
// ParentResource
'child' => new ChildResource($this->whenLoaded('child')),
```

---

## Performance Considerations

### Avoid N+1 Queries

**❌ Bad:**
```php
$accounts = BankAccount::all(); // 1 query
foreach ($accounts as $account) {
    echo $account->country->name; // N queries!
}
```

**✅ Good:**
```php
$accounts = BankAccount::with('country')->all(); // 2 queries total
foreach ($accounts as $account) {
    echo $account->country->name; // No additional queries
}
```

### Selective Loading

Only load what you need:

```php
// For display list - maybe don't need relationships
$accounts = BankAccount::paginate(15);

// For export - need everything
$accounts = BankAccount::with(['country', 'transactions'])->get();

// For detail page - need specific relationships
$account = BankAccount::with(['country', 'transactions.category'])->find($id);
```

---

## Testing

```php
use App\Models\BankAccount;
use App\Http\Resources\BankAccountResource;

test('resource does not include country when not loaded', function () {
    $account = BankAccount::factory()->create();
    
    $resource = new BankAccountResource($account);
    $data = $resource->resolve();
    
    expect($data)->not->toHaveKey('country');
});

test('resource includes country when loaded', function () {
    $account = BankAccount::with('country')->first();
    
    $resource = new BankAccountResource($account);
    $data = $resource->resolve();
    
    expect($data)
        ->toHaveKey('country')
        ->and($data['country'])->toBeArray()
        ->and($data['country'])->toHaveKey('name');
});

test('controller loads relationships via include parameter', function () {
    $response = $this->get('/api/bank-accounts?include=country');
    
    $response->assertOk();
    $data = $response->json('data');
    
    expect($data[0])->toHaveKey('country');
});
```

---

## Summary

| Situation | Pattern | Example |
|-----------|---------|---------|
| Always need relationship | Eager load in controller | `Model::with('rel')->get()` |
| Sometimes need relationship | Query parameter | `?include=rel` |
| Nested relationship | Dot notation | `Model::with('parent.child')` |
| Multiple relationships | Array | `Model::with(['rel1', 'rel2'])` |
| In resource | Use whenLoaded | `new Resource($this->whenLoaded('rel'))` |

**Key Principle**: Resources use `whenLoaded()`, controllers decide what to load with `with()` or `load()`.

