# Multi-Tenant Vessel-Based System Patterns

## Overview

The Vessel Management System implements a sophisticated multi-tenant architecture where all data is scoped to specific vessels. Users can have different roles for different vessels, ensuring complete data isolation and flexible permission management.

## Core Multi-Tenant Principles

### 1. Vessel-Scoped Data
All entities must include `vessel_id` and be filtered by the current vessel context:

```php
// ✅ Correct - Always filter by vessel
public function index(Request $request)
{
    $vesselId = $this->getCurrentVesselId();
    $query = Model::query()->where('vessel_id', $vesselId);
    // ... rest of query
}

// ❌ Incorrect - No vessel filtering
public function index(Request $request)
{
    $query = Model::query(); // Missing vessel filter
    // ... rest of query
}
```

### 2. Tenant-Based Authorization
All authorization checks must be vessel-specific:

```php
// ✅ Correct - Vessel-specific authorization
public function authorize(): bool
{
    $vesselId = $this->route('vessel');
    return $this->user()?->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor']) ?? false;
}

// ❌ Incorrect - Global authorization
public function authorize(): bool
{
    return $this->user()?->hasAnyRole(['admin', 'manager']) ?? false;
}
```

### 3. Automatic Vessel Injection
Controllers must automatically inject `vessel_id` into created records:

```php
// ✅ Correct - Inject vessel_id
public function store(StoreRequest $request)
{
    $vesselId = $this->getCurrentVesselId();
    $data = $request->validated();
    $data['vessel_id'] = $vesselId; // Always inject vessel_id
    $model = Model::create($data);
}

// ❌ Incorrect - Missing vessel_id injection
public function store(StoreRequest $request)
{
    $model = Model::create($request->validated()); // Missing vessel_id
}
```

## Database Schema Patterns

### Required Vessel Columns
All tenant entities must include:

```sql
-- Required for all tenant entities
vessel_id BIGINT UNSIGNED NOT NULL,
FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
INDEX idx_vessel_id (vessel_id)
```

### Vessel-Specific Unique Constraints
Use composite unique constraints for vessel-specific uniqueness:

```sql
-- ✅ Correct - Vessel-specific unique constraint
UNIQUE KEY unique_name_per_vessel (vessel_id, name)

-- ❌ Incorrect - Global unique constraint
UNIQUE KEY unique_name (name)
```

### Example Entity Schema
```sql
CREATE TABLE crew_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id BIGINT UNSIGNED NOT NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    salary_amount INT NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
    house_of_zeros TINYINT NOT NULL DEFAULT 2,
    status ENUM('active', 'inactive', 'on_leave') NOT NULL DEFAULT 'active',
    hire_date DATE,
    notes TEXT,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES crew_positions(id) ON DELETE RESTRICT,
    INDEX idx_vessel_id (vessel_id),
    INDEX idx_position_id (position_id),
    INDEX idx_status (status),
    INDEX idx_hire_date (hire_date)
);
```

## Model Patterns

### Base Tenant Model
All tenant models should extend a base class:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class TenantModel extends Model
{
    /**
     * Get the vessel that owns the model.
     */
    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    /**
     * Scope a query to only include records for a specific vessel.
     */
    public function scopeForVessel($query, $vesselId)
    {
        return $query->where('vessel_id', $vesselId);
    }

    /**
     * Boot method to automatically set vessel_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->vessel_id && app()->has('current.vessel.id')) {
                $model->vessel_id = app('current.vessel.id');
            }
        });
    }
}
```

### Tenant Model Example
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewMember extends TenantModel
{
    protected $fillable = [
        'vessel_id',
        'position_id',
        'name',
        'email',
        'phone',
        'salary_amount',
        'currency',
        'house_of_zeros',
        'status',
        'hire_date',
        'notes',
    ];

    protected $casts = [
        'salary_amount' => 'integer',
        'house_of_zeros' => 'integer',
        'hire_date' => 'date',
    ];

    /**
     * Get the position that belongs to the crew member.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(CrewPosition::class);
    }

    /**
     * Scope a query to only include active crew members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include crew members for a specific position.
     */
    public function scopeForPosition($query, $positionId)
    {
        return $query->where('position_id', $positionId);
    }
}
```

## Controller Patterns

### Base Tenant Controller
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

abstract class TenantController extends Controller
{
    /**
     * Get the current vessel ID from the route.
     */
    protected function getCurrentVesselId(): int
    {
        return (int) request()->route('vessel');
    }

    /**
     * Get the current vessel model.
     */
    protected function getCurrentVessel()
    {
        return Vessel::findOrFail($this->getCurrentVesselId());
    }

    /**
     * Verify that the user has access to the current vessel.
     */
    protected function verifyVesselAccess(): void
    {
        $vesselId = $this->getCurrentVesselId();
        $user = auth()->user();

        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }
    }

    /**
     * Verify that the user has a specific role for the current vessel.
     */
    protected function verifyVesselRole(array $roles): void
    {
        $vesselId = $this->getCurrentVesselId();
        $user = auth()->user();

        if (!$user || !$user->hasAnyRoleForVessel($vesselId, $roles)) {
            abort(403, 'You do not have the required role for this vessel.');
        }
    }
}
```

### Tenant Controller Example
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCrewMemberRequest;
use App\Http\Requests\UpdateCrewMemberRequest;
use App\Http\Resources\CrewMemberResource;
use App\Models\CrewMember;
use App\Models\CrewPosition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrewMemberController extends TenantController
{
    /**
     * Display a listing of crew members for the current vessel.
     */
    public function index(Request $request)
    {
        $this->verifyVesselAccess();
        $vesselId = $this->getCurrentVesselId();
        
        $query = CrewMember::query()
            ->forVessel($vesselId)
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
            $query->forPosition($request->position_id);
        }

        // Apply sorting
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $query->orderBy($sort, $direction);

        $crewMembers = $query->paginate(15);

        return Inertia::render('CrewMembers/Index', [
            'crewMembers' => CrewMemberResource::collection($crewMembers),
            'filters' => $request->only(['search', 'status', 'position_id', 'sort', 'direction']),
            'positions' => CrewPosition::forVessel($vesselId)->select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a newly created crew member for the current vessel.
     */
    public function store(StoreCrewMemberRequest $request)
    {
        $this->verifyVesselRole(['Administrator', 'Supervisor']);
        
        try {
            $vesselId = $this->getCurrentVesselId();
            $data = $request->validated();
            $data['vessel_id'] = $vesselId; // Always inject vessel_id
            
            $crewMember = CrewMember::create($data);
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
        $this->verifyVesselRole(['Administrator', 'Supervisor']);
        
        try {
            // Verify crew member belongs to current vessel
            $vesselId = $this->getCurrentVesselId();
            if ($crewMember->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to crew member.');
            }

            $crewMember->update($request->validated());
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
        $this->verifyVesselRole(['Administrator', 'Supervisor']);
        
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
```

## Request Patterns

### Tenant Request Authorization
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\CrewPosition;

class StoreCrewMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter
        $vesselId = $this->route('vessel');
        
        // Check if user has admin or supervisor role for this specific vessel
        return $this->user()?->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vesselId = $this->route('vessel');
        
        return [
            'position_id' => [
                'required', 
                'integer', 
                Rule::exists(CrewPosition::class, 'id')->where('vessel_id', $vesselId)
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'salary_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'house_of_zeros' => ['required', 'integer', 'min:0', 'max:4'],
            'status' => ['required', 'in:active,inactive,on_leave'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'position_id.required' => 'Please select a position.',
            'position_id.exists' => 'The selected position is invalid for this vessel.',
            'name.required' => 'The crew member name is required.',
            'salary_amount.required' => 'The salary amount is required.',
            'salary_amount.min' => 'The salary amount must be greater than or equal to zero.',
            'currency.required' => 'The currency is required.',
            'status.required' => 'Please select a status.',
            'hire_date.before_or_equal' => 'The hire date cannot be in the future.',
        ];
    }
}
```

## Frontend Patterns

### Vessel Layout Usage
All vessel-specific pages must use `VesselLayout`:

```vue
<template>
  <VesselLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Page content -->
    </div>
  </VesselLayout>
</template>

<script setup lang="ts">
import VesselLayout from '@/layouts/VesselLayout.vue'

// Breadcrumbs with vessel context
const breadcrumbs = [
  { label: 'Dashboard', href: route('dashboard', { vessel: getCurrentVesselId() }) },
  { label: 'Crew Members', href: route('crew-members.index', { vessel: getCurrentVesselId() }) },
]
</script>
```

### Vessel-Specific Route Generation
```typescript
// ✅ Correct - Always include vessel parameter
const getCurrentVesselId = (): number => {
  const path = window.location.pathname
  const vesselMatch = path.match(/\/panel\/(\d+)\//)
  return vesselMatch ? parseInt(vesselMatch[1]) : 1
}

// Usage in components
const createUrl = computed(() => 
  crewMembers.store.url({ vessel: getCurrentVesselId() })
)

const editUrl = (crewMember: CrewMember) => 
  crewMembers.update.url({ vessel: getCurrentVesselId(), crewMember: crewMember.id })
```

### Vessel-Specific Permissions
```typescript
// composables/usePermissions.ts
export function usePermissions() {
  const page = usePage<PageProps>()
  
  const user = computed(() => page.props.auth.user)
  const currentVesselRole = computed(() => user.value?.vessel_role || 'Normal User')
  
  const hasRole = (role: string): boolean => {
    return currentVesselRole.value === role
  }
  
  const hasAnyRole = (roleList: string[]): boolean => {
    return roleList.includes(currentVesselRole.value)
  }
  
  const canCreate = (resource: string): boolean => {
    return hasAnyRole(['Administrator', 'Supervisor'])
  }
  
  const canEdit = (resource: string): boolean => {
    return hasAnyRole(['Administrator', 'Supervisor', 'Moderator'])
  }
  
  const canDelete = (resource: string): boolean => {
    return hasAnyRole(['Administrator', 'Supervisor'])
  }
  
  return {
    currentVesselRole,
    hasRole,
    hasAnyRole,
    canCreate,
    canEdit,
    canDelete,
    isAdministrator: computed(() => hasRole('Administrator')),
    isSupervisor: computed(() => hasRole('Supervisor')),
    isModerator: computed(() => hasRole('Moderator')),
    isNormalUser: computed(() => hasRole('Normal User')),
  }
}
```

## Route Patterns

### Vessel-Scoped Routes
All vessel-specific routes must include the vessel parameter:

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->prefix('panel/{vessel}')->group(function () {
    // Crew Members
    Route::get('crew-members', [CrewMemberController::class, 'index'])->name('crew-members.index');
    Route::post('crew-members', [CrewMemberController::class, 'store'])->name('crew-members.store');
    Route::get('crew-members/{crewMember}', [CrewMemberController::class, 'show'])->name('crew-members.show');
    Route::put('crew-members/{crewMember}', [CrewMemberController::class, 'update'])->name('crew-members.update');
    Route::delete('crew-members/{crewMember}', [CrewMemberController::class, 'destroy'])->name('crew-members.destroy');
    
    // Suppliers
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    
    // Bank Accounts
    Route::get('bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::get('bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])->name('bank-accounts.show');
    Route::put('bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
});
```

## Middleware Patterns

### Vessel Access Middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVesselAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vesselId = $request->route('vessel');
        $user = $request->user();

        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        return $next($request);
    }
}
```

### Vessel Role Middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVesselRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $vesselId = $request->route('vessel');
        $user = $request->user();

        if (!$user || !$user->hasAnyRoleForVessel($vesselId, $roles)) {
            abort(403, 'You do not have the required role for this vessel.');
        }

        return $next($request);
    }
}
```

## Best Practices

### 1. Always Filter by Vessel
- Every query must include vessel filtering
- Never show data from other vessels
- Always verify vessel ownership before operations

### 2. Consistent Authorization
- Use vessel-specific role checks
- Implement proper permission gates
- Never rely on global permissions

### 3. Automatic Vessel Injection
- Always inject `vessel_id` in store methods
- Verify vessel ownership in update/delete methods
- Use route model binding with vessel scoping

### 4. Frontend Consistency
- Use `VesselLayout` for all vessel pages
- Include vessel parameter in all routes
- Implement vessel-specific permissions

### 5. Database Integrity
- Use foreign key constraints with CASCADE
- Implement vessel-specific unique constraints
- Always include vessel_id indexes

### 6. Error Handling
- Provide clear vessel-specific error messages
- Log unauthorized access attempts
- Handle vessel access gracefully

## Common Mistakes to Avoid

❌ **Don't forget vessel filtering**: Always filter queries by vessel_id
❌ **Don't use global permissions**: Always use vessel-specific role checks
❌ **Don't skip vessel injection**: Always inject vessel_id in store methods
❌ **Don't use AppLayout**: Use VesselLayout for vessel-specific pages
❌ **Don't forget vessel parameter**: Always include vessel in route generation
❌ **Don't ignore vessel ownership**: Always verify ownership before operations
❌ **Don't use global unique constraints**: Use vessel-specific unique constraints
❌ **Don't skip vessel verification**: Always verify vessel access in middleware

## Testing Patterns

### Vessel-Scoped Tests
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vessel;
use App\Models\CrewMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrewMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_crew_members_from_their_vessel()
    {
        $vessel1 = Vessel::factory()->create();
        $vessel2 = Vessel::factory()->create();
        
        $user = User::factory()->create();
        $user->vessels()->attach($vessel1->id, ['role' => 'Administrator']);
        
        $crewMember1 = CrewMember::factory()->create(['vessel_id' => $vessel1->id]);
        $crewMember2 = CrewMember::factory()->create(['vessel_id' => $vessel2->id]);
        
        $response = $this->actingAs($user)
            ->get(route('crew-members.index', ['vessel' => $vessel1->id]));
        
        $response->assertStatus(200);
        $response->assertSee($crewMember1->name);
        $response->assertDontSee($crewMember2->name);
    }

    public function test_user_cannot_access_crew_members_from_other_vessel()
    {
        $vessel1 = Vessel::factory()->create();
        $vessel2 = Vessel::factory()->create();
        
        $user = User::factory()->create();
        $user->vessels()->attach($vessel1->id, ['role' => 'Administrator']);
        
        $crewMember = CrewMember::factory()->create(['vessel_id' => $vessel2->id]);
        
        $response = $this->actingAs($user)
            ->get(route('crew-members.show', ['vessel' => $vessel2->id, 'crewMember' => $crewMember->id]));
        
        $response->assertStatus(403);
    }
}
```

---

This multi-tenant pattern ensures complete data isolation, flexible permission management, and scalable architecture for the Vessel Management System.
