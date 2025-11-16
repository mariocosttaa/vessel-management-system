# SaaS User Management - Database Analysis

## Current Database Columns

### Users Table (`users`)

#### User Type & Access Control
- **`user_type`** (ENUM: `'paid_system'`, `'employee_of_vessel'`)
  - Purpose: Distinguishes between system owners and vessel employees
  - Current Usage: Only `paid_system` users can create vessels
  - Status: ✅ Exists

- **`login_permitted`** (BOOLEAN, default: `true`)
  - Purpose: Controls whether user can log into the system
  - Current Usage: Can disable/enable system access
  - Status: ✅ Exists

- **`status`** (ENUM: `'active'`, `'inactive'`, `'on_leave'`)
  - Purpose: User account status
  - Current Usage: Tracks user activity state
  - Status: ✅ Exists

- **`administrative`** (BOOLEAN, default: `false`)
  - Purpose: Administrative privileges flag
  - Current Usage: Additional admin permissions
  - Status: ✅ Exists

#### Authentication & Security
- **`email`** (VARCHAR(255), UNIQUE)
- **`password`** (VARCHAR(255))
- **`email_verified_at`** (TIMESTAMP, nullable)
- **`two_factor_secret`** (TEXT, nullable)
- **`two_factor_recovery_codes`** (TEXT, nullable)
- **`two_factor_confirmed_at`** (TIMESTAMP, nullable)
- **`remember_token`** (VARCHAR(100), nullable)

#### Crew Member Fields (for `employee_of_vessel` users)
- **`vessel_id`** (BIGINT UNSIGNED, nullable) - Foreign key to vessels
- **`position_id`** (BIGINT UNSIGNED, nullable) - Foreign key to crew_positions
- **`phone`** (VARCHAR(50), nullable)
- **`date_of_birth`** (DATE, nullable)
- **`hire_date`** (DATE, nullable)
- **`house_of_zeros`** (TINYINT, default: 2)
- **`notes`** (TEXT, nullable)
- **`temporary_password`** (VARCHAR(255), nullable)

#### Preferences & Notifications
- **`vessel_admin_notification`** (BOOLEAN, default: `false`)
- **`language`** (VARCHAR(5), default: `'en'`)

#### Invitation System
- **`invitation_token`** (VARCHAR(255), nullable)
- **`invitation_sent_at`** (TIMESTAMP, nullable)
- **`invitation_accepted_at`** (TIMESTAMP, nullable)

#### OAuth Integration
- **`provider`** (VARCHAR(255), nullable) - e.g., 'google', 'github'
- **`provider_id`** (VARCHAR(255), nullable)
- **`avatar`** (VARCHAR(255), nullable)

### Vessels Table (`vessels`)

#### Ownership
- **`owner_id`** (BIGINT UNSIGNED, nullable) - Foreign key to users
  - Purpose: Links vessel to its owner (paid_system user)
  - Current Usage: Identifies who owns/manages the vessel
  - Status: ✅ Exists

## Missing Columns for Full SaaS Functionality

### Recommended Additions to `users` Table

#### Subscription Management
```sql
-- Subscription status
subscription_status ENUM('active', 'expired', 'cancelled', 'trial', 'suspended') DEFAULT 'trial'

-- Subscription dates
subscription_expires_at TIMESTAMP NULL
trial_ends_at TIMESTAMP NULL
subscription_started_at TIMESTAMP NULL
subscription_cancelled_at TIMESTAMP NULL

-- Payment information
last_payment_at TIMESTAMP NULL
next_payment_at TIMESTAMP NULL
payment_failed_at TIMESTAMP NULL

-- Plan information
subscription_plan VARCHAR(50) NULL DEFAULT 'free' -- 'free', 'basic', 'pro', 'enterprise'
billing_cycle ENUM('monthly', 'yearly') NULL DEFAULT 'monthly'
```

#### Usage Limits
```sql
-- Vessel limits
vessel_limit INT NULL DEFAULT 1 -- NULL = unlimited
current_vessel_count INT DEFAULT 0 -- Cached count, recalculated periodically

-- Feature limits
max_crew_members_per_vessel INT NULL DEFAULT NULL -- NULL = unlimited
max_transactions_per_month INT NULL DEFAULT NULL -- NULL = unlimited
max_storage_gb DECIMAL(5,2) NULL DEFAULT 1.0 -- Storage limit in GB
```

#### Billing & Payment
```sql
-- Billing information
stripe_customer_id VARCHAR(255) NULL
stripe_subscription_id VARCHAR(255) NULL
payment_method VARCHAR(50) NULL -- 'card', 'bank_transfer', 'paypal', etc.

-- Billing address (or reference to separate billing table)
billing_email VARCHAR(255) NULL
```

## Current Capabilities

### ✅ What We Can Do Now

1. **User Type Management**
   - Set user as `paid_system` or `employee_of_vessel`
   - Control who can create vessels

2. **Access Control**
   - Enable/disable login with `login_permitted`
   - Set user status (active, inactive, on_leave)
   - Set administrative privileges

3. **Vessel Ownership**
   - Assign vessel owners via `vessels.owner_id`
   - Track which users own which vessels

### ❌ What We Cannot Do Yet

1. **Subscription Management**
   - No subscription status tracking
   - No expiration dates
   - No trial period management

2. **Usage Limits**
   - No vessel creation limits
   - No feature-based restrictions
   - No storage limits

3. **Payment Tracking**
   - No payment history
   - No billing cycle management
   - No payment method storage

4. **Plan Management**
   - No subscription plan tiers
   - No plan-based feature access

## Recommended Console Commands

Based on current columns, we can create these commands:

1. **`user:set-paid`** - Set user as paid_system
2. **`user:set-employee`** - Set user as employee_of_vessel
3. **`user:enable-login`** - Enable user login access
4. **`user:disable-login`** - Disable user login access
5. **`user:set-owner`** - Set user as owner of a vessel
6. **`user:remove-owner`** - Remove user as owner of a vessel
7. **`user:set-status`** - Set user status (active/inactive/on_leave)
8. **`user:set-administrative`** - Set/remove administrative privileges
9. **`user:show-info`** - Display user information and permissions
10. **`user:list-paid`** - List all paid_system users
11. **`user:list-owners`** - List all users who own vessels
12. **`user:check-limits`** - Check user's current vessel count vs limits

## Future Enhancements (After Adding Missing Columns)

Once subscription columns are added:

1. **`subscription:activate`** - Activate user subscription
2. **`subscription:expire`** - Expire user subscription
3. **`subscription:set-plan`** - Set subscription plan
4. **`subscription:set-limit`** - Set vessel/feature limits
5. **`subscription:check-expired`** - Check and expire outdated subscriptions
6. **`subscription:extend-trial`** - Extend trial period
7. **`subscription:renew`** - Renew subscription

## Implementation Priority

### Phase 1: Use Existing Columns (Immediate)
- Create console commands for current columns
- Implement user management
- Implement owner management

### Phase 2: Add Subscription Columns (Next)
- Add subscription status and dates
- Add plan information
- Add payment tracking

### Phase 3: Add Usage Limits (Future)
- Add vessel limits
- Add feature limits
- Add storage limits

### Phase 4: Full SaaS Features (Future)
- Payment integration
- Automated billing
- Usage monitoring
- Automated limit enforcement

