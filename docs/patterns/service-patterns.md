# Service Patterns

## Overview

Services encapsulate complex business logic that doesn't belong in controllers or models. They provide reusable, testable, and maintainable code for operations that involve multiple models or complex workflows.

## When to Use Services

Use services when:
- ✅ Operations involve multiple models
- ✅ Complex business logic that spans multiple steps
- ✅ Operations that need transaction safety
- ✅ Reusable logic that might be called from multiple places
- ✅ Operations that need to be easily testable

## Service Structure

### Basic Service Pattern

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VesselService
{
    /**
     * Create a new vessel with owner and initial administrator role.
     *
     * @param \App\Models\User $owner The user who will own the vessel (must be paid_system)
     * @param array $vesselData Vessel configuration data
     * @return \App\Models\Vessel The created vessel
     * @throws \Exception If user doesn't have tenant role or creation fails
     */
    public function createVessel(\App\Models\User $owner, array $vesselData): \App\Models\Vessel
    {
        // Validate prerequisites
        if ($owner->user_type !== 'paid_system') {
            throw new \Exception('User must have tenant role (paid_system) to create vessels.');
        }

        return DB::transaction(function () use ($owner, $vesselData) {
            // Perform all operations within a transaction
            // ...
            
            return $vessel;
        });
    }
}
```

## VesselService Example

### Complete Implementation

```php
<?php

namespace App\Services;

use App\Actions\AuditLogAction;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselRoleAccess;
use App\Models\VesselSetting;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VesselService
{
    /**
     * Create a new vessel with owner and initial administrator role.
     *
     * @param User $owner The user who will own the vessel (must be paid_system)
     * @param array $vesselData Vessel configuration data
     * @return Vessel The created vessel
     * @throws \Exception If user doesn't have tenant role or creation fails
     */
    public function createVessel(User $owner, array $vesselData): Vessel
    {
        // Validate that owner has tenant role (paid_system)
        if ($owner->user_type !== 'paid_system') {
            throw new \Exception('User must have tenant role (paid_system) to create vessels.');
        }

        return DB::transaction(function () use ($owner, $vesselData) {
            // Create the vessel
            $vessel = Vessel::create([
                'name'                => $vesselData['name'],
                'registration_number' => $vesselData['registration_number'],
                'vessel_type'         => $vesselData['vessel_type'],
                'capacity'            => $vesselData['capacity'] ?? null,
                'year_built'          => $vesselData['year_built'] ?? null,
                'status'              => $vesselData['status'],
                'notes'               => $vesselData['notes'] ?? null,
                'country_code'        => $vesselData['country_code'],
                'currency_code'       => $vesselData['currency_code'],
                'owner_id'            => $owner->id,
            ]);

            // Get or create the administrator role access
            $adminRoleAccess = VesselRoleAccess::where('name', 'administrator')->first();

            if (! $adminRoleAccess) {
                $adminRoleAccess = VesselRoleAccess::create([
                    'name'         => 'administrator',
                    'display_name' => 'Administrator',
                    'description'  => 'Full access to vessel including deletion and user management',
                    'permissions' => [
                        'view_vessel',
                        'edit_vessel_basic',
                        'edit_vessel_advanced',
                        'manage_crew',
                        'delete_vessel',
                        'manage_vessel_users',
                    ],
                    'is_active'    => true,
                ]);
            }

            // Create vessel user role with administrator access (mandatory)
            VesselUserRole::create([
                'vessel_id'             => $vessel->id,
                'user_id'               => $owner->id,
                'vessel_role_access_id' => $adminRoleAccess->id,
                'is_active'             => true,
            ]);

            // Also maintain the old vessel_users table for backward compatibility
            VesselUser::create([
                'vessel_id' => $vessel->id,
                'user_id'   => $owner->id,
                'role'      => 'owner',
                'is_active' => true,
            ]);

            // Create vessel setting with country, currency, and VAT profile
            if (isset($vesselData['vat_profile_id'])) {
                VesselSetting::create([
                    'vessel_id'     => $vessel->id,
                    'country_code'  => $vesselData['country_code'],
                    'currency_code' => $vesselData['currency_code'],
                    'vat_profile_id' => $vesselData['vat_profile_id'],
                ]);
            }

            // Log the create action
            AuditLogAction::logCreate(
                $vessel,
                'Vessel',
                $vessel->name,
                null // Vessels are not vessel-scoped (they're global entities)
            );

            Log::info('Vessel created successfully', [
                'vessel_id' => $vessel->id,
                'vessel_name' => $vessel->name,
                'owner_id' => $owner->id,
                'owner_email' => $owner->email,
            ]);

            return $vessel;
        });
    }
}
```

### Usage in Controller

```php
// app/Http/Controllers/VesselController.php
public function store(StoreVesselRequest $request)
{
    $user = auth()->user();

    // Check if user can create vessels (must have tenant role - paid_system)
    if (! $user->canCreateVessels()) {
        abort(403, 'You do not have permission to create vessels. You must have tenant role (paid_system).');
    }

    // Validate that user has tenant role (mandatory)
    if ($user->user_type !== 'paid_system') {
        abort(403, 'You must have tenant role (paid_system) to create vessels.');
    }

    try {
        $vesselService = new VesselService();
        
        $vessel = $vesselService->createVessel($user, [
            'name'                => $request->name,
            'registration_number' => $request->registration_number,
            'vessel_type'         => $request->vessel_type,
            'capacity'            => $request->capacity,
            'year_built'          => $request->year_built,
            'status'              => $request->status,
            'notes'               => $request->notes,
            'country_code'        => $request->country_code,
            'currency_code'       => $request->currency_code,
            'vat_profile_id'      => $request->vat_profile_id,
        ]);

        return redirect()
            ->route('panel.index')
            ->with('success', "Vessel '{$vessel->name}' has been created successfully.");
    } catch (\Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Failed to create vessel: ' . $e->getMessage());
    }
}
```

## Key Features

### 1. Tenant Role Validation

The service validates that the user has the `paid_system` user type (tenant role) before allowing vessel creation:

```php
if ($owner->user_type !== 'paid_system') {
    throw new \Exception('User must have tenant role (paid_system) to create vessels.');
}
```

### 2. Transaction Safety

All operations are wrapped in a database transaction to ensure atomicity:

```php
return DB::transaction(function () use ($owner, $vesselData) {
    // All operations here are atomic
    // If any fails, all are rolled back
});
```

### 3. Mandatory Administrator Role

The service automatically assigns the administrator role to the vessel owner:

```php
// Create vessel user role with administrator access (mandatory)
VesselUserRole::create([
    'vessel_id'             => $vessel->id,
    'user_id'               => $owner->id,
    'vessel_role_access_id' => $adminRoleAccess->id,
    'is_active'             => true,
]);
```

### 4. Complete Vessel Setup

The service handles all aspects of vessel creation:
- ✅ Vessel record creation
- ✅ Owner assignment
- ✅ Administrator role assignment
- ✅ Vessel settings creation
- ✅ Backward compatibility (VesselUser table)
- ✅ Audit logging

## Benefits

1. **Centralized Logic**: All vessel creation logic in one place
2. **Reusability**: Can be called from controllers, commands, or other services
3. **Testability**: Easy to unit test service methods
4. **Maintainability**: Changes to vessel creation logic only need to be made in one place
5. **Transaction Safety**: All operations are atomic
6. **Consistency**: Ensures vessels are always created with the same structure

## Best Practices

1. ✅ Always validate prerequisites before operations
2. ✅ Use database transactions for multi-step operations
3. ✅ Throw descriptive exceptions for validation failures
4. ✅ Log important operations for debugging
5. ✅ Keep services focused on single responsibilities
6. ✅ Use type hints for better IDE support
7. ✅ Document method parameters and return types
8. ✅ Handle errors gracefully and provide meaningful messages

## Related Patterns

- **Controller Patterns**: Controllers use services for complex operations
- **Model Patterns**: Services interact with models
- **Permission Patterns**: Services validate user permissions
- **Multi-Tenant Patterns**: Services respect vessel-based architecture

