# Vessel-Specific Role-Based Access Control (RBAC) Overview

## 🎯 System Overview

The Vessel Management System implements a sophisticated **vessel-specific role-based access control (RBAC)** system that provides granular permissions at the vessel level. This system ensures that users only have access to vessels they're assigned to and can only perform actions based on their specific role for each vessel.

## 🏗️ Architecture

### Core Components

1. **User Types**: Distinguishes between system users and vessel employees
2. **Vessel Roles**: Defines permission levels for each vessel
3. **Vessel-User Relationships**: Links users to vessels with specific roles
4. **Permission System**: Granular permissions for different actions

### Database Structure

```
users (user_type: paid_system | employee_of_vessel)
  ↓
vessel_user_roles (pivot table)
  ↓
vessel_role_accesses (role definitions with permissions)
  ↓
vessels (owner_id references users)
```

## 👥 User Types

### `paid_system`
- **Purpose**: Users who pay for the system and can create vessels
- **Capabilities**:
  - ✅ Can create new vessels
  - ✅ Automatically becomes vessel owner/administrator
  - ✅ Can manage vessel users
  - ✅ Full control over created vessels

### `employee_of_vessel`
- **Purpose**: Users who work for specific vessels
- **Capabilities**:
  - ❌ Cannot create vessels
  - ✅ Can only access assigned vessels
  - ✅ Permissions based on assigned role
  - ✅ Limited to vessel-specific operations

## 🎭 Vessel Roles

### `normal` (View-Only)
- **Permissions**: `view_vessel`
- **Capabilities**:
  - ✅ View vessel information
  - ❌ Cannot edit or delete
  - ❌ Cannot manage users

### `moderator` (Basic Edit)
- **Permissions**: `view_vessel`, `edit_vessel_basic`
- **Capabilities**:
  - ✅ View vessel information
  - ✅ Edit basic vessel data
  - ❌ Cannot edit advanced settings
  - ❌ Cannot delete vessel
  - ❌ Cannot manage users

### `supervisor` (Advanced Edit)
- **Permissions**: `view_vessel`, `edit_vessel_basic`, `edit_vessel_advanced`
- **Capabilities**:
  - ✅ View vessel information
  - ✅ Edit basic vessel data
  - ✅ Edit advanced vessel settings
  - ❌ Cannot delete vessel
  - ❌ Cannot manage users

### `administrator` (Full Control)
- **Permissions**: `view_vessel`, `edit_vessel_basic`, `edit_vessel_advanced`, `delete_vessel`, `manage_vessel_users`
- **Capabilities**:
  - ✅ View vessel information
  - ✅ Edit all vessel data
  - ✅ Delete vessel
  - ✅ Manage vessel users
  - ✅ Full control over vessel

## 🔐 Permission Matrix

| Action | Normal | Moderator | Supervisor | Administrator |
|--------|--------|-----------|------------|---------------|
| **View Vessel** | ✅ | ✅ | ✅ | ✅ |
| **Edit Basic Data** | ❌ | ✅ | ✅ | ✅ |
| **Edit Advanced Data** | ❌ | ❌ | ✅ | ✅ |
| **Delete Vessel** | ❌ | ❌ | ❌ | ✅ |
| **Manage Users** | ❌ | ❌ | ❌ | ✅ |

## 🚀 Key Features

### 1. Vessel Isolation
- Users only see vessels they have access to
- No cross-vessel data leakage
- Secure data separation

### 2. Granular Permissions
- Different roles for different vessels
- Permission inheritance (administrator > supervisor > moderator > normal)
- Action-specific permissions

### 3. Automatic Role Assignment
- Vessel creators automatically become administrators
- Owner status is maintained in vessels table
- Seamless user experience

### 4. User Type Restrictions
- Only `paid_system` users can create vessels
- `employee_of_vessel` users are restricted to assigned vessels
- Clear separation of responsibilities

### 5. Scalable Architecture
- Easy to add new roles
- Easy to add new permissions
- Flexible permission combinations

## 🔄 Permission Flow

1. **User Login**: System checks `user_type`
2. **Vessel Loading**: System loads vessels through `vesselsThroughRoles()`
3. **Permission Calculation**: For each vessel, system checks `VesselUserRole` and `VesselRoleAccess`
4. **Frontend Display**: UI shows/hides actions based on permissions
5. **Action Authorization**: Backend validates permissions before execution

## 🛡️ Security Benefits

### Backend Security
- **Vessel-specific authorization**: Each action is checked per vessel
- **User type validation**: Only authorized users can create vessels
- **Permission inheritance**: Higher roles include lower role permissions
- **Database constraints**: Foreign keys ensure data integrity

### Frontend Security
- **Conditional rendering**: UI elements only show when user has permission
- **Graceful degradation**: Users see appropriate content for their role
- **Clear feedback**: Users understand their limitations
- **Consistent experience**: Same patterns across all vessels

## 📊 Use Cases

### Scenario 1: Fleet Owner
- **User Type**: `paid_system`
- **Role**: `administrator` on all owned vessels
- **Capabilities**: Full control over entire fleet

### Scenario 2: Vessel Captain
- **User Type**: `employee_of_vessel`
- **Role**: `supervisor` on assigned vessel
- **Capabilities**: Manage vessel operations, cannot delete vessel

### Scenario 3: Deck Hand
- **User Type**: `employee_of_vessel`
- **Role**: `normal` on assigned vessel
- **Capabilities**: View vessel information only

### Scenario 4: Fleet Manager
- **User Type**: `employee_of_vessel`
- **Role**: `moderator` on multiple vessels
- **Capabilities**: Basic editing across multiple vessels

## 🎯 Implementation Benefits

1. **Security**: Vessel-specific permissions prevent unauthorized access
2. **Scalability**: Easy to add new roles and permissions
3. **Flexibility**: Users can have different roles on different vessels
4. **User Experience**: Clear, role-appropriate interface
5. **Maintainability**: Centralized permission logic
6. **Auditability**: Clear permission trails and user actions

## 🔧 Technical Implementation

### Backend
- **Models**: `User`, `Vessel`, `VesselUserRole`, `VesselRoleAccess`
- **Controllers**: Vessel-specific authorization checks
- **Requests**: Permission validation in FormRequest classes
- **Relationships**: Complex many-to-many with pivot data

### Frontend
- **Components**: Permission-based rendering
- **Pages**: Vessel-specific action buttons
- **Navigation**: Role-appropriate menu items
- **State**: Permission data from backend

This sophisticated RBAC system provides the foundation for a secure, scalable, and user-friendly vessel management platform.
