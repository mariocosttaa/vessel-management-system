# Resource Patterns

## Structure and Naming Conventions

### Resource Naming
- Use singular, PascalCase: `TransactionResource`, `VesselResource`
- Place in `app/Http/Resources/`
- Follow Laravel conventions

### Resource Organization
Create folders for different contexts to prevent N+1 queries and provide different data structures:

```
app/Http/Resources/
├── General/           # General purpose resources (minimal data)
│   ├── TransactionResource.php
│   ├── VesselResource.php
│   └── CrewMemberResource.php
├── Detailed/          # Detailed resources (with relationships)
│   ├── TransactionResource.php
│   ├── VesselResource.php
│   └── CrewMemberResource.php
├── List/              # List resources (for tables/lists)
│   ├── TransactionResource.php
│   ├── VesselResource.php
│   └── CrewMemberResource.php
└── Api/               # API-specific resources
    ├── TransactionResource.php
    ├── VesselResource.php
    └── CrewMemberResource.php
```

### Usage Examples
```php
// In Controller - General resource (minimal data)
return new General\TransactionResource($transaction);

// In Controller - Detailed resource (with relationships)
return new Detailed\TransactionResource($transaction);

// In Controller - List resource (for tables)
return TransactionResource::collection($transactions);

// In Controller - API resource (for external APIs)
return new Api\TransactionResource($transaction);
```

## Basic Structure
```php
<?php

namespace App\Http\Resources\General;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status_label,
            
            // Relationships
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
            'category' => new TransactionCategoryResource($this->whenLoaded('category')),
            'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'crew_member' => new CrewMemberResource($this->whenLoaded('crewMember')),
            'vat_rate' => new VatRateResource($this->whenLoaded('vatRate')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

## Resource Context Examples

### General Resource (Minimal Data)
```php
<?php

namespace App\Http\Resources\General;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status_label,
        ];
    }
}
```

### Detailed Resource (With Relationships)
```php
<?php

namespace App\Http\Resources\Detailed;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => $this->type_label,
            
            // Money values
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'vat_amount' => $this->vat_amount,
            'formatted_vat_amount' => $this->formatted_vat_amount,
            'total_amount' => $this->total_amount,
            'formatted_total_amount' => $this->formatted_total_amount,
            'currency' => $this->currency,
            'house_of_zeros' => $this->house_of_zeros,
            
            // Dates
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'formatted_transaction_date' => $this->transaction_date?->format('d/m/Y'),
            
            // Descriptions
            'description' => $this->description,
            'notes' => $this->notes,
            'reference' => $this->reference,
            
            // Status
            'status' => $this->status,
            'status_label' => $this->status_label,
            
            // Flags
            'is_recurring' => $this->is_recurring,
            
            // Relationships
            'vessel' => new General\VesselResource($this->whenLoaded('vessel')),
            'category' => new General\TransactionCategoryResource($this->whenLoaded('category')),
            'bank_account' => new General\BankAccountResource($this->whenLoaded('bankAccount')),
            'supplier' => new General\SupplierResource($this->whenLoaded('supplier')),
            'crew_member' => new General\CrewMemberResource($this->whenLoaded('crewMember')),
            'vat_rate' => new General\VatRateResource($this->whenLoaded('vatRate')),
            'created_by' => new General\UserResource($this->whenLoaded('createdBy')),
            'attachments' => General\AttachmentResource::collection($this->whenLoaded('attachments')),
            
            // Additional data for detailed views
            'detailed_info' => $this->when($request->include_details, [
                'transaction_month' => $this->transaction_month,
                'transaction_year' => $this->transaction_year,
                'recurring_transaction_id' => $this->recurring_transaction_id,
            ]),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### List Resource (For Tables/Lists)
```php
<?php

namespace App\Http\Resources\List;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'formatted_transaction_date' => $this->transaction_date?->format('d/m/Y'),
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status_label,
            
            // Minimal relationship data for lists
            'vessel' => $this->whenLoaded('vessel', [
                'id' => $this->vessel->id,
                'name' => $this->vessel->name,
                'registration_number' => $this->vessel->registration_number,
            ]),
            'category' => $this->whenLoaded('category', [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type,
                'color' => $this->category->color,
            ]),
            'bank_account' => $this->whenLoaded('bankAccount', [
                'id' => $this->bankAccount->id,
                'name' => $this->bankAccount->name,
                'bank_name' => $this->bankAccount->bank_name,
            ]),
        ];
    }
}
```

### API Resource (For External APIs)
```php
<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status,
            
            // API-specific fields
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional API data
            'vessel' => $this->when($request->include_vessel && $this->relationLoaded('vessel'), [
                'id' => $this->vessel->id,
                'name' => $this->vessel->name,
                'registration_number' => $this->vessel->registration_number,
            ]),
            'category' => $this->when($request->include_category && $this->relationLoaded('category'), [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type,
            ]),
        ];
    }
}
```

### Date Formatting
```php
public function toArray(Request $request): array
{
    return [
        'transaction_date' => $this->transaction_date?->format('Y-m-d'),
        'formatted_transaction_date' => $this->transaction_date?->format('d/m/Y'),
        'hire_date' => $this->hire_date?->format('Y-m-d'),
        'formatted_hire_date' => $this->hire_date?->format('d/m/Y'),
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

### Status and Type Labels
```php
public function toArray(Request $request): array
{
    return [
        'type' => $this->type,
        'type_label' => $this->type_label,
        'status' => $this->status,
        'status_label' => $this->status_label,
        'vessel_status' => $this->vessel_status,
        'vessel_status_label' => $this->vessel_status_label,
    ];
}
```

## Conditional Field Inclusion

### Using whenLoaded for Relationships
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        
        // Only include if relationship is loaded
        'vessel' => new VesselResource($this->whenLoaded('vessel')),
        'category' => new TransactionCategoryResource($this->whenLoaded('category')),
        'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),
        'supplier' => new SupplierResource($this->whenLoaded('supplier')),
        'crew_member' => new CrewMemberResource($this->whenLoaded('crewMember')),
        'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
    ];
}
```

### Using whenLoaded with Closures (Recommended Pattern)
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        
        // Use closures to ensure proper resource instantiation
        'country' => $this->whenLoaded('country', function () {
            return new CountryResource($this->country);
        }),
        'vessel' => $this->whenLoaded('vessel', function () {
            return new VesselResource($this->vessel);
        }),
        'category' => $this->whenLoaded('category', function () {
            return new TransactionCategoryResource($this->category);
        }),
    ];
}
```

**Why use closures?** Closures ensure that the resource is only instantiated when the relationship is actually loaded, preventing errors when the relationship is null or not loaded.

### Using when for Conditional Fields
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'amount' => $this->amount,
        
        // Only include formatted amount if needed
        'formatted_amount' => $this->when($request->include_formatted, $this->formatted_amount),
        
        // Only include sensitive data for authorized users
        'salary_amount' => $this->when($request->user()?->can('view_salary', $this), $this->salary_amount),
        
        // Include additional data based on request
        'detailed_info' => $this->when($request->include_details, [
            'notes' => $this->notes,
            'reference' => $this->reference,
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
        ]),
    ];
}
```

### Using whenPivotLoaded for Pivot Data
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        
        // Include pivot data if loaded
        'pivot' => $this->whenPivotLoaded('vessel_crew', function () {
            return [
                'assigned_at' => $this->pivot->assigned_at,
                'role' => $this->pivot->role,
            ];
        }),
    ];
}
```

## Nested Resource Handling

### Simple Nested Resources
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'transaction_number' => $this->transaction_number,
        
        // Nested resources
        'vessel' => new VesselResource($this->whenLoaded('vessel')),
        'category' => new TransactionCategoryResource($this->whenLoaded('category')),
        'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),
    ];
}
```

### Complex Nested Resources with Additional Data
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        
        // Vessel with additional transaction summary
        'vessel' => $this->whenLoaded('vessel', function () {
            return [
                'id' => $this->vessel->id,
                'name' => $this->vessel->name,
                'registration_number' => $this->vessel->registration_number,
                'status' => $this->vessel->status,
                'status_label' => $this->vessel->status_label,
                
                // Additional computed data
                'total_transactions' => $this->vessel->transactions_count ?? 0,
                'current_balance' => $this->vessel->current_balance ?? 0,
            ];
        }),
    ];
}
```

### Collection Resources
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        
        // Collections of resources
        'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        'crew_members' => CrewMemberResource::collection($this->whenLoaded('crewMembers')),
        'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        
        // With additional metadata
        'transactions_summary' => $this->whenLoaded('transactions', function () {
            return [
                'count' => $this->transactions->count(),
                'total_income' => $this->transactions->where('type', 'income')->sum('total_amount'),
                'total_expense' => $this->transactions->where('type', 'expense')->sum('total_amount'),
            ];
        }),
    ];
}
```

## Money Formatting for Frontend

### Complete Money Resource Pattern
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        
        // Raw money values (integers)
        'amount' => $this->amount,
        'vat_amount' => $this->vat_amount,
        'total_amount' => $this->total_amount,
        
        // Formatted money values (strings)
        'formatted_amount' => $this->formatted_amount,
        'formatted_vat_amount' => $this->formatted_vat_amount,
        'formatted_total_amount' => $this->formatted_total_amount,
        
        // Currency information
        'currency' => $this->currency,
        'house_of_zeros' => $this->house_of_zeros,
        
        // Additional money calculations
        'amount_without_vat' => $this->amount,
        'vat_percentage' => $this->when($this->vat_rate_id, function () {
            return $this->vatRate->rate ?? 0;
        }),
    ];
}
```

### Salary Resource Pattern
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        
        // Salary information
        'salary_amount' => $this->salary_amount,
        'formatted_salary_amount' => $this->formatted_salary_amount,
        'salary_currency' => $this->salary_currency,
        'payment_frequency' => $this->payment_frequency,
        'payment_frequency_label' => $this->getPaymentFrequencyLabel(),
        
        // Computed salary data
        'monthly_salary' => $this->getMonthlySalary(),
        'formatted_monthly_salary' => $this->getFormattedMonthlySalary(),
    ];
}

private function getPaymentFrequencyLabel(): string
{
    return match($this->payment_frequency) {
        'weekly' => 'Semanal',
        'biweekly' => 'Quinzenal',
        'monthly' => 'Mensal',
        default => $this->payment_frequency,
    };
}

private function getMonthlySalary(): int
{
    return match($this->payment_frequency) {
        'weekly' => $this->salary_amount * 4,
        'biweekly' => $this->salary_amount * 2,
        'monthly' => $this->salary_amount,
        default => $this->salary_amount,
    };
}

private function getFormattedMonthlySalary(): string
{
    return $this->formatMoney($this->getMonthlySalary(), $this->salary_currency, 2);
}
```

## Relationship Loading

### Eager Loading in Controllers
```php
// In Controller
public function index(Request $request)
{
    $transactions = Transaction::with([
        'vessel:id,name,registration_number',
        'category:id,name,type,color',
        'bankAccount:id,name,bank_name',
        'supplier:id,name',
        'crewMember:id,name,position_id',
        'vatRate:id,name,rate'
    ])->paginate(15);

    return inertia('Transactions/Index', [
        'transactions' => TransactionResource::collection($transactions),
    ]);
}
```

### Resource with Selective Loading
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'transaction_number' => $this->transaction_number,
        
        // Only load specific fields for performance
        'vessel' => $this->whenLoaded('vessel', function () {
            return [
                'id' => $this->vessel->id,
                'name' => $this->vessel->name,
                'registration_number' => $this->vessel->registration_number,
            ];
        }),
        
        'category' => $this->whenLoaded('category', function () {
            return [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type,
                'color' => $this->category->color,
            ];
        }),
    ];
}
```

## ID Hashing with BaseResource

### Overview
All resources that extend `BaseResource` automatically have access to ID hashing methods. All IDs sent to the frontend must be hashed.

### Using BaseResource Hashing Methods

**Important**: The `hashIdForModel()` method automatically appends `-id` to the model name. When calling `hashIdForModel()`, pass only the model name (e.g., `'vessel'`), not the full hash type (e.g., `'vessel-id'`).

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TransactionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            // Hash the main model ID
            'id' => $this->hashId($this->id), // Automatically uses 'transaction-id'
            
            // Hash foreign key IDs
            'category_id' => $this->hashIdForModel($this->category_id, 'transactioncategory'),
            'supplier_id' => $this->hashIdForModel($this->supplier_id, 'supplier'),
            'vessel_id' => $this->hashIdForModel($this->vessel_id, 'vessel'),
            
            // Hash IDs in relationships
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
        ];
    }
}
```

### Common Patterns

#### Hashing Main Model ID
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->hashId($this->id), // Automatically uses model name from class
        'name' => $this->name,
    ];
}
```

#### Hashing Foreign Key IDs
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->hashId($this->id),
        'vessel_id' => $this->hashIdForModel($this->vessel_id, 'vessel'),
        'category_id' => $this->hashIdForModel($this->category_id, 'transactioncategory'),
        'supplier_id' => $this->hashIdForModel($this->supplier_id, 'supplier'),
    ];
}
```

#### Hashing IDs in Relationships
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->hashId($this->id),
        
        'vessel' => $this->whenLoaded('vessel', function () {
            return [
                'id' => $this->hashIdForModel($this->vessel->id, 'vessel'),
                'name' => $this->vessel->name,
            ];
        }),
        
        'category' => $this->whenLoaded('category', function () {
            return [
                'id' => $this->hashIdForModel($this->category->id, 'transactioncategory'),
                'name' => $this->category->name,
            ];
        }),
    ];
}
```

#### Using Nested Resources (Recommended)
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->hashId($this->id),
        
        // Use nested resources which handle hashing automatically
        'vessel' => new VesselResource($this->whenLoaded('vessel')),
        'category' => new TransactionCategoryResource($this->whenLoaded('category')),
        'supplier' => new SupplierResource($this->whenLoaded('supplier')),
    ];
}
```

### Model Name Patterns

When using `hashIdForModel()`, use the model name in lowercase:

- `'vessel'` → becomes `'vessel-id'`
- `'transaction'` → becomes `'transaction-id'`
- `'user'` → becomes `'user-id'`
- `'supplier'` → becomes `'supplier-id'`
- `'transactioncategory'` → becomes `'transactioncategory-id'`
- `'crewposition'` → becomes `'crewposition-id'`

### Common Mistakes to Avoid

❌ **DON'T:**
```php
// ❌ WRONG - Don't pass 'vessel-id', the method appends '-id' automatically
'vessel_id' => $this->hashIdForModel($this->vessel_id, 'vessel-id'), // This becomes 'vessel-id-id' (WRONG!)

// ❌ WRONG - Don't use numeric IDs in responses
'id' => $this->id,

// ❌ WRONG - Don't forget to hash foreign key IDs
'category_id' => $this->category_id,
```

✅ **DO:**
```php
// ✅ CORRECT - Pass only the model name
'vessel_id' => $this->hashIdForModel($this->vessel_id, 'vessel'), // This becomes 'vessel-id' (CORRECT!)

// ✅ CORRECT - Always hash IDs in responses
'id' => $this->hashId($this->id),

// ✅ CORRECT - Always hash foreign key IDs
'category_id' => $this->hashIdForModel($this->category_id, 'transactioncategory'),
```

## Complete Resource Examples

### TransactionResource
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'type_label' => $this->type_label,
            
            // Money values
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'vat_amount' => $this->vat_amount,
            'formatted_vat_amount' => $this->formatted_vat_amount,
            'total_amount' => $this->total_amount,
            'formatted_total_amount' => $this->formatted_total_amount,
            'currency' => $this->currency,
            'house_of_zeros' => $this->house_of_zeros,
            
            // Dates
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'formatted_transaction_date' => $this->transaction_date?->format('d/m/Y'),
            
            // Descriptions
            'description' => $this->description,
            'notes' => $this->notes,
            'reference' => $this->reference,
            
            // Status
            'status' => $this->status,
            'status_label' => $this->status_label,
            
            // Flags
            'is_recurring' => $this->is_recurring,
            
            // Relationships
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
            'category' => new TransactionCategoryResource($this->whenLoaded('category')),
            'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'crew_member' => new CrewMemberResource($this->whenLoaded('crewMember')),
            'vat_rate' => new VatRateResource($this->whenLoaded('vatRate')),
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            
            // Additional data for detailed views
            'detailed_info' => $this->when($request->include_details, [
                'transaction_month' => $this->transaction_month,
                'transaction_year' => $this->transaction_year,
                'recurring_transaction_id' => $this->recurring_transaction_id,
            ]),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### VesselResource
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VesselResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'registration_number' => $this->registration_number,
            'vessel_type' => $this->vessel_type,
            'vessel_type_label' => $this->vessel_type_label,
            'capacity' => $this->capacity,
            'year_built' => $this->year_built,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'notes' => $this->notes,
            
            // Relationships
            'crew_members' => CrewMemberResource::collection($this->whenLoaded('crewMembers')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            
            // Summary data
            'summary' => $this->whenLoaded('transactions', function () {
                return [
                    'total_transactions' => $this->transactions->count(),
                    'total_income' => $this->transactions->where('type', 'income')->sum('total_amount'),
                    'total_expense' => $this->transactions->where('type', 'expense')->sum('total_amount'),
                    'net_balance' => $this->transactions->where('type', 'income')->sum('total_amount') - 
                                   $this->transactions->where('type', 'expense')->sum('total_amount'),
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### CrewMemberResource
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrewMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document_number' => $this->document_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'formatted_date_of_birth' => $this->date_of_birth?->format('d/m/Y'),
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'formatted_hire_date' => $this->hire_date?->format('d/m/Y'),
            
            // Salary information
            'salary_amount' => $this->salary_amount,
            'formatted_salary_amount' => $this->formatted_salary_amount,
            'salary_currency' => $this->salary_currency,
            'payment_frequency' => $this->payment_frequency,
            'payment_frequency_label' => $this->getPaymentFrequencyLabel(),
            
            // Status
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            
            // Relationships
            'vessel' => new VesselResource($this->whenLoaded('vessel')),
            'position' => new CrewPositionResource($this->whenLoaded('position')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            
            // Additional data
            'notes' => $this->notes,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    
    private function getPaymentFrequencyLabel(): string
    {
        return match($this->payment_frequency) {
            'weekly' => 'Semanal',
            'biweekly' => 'Quinzenal',
            'monthly' => 'Mensal',
            default => $this->payment_frequency,
        };
    }
    
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'on_leave' => 'Em Licença',
            default => $this->status,
        };
    }
}
```

### BankAccountResource
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            
            // Money values
            'initial_balance' => $this->initial_balance,
            'formatted_initial_balance' => $this->formatMoney($this->initial_balance, $this->currency, $this->house_of_zeros),
            'current_balance' => $this->current_balance,
            'formatted_current_balance' => $this->formatMoney($this->current_balance, $this->currency, $this->house_of_zeros),
            'currency' => $this->currency,
            'house_of_zeros' => $this->house_of_zeros,
            
            // Status
            'status' => $this->status,
            'status_label' => $this->status === 'active' ? 'Ativa' : 'Inativa',
            
            // Additional data
            'notes' => $this->notes,
            
            // Relationships
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```
