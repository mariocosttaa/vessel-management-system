# Vessel-Specific Role-Based Access Control (RBAC) Patterns

This document outlines the sophisticated vessel-specific permission system for the Vessel Management System, including backend authorization patterns and frontend access control.

## 📋 Overview

The system uses a **vessel-specific role-based access control (RBAC)** approach with:

### User Types
- **`paid_system`**: Users who can create vessels and become vessel owners
- **`employee_of_vessel`**: Users who can only access vessels they're assigned to

### Vessel Roles
- **`normal`**: View-only access to vessel data
- **`moderator`**: Can view and edit basic vessel data
- **`supervisor`**: Can view, edit basic and advanced vessel data
- **`administrator`**: Full control over the vessel (owner-level permissions)

## 🎯 Permission Matrix

| Permission | Normal | Moderator | Supervisor | Administrator |
|------------|--------|-----------|------------|---------------|
| `view_vessel` | ✅ | ✅ | ✅ | ✅ |
| `edit_vessel_basic` | ❌ | ✅ | ✅ | ✅ |
| `edit_vessel_advanced` | ❌ | ❌ | ✅ | ✅ |
| `delete_vessel` | ❌ | ❌ | ❌ | ✅ |
| `manage_vessel_users` | ❌ | ❌ | ❌ | ✅ |

## 🗄️ Database Schema

### VesselRoleAccess Table
```sql
CREATE TABLE vessel_role_accesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE, -- normal, moderator, supervisor, administrator
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    permissions JSON NOT NULL, -- Array of permission strings
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### VesselUserRole Table (Pivot)
```sql
CREATE TABLE vessel_user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    vessel_id BIGINT UNSIGNED NOT NULL,
    vessel_role_access_id BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_role_access_id) REFERENCES vessel_role_accesses(id) ON DELETE CASCADE,
    UNIQUE KEY user_vessel_role_unique (user_id, vessel_id, vessel_role_access_id),
    INDEX idx_user_vessel (user_id, vessel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Users Table (Updated)
```sql
ALTER TABLE users ADD COLUMN user_type ENUM('paid_system', 'employee_of_vessel') DEFAULT 'employee_of_vessel' AFTER email;
```

## 🔒 Backend Authorization Patterns

### 1. Model Relationships

#### User Model
```php
// app/Models/User.php
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'user_type'
    ];

    /**
     * Get vessel user roles for this user
     */
    public function vesselUserRoles(): HasMany
    {
        return $this->hasMany(VesselUserRole::class);
    }

    /**
     * Get vessels through roles (active roles only)
     */
    public function vesselsThroughRoles(): BelongsToMany
    {
        return $this->belongsToMany(Vessel::class, 'vessel_user_roles')
                    ->withPivot(['vessel_role_access_id', 'is_active'])
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * Check if user can create vessels (paid_system users only)
     */
    public function canCreateVessels(): bool
    {
        return $this->user_type === 'paid_system';
    }

    /**
     * Check if user can edit a specific vessel
     */
    public function canEditVessel(int $vesselId): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasAnyPermission(['edit_vessel_basic', 'edit_vessel_advanced']);
    }

    /**
     * Check if user can delete a specific vessel
     */
    public function canDeleteVessel(int $vesselId): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasPermission('delete_vessel');
    }

    /**
     * Check if user can manage users for a specific vessel
     */
    public function canManageVesselUsers(int $vesselId): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasPermission('manage_vessel_users');
    }

    /**
     * Get vessel role access for a specific vessel
     */
    public function getVesselRoleAccess(int $vesselId): ?VesselRoleAccess
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        return $vesselUserRole?->vesselRoleAccess;
    }

    /**
     * Check if user has a specific permission for a vessel
     */
    public function hasVesselPermission(int $vesselId, string $permission): bool
    {
        $vesselUserRole = $this->vesselUserRoles()
            ->where('vessel_id', $vesselId)
            ->where('is_active', true)
            ->with('vesselRoleAccess')
            ->first();

        if (!$vesselUserRole) {
            return false;
        }

        return $vesselUserRole->hasPermission($permission);
    }
}
```

#### VesselUserRole Model (Pivot)
```php
// app/Models/VesselUserRole.php
class VesselUserRole extends Pivot
{
    use HasFactory;

    protected $table = 'vessel_user_roles';

    protected $fillable = [
        'vessel_id', 'user_id', 'vessel_role_access_id', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function vesselRoleAccess(): BelongsTo
    {
        return $this->belongsTo(VesselRoleAccess::class);
    }

    /**
     * Check if the associated role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->vesselRoleAccess?->hasPermission($permission) ?? false;
    }

    /**
     * Check if the associated role has any of the specified permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->vesselRoleAccess?->hasAnyPermission($permissions) ?? false;
    }
}
```

#### VesselRoleAccess Model
```php
// app/Models/VesselRoleAccess.php
class VesselRoleAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'display_name', 'description', 'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function vesselUserRoles(): HasMany
    {
        return $this->hasMany(VesselUserRole::class);
    }

    /**
     * Check if this role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Check if this role has any of the specified permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return count(array_intersect($permissions, $this->permissions)) > 0;
    }
}
```

### 2. Request Authorization

All FormRequest classes implement vessel-specific authorization:

```php
// app/Http/Requests/StoreVesselRequest.php
public function authorize(): bool
{
    return $this->user()?->canCreateVessels() ?? false;
}

// app/Http/Requests/UpdateVesselRequest.php
public function authorize(): bool
{
    $vessel = $this->route('vessel');
    return $this->user()?->canEditVessel($vessel->id) ?? false;
}

// app/Http/Requests/DeleteVesselRequest.php
public function authorize(): bool
{
    $vessel = $this->route('vessel');
    return $this->user()?->canDeleteVessel($vessel->id) ?? false;
}
```

### 3. Controller Authorization

Controllers implement vessel-specific permission checks:

```php
// app/Http/Controllers/VesselController.php
public function store(StoreVesselRequest $request)
{
    $user = auth()->user();

    // Check if user can create vessels
    if (!$user->canCreateVessels()) {
        abort(403, 'You do not have permission to create vessels.');
    }

    try {
        $vessel = Vessel::create($request->validated());

        // Assign the vessel to the current user as administrator (owner)
        $adminRoleAccess = VesselRoleAccess::where('name', 'administrator')->first();

        if ($adminRoleAccess) {
            VesselUserRole::create([
                'vessel_id' => $vessel->id,
                'user_id' => $user->id,
                'vessel_role_access_id' => $adminRoleAccess->id,
                'is_active' => true,
            ]);
        }

        // Set the vessel owner
        $vessel->update(['owner_id' => $user->id]);

        return redirect()
            ->route('panel.index')
            ->with('success', "Vessel '{$vessel->name}' has been created successfully.");
    } catch (\Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Failed to create vessel. Please try again.');
    }
}

public function update(UpdateVesselRequest $request, Vessel $vessel)
{
    // Authorization handled in UpdateVesselRequest
    $vessel->update($request->validated());
    
    return redirect()
        ->route('panel.index')
        ->with('success', "Vessel '{$vessel->name}' has been updated successfully.");
}

public function destroy(Vessel $vessel)
{
    $user = auth()->user();
    
    if (!$user->canDeleteVessel($vessel->id)) {
        abort(403, 'You do not have permission to delete this vessel.');
    }
    
    // Additional business logic checks
    if ($vessel->crewMembers()->count() > 0) {
        return back()->with('error', 'Cannot delete vessel with crew members assigned.');
    }
    
    $vessel->delete();
    
    return redirect()
        ->route('panel.index')
        ->with('success', "Vessel '{$vessel->name}' has been deleted successfully.");
}
```

### 4. Vessel Selector Controller

The vessel selector shows only vessels the user has access to:

```php
// app/Http/Controllers/VesselSelectorController.php
public function index(Request $request)
{
    $user = $request->user();

    $vessels = $user->vesselsThroughRoles()
        ->with(['owner', 'crewMembers', 'transactions'])
        ->get()
        ->map(function ($vessel) use ($user) {
            // Get vessel-specific permissions
            $canEdit = $user->canEditVessel($vessel->id);
            $canDelete = $user->canDeleteVessel($vessel->id);
            $canManageUsers = $user->canManageVesselUsers($vessel->id);

            // Get role access info
            $roleAccess = $user->getVesselRoleAccess($vessel->id);

            return [
                'id' => $vessel->id,
                'name' => $vessel->name,
                'registration_number' => $vessel->registration_number,
                'vessel_type' => $vessel->vessel_type,
                'status' => $vessel->status,
                'status_label' => $vessel->status_label,
                'role_access' => $roleAccess ? [
                    'name' => $roleAccess->name,
                    'display_name' => $roleAccess->display_name,
                ] : null,
                'permissions' => [
                    'can_edit' => $canEdit,
                    'can_delete' => $canDelete,
                    'can_manage_users' => $canManageUsers,
                ],
                'crew_count' => $vessel->crewMembers()->count(),
                'transaction_count' => $vessel->transactions()->count(),
            ];
        });

    return Inertia::render('VesselSelector', [
        'vessels' => $vessels,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
        'permissions' => [
            'can_create_vessels' => $user->canCreateVessels(),
        ],
    ]);
}
```

## 🎨 Frontend Authorization Patterns

### 1. Vessel Selector Component

The frontend displays vessel-specific permissions:

```vue
<!-- resources/js/pages/VesselSelector.vue -->
<template>
  <div>
    <!-- Create button only for paid_system users -->
    <button
      v-if="permissions.can_create_vessels"
      @click="createVessel"
      class="btn-primary"
    >
      <Icon name="plus" class="w-4 h-4 mr-2" />
      Create New Vessel
    </button>

    <!-- Vessel cards with role-specific actions -->
    <div v-for="vessel in vessels" :key="vessel.id" class="vessel-card">
      <div class="vessel-info">
        <h3>{{ vessel.name }}</h3>
        <p>{{ vessel.registration_number }}</p>
        <span class="role-badge">{{ vessel.role_access?.display_name }}</span>
      </div>

      <div class="vessel-actions">
        <!-- Edit button based on permissions -->
        <button
          v-if="vessel.permissions.can_edit"
          @click="editVessel(vessel.id)"
          class="btn-secondary"
        >
          <Icon name="edit" class="w-4 h-4" />
        </button>

        <!-- Delete button based on permissions -->
        <button
          v-if="vessel.permissions.can_delete"
          @click="deleteVessel(vessel.id, vessel.name)"
          class="btn-destructive"
        >
          <Icon name="trash-2" class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface Vessel {
  id: number
  name: string
  registration_number: string
  vessel_type: string
  status: string
  status_label: string
  role_access?: {
    name: string
    display_name: string
  } | null
  permissions: {
    can_edit: boolean
    can_delete: boolean
    can_manage_users: boolean
  }
  crew_count: number
  transaction_count: number
}

const page = usePage()
const vessels = computed(() => page.props.vessels as Vessel[])
const permissions = computed(() => page.props.permissions as { can_create_vessels: boolean })

const createVessel = () => {
  // Navigate to vessel creation
}

const editVessel = (vesselId: number) => {
  // Navigate to vessel edit
}

const deleteVessel = (vesselId: number, vesselName: string) => {
  // Show confirmation dialog and delete
}
</script>
```

### 2. Permission-Based Components

Create components that respect vessel-specific permissions:

```vue
<!-- resources/js/components/VesselPermissionGate.vue -->
<template>
  <div v-if="hasAccess">
    <slot />
  </div>
  <div v-else-if="fallback" class="text-muted-foreground">
    {{ fallback }}
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface Props {
  vesselId: number
  permission: string
  fallback?: string
}

const props = defineProps<Props>()
const page = usePage()

const hasAccess = computed(() => {
  const vessel = page.props.vessels?.find((v: any) => v.id === props.vesselId)
  if (!vessel) return false
  
  return vessel.permissions[props.permission] === true
})
</script>
```

### 3. Usage in Templates

Use vessel-specific permission gates:

```vue
<!-- resources/js/Pages/Vessels/Show.vue -->
<template>
  <div>
    <h1>{{ vessel.name }}</h1>
    
    <!-- Only show edit button if user can edit this vessel -->
    <VesselPermissionGate 
      :vessel-id="vessel.id" 
      permission="can_edit"
      fallback="You don't have permission to edit this vessel"
    >
      <Button @click="editVessel">
        <Icon name="edit" class="w-4 h-4 mr-2" />
        Edit Vessel
      </Button>
    </VesselPermissionGate>

    <!-- Only show delete button if user can delete this vessel -->
    <VesselPermissionGate 
      :vessel-id="vessel.id" 
      permission="can_delete"
    >
      <Button variant="destructive" @click="deleteVessel">
        <Icon name="trash-2" class="w-4 h-4 mr-2" />
        Delete Vessel
      </Button>
    </VesselPermissionGate>
  </div>
</template>
```

## 🌱 Database Seeding

### VesselRoleAccess Seeder

```php
// database/seeders/VesselRoleAccessSeeder.php
class VesselRoleAccessSeeder extends Seeder
{
    public function run(): void
    {
        VesselRoleAccess::firstOrCreate(
            ['name' => 'normal'],
            [
                'display_name' => 'Normal User',
                'description' => 'Can view vessel data.',
                'permissions' => ['view_vessel'],
            ]
        );

        VesselRoleAccess::firstOrCreate(
            ['name' => 'moderator'],
            [
                'display_name' => 'Moderator',
                'description' => 'Can view and edit basic vessel data.',
                'permissions' => ['view_vessel', 'edit_vessel_basic'],
            ]
        );

        VesselRoleAccess::firstOrCreate(
            ['name' => 'supervisor'],
            [
                'display_name' => 'Supervisor',
                'description' => 'Can view, edit basic and advanced vessel data.',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'edit_vessel_advanced'],
            ]
        );

        VesselRoleAccess::firstOrCreate(
            ['name' => 'administrator'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full control over the vessel, including deleting and managing users.',
                'permissions' => ['view_vessel', 'edit_vessel_basic', 'edit_vessel_advanced', 'delete_vessel', 'manage_vessel_users'],
            ]
        );
    }
}
```

## 🛡️ Security Best Practices

### 1. Backend Security
- **Always validate on backend**: Frontend permissions are for UX only
- **Use vessel-specific authorization**: Check permissions per vessel
- **Implement user type restrictions**: Only `paid_system` users can create vessels
- **Validate business rules**: Additional checks beyond roles
- **Log access attempts**: Audit trail for security

### 2. Frontend Security
- **Hide sensitive UI elements**: Better user experience
- **Show vessel-specific permissions**: Users see only what they can access
- **Disable actions gracefully**: Show why action is unavailable
- **Provide fallback content**: Alternative actions for limited users

### 3. Permission Granularity
- **Vessel-specific permissions**: Each vessel has independent permissions
- **Role-based permissions**: Different roles have different capabilities
- **User type restrictions**: `paid_system` vs `employee_of_vessel`
- **Action-based permissions**: `view_vessel`, `edit_vessel_basic`, etc.

## 📝 Implementation Checklist

### Backend Implementation
- [ ] Create `VesselRoleAccess` model and migration
- [ ] Create `VesselUserRole` model and migration
- [ ] Add `user_type` field to users table
- [ ] Update `User` model with vessel-specific permission methods
- [ ] Update `Vessel` model with new relationships
- [ ] Update all FormRequest classes with vessel-specific authorization
- [ ] Update controllers to use new permission system
- [ ] Create `VesselRoleAccessSeeder`
- [ ] Update `VesselSelectorController` to use `vesselsThroughRoles()`

### Frontend Implementation
- [ ] Update `VesselSelector.vue` to show vessel-specific permissions
- [ ] Create `VesselPermissionGate` component
- [ ] Update all vessel-related pages with permission checks
- [ ] Modify navigation based on user type
- [ ] Update table actions based on vessel permissions
- [ ] Add permission-based button states

### Testing
- [ ] Test with `paid_system` users (can create vessels)
- [ ] Test with `employee_of_vessel` users (cannot create vessels)
- [ ] Test all role combinations (normal, moderator, supervisor, administrator)
- [ ] Verify vessel isolation (users only see their vessels)
- [ ] Test permission inheritance (administrator gets all permissions)
- [ ] Validate error handling and fallbacks

## 🔄 Permission Flow

1. **User Login**: System checks `user_type` (`paid_system` or `employee_of_vessel`)
2. **Vessel Access**: System loads vessels through `vesselsThroughRoles()` relationship
3. **Permission Check**: For each vessel, system checks `VesselUserRole` and `VesselRoleAccess`
4. **Frontend Display**: UI shows/hides actions based on vessel-specific permissions
5. **Action Authorization**: Backend validates permissions before executing actions

## 🎯 Key Benefits

- **Vessel Isolation**: Users only see vessels they have access to
- **Granular Permissions**: Different roles for different vessels
- **User Type Restrictions**: Only paid users can create vessels
- **Automatic Role Assignment**: Vessel creators become administrators
- **Scalable System**: Easy to add new roles and permissions
- **Security First**: Backend validation with frontend UX improvements

This sophisticated vessel-specific RBAC system ensures secure, granular access control while providing an intuitive user experience.
