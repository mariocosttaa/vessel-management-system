# SaaS User Management - Console Commands Guide

This guide explains how to use the console commands for managing user permissions, paid status, and vessel ownership in the Vessel Management System.

## Available Commands

### 🎯 Interactive Menu Command (Recommended)

#### `user:manage`
**Interactive menu-driven command to manage everything easily.**

```bash
php artisan user:manage
```

**What it does:**
- Provides an intuitive menu interface
- Access all user management functions from one place
- No need to remember individual command names
- Step-by-step guided operations

**Menu Options:**
1. Manage User Type (Paid/Unpaid)
2. Manage Login Access
3. Manage User Status
4. Manage Administrative Privileges
5. Manage Vessel Ownership
6. View User Information
7. List Users
8. Check Limits & Usage
0. Exit

This is the **easiest way** to manage users - just run `php artisan user:manage` and follow the menu!

### Individual Commands

### User Type Management

#### `user:set-paid`
Set a user as `paid_system` (allows vessel creation).

```bash
# By email
php artisan user:set-paid user@example.com

# By ID
php artisan user:set-paid 123

# Force without confirmation
php artisan user:set-paid user@example.com --force
```

**What it does:**
- Changes `user_type` to `'paid_system'`
- User can now create and manage vessels
- Shows user information and owned vessels count

#### `user:set-employee`
Set a user as `employee_of_vessel` (cannot create vessels).

```bash
php artisan user:set-employee user@example.com
php artisan user:set-employee 123 --force
```

**What it does:**
- Changes `user_type` to `'employee_of_vessel'`
- User can no longer create vessels
- Warns if user owns vessels (ownership should be transferred)

#### `user:set-unpaid`
Set a user as unpaid (same as `user:set-employee` - alias for clarity).

```bash
php artisan user:set-unpaid user@example.com
php artisan user:set-unpaid 123 --force
```

**What it does:**
- Changes `user_type` to `'employee_of_vessel'`
- User can no longer create vessels
- Warns if user owns vessels (ownership should be transferred)

### Login Access Control

#### `user:enable-login`
Enable login access for a user.

```bash
# Single user
php artisan user:enable-login user@example.com

# All users
php artisan user:enable-login dummy --all

# Force without confirmation
php artisan user:enable-login user@example.com --force
```

**What it does:**
- Sets `login_permitted` to `true`
- Clears `temporary_password`
- User can now log into the system

#### `user:disable-login`
Disable login access for a user.

```bash
php artisan user:disable-login user@example.com
php artisan user:disable-login dummy --all --force
```

**What it does:**
- Sets `login_permitted` to `false`
- Generates a `temporary_password`
- User cannot log into the system

### User Status Management

#### `user:set-status`
Set user status (active, inactive, on_leave).

```bash
php artisan user:set-status user@example.com active
php artisan user:set-status user@example.com inactive
php artisan user:set-status user@example.com on_leave --force
```

**Valid statuses:**
- `active` - User is active
- `inactive` - User is inactive
- `on_leave` - User is on leave

### Administrative Privileges

#### `user:set-administrative`
Grant or remove administrative privileges.

```bash
# Grant administrative privileges
php artisan user:set-administrative user@example.com

# Remove administrative privileges
php artisan user:set-administrative user@example.com --remove

# Force without confirmation
php artisan user:set-administrative user@example.com --force
```

**What it does:**
- Sets or removes `administrative` flag
- Grants additional admin permissions

### Vessel Ownership Management

#### `user:set-owner`
Set a user as owner of a vessel.

```bash
# By vessel ID
php artisan user:set-owner user@example.com 123

# By vessel registration number
php artisan user:set-owner user@example.com REG-12345

# Force without confirmation
php artisan user:set-owner user@example.com 123 --force
```

**Requirements:**
- User must be `paid_system` type
- Vessel must exist

**What it does:**
- Sets `vessels.owner_id` to the user's ID
- User becomes the owner of the vessel

#### `user:remove-owner`
Remove owner from a vessel.

```bash
php artisan user:remove-owner 123
php artisan user:remove-owner REG-12345 --force
```

**What it does:**
- Sets `vessels.owner_id` to `null`
- Vessel becomes ownerless

### Information & Reporting Commands

#### `user:show-info`
Display detailed information about a user.

```bash
# Basic information
php artisan user:show-info user@example.com

# Detailed information (includes vessels and roles)
php artisan user:show-info user@example.com --detailed
```

**Shows:**
- Basic user information
- User type and status
- Login permissions
- Vessel ownership
- Vessel access through roles
- Account dates
- Permissions summary

#### `user:list-paid`
List all `paid_system` users.

```bash
# Basic list
php artisan user:list-paid

# Filter by status
php artisan user:list-paid --status=active

# Include vessel count
php artisan user:list-paid --with-vessels

# JSON output
php artisan user:list-paid --format=json
```

#### `user:list-owners`
List all users who own vessels.

```bash
php artisan user:list-owners
php artisan user:list-owners --format=json
```

**Shows:**
- User information
- Number of vessels owned
- Total vessels across all owners

#### `user:check-limits`
Check user vessel limits and current usage.

```bash
# Check single user
php artisan user:check-limits user@example.com

# Check all paid_system users
php artisan user:check-limits

# JSON output
php artisan user:check-limits --format=json
```

**Note:** Currently shows vessel counts. Once `vessel_limit` column is added to the database, this will show limit status and warnings.

## Quick Start

### Easiest Way: Interactive Menu

```bash
# Just run this command and follow the menu!
php artisan user:manage
```

The interactive menu will guide you through all operations step by step.

## Common Workflows

### Making a User a Paid System Owner

```bash
# 1. Set user as paid_system
php artisan user:set-paid user@example.com

# 2. Enable login (if needed)
php artisan user:enable-login user@example.com

# 3. Set as owner of a vessel
php artisan user:set-owner user@example.com REG-12345

# 4. Verify
php artisan user:show-info user@example.com --detailed
```

### Disabling a User's Access

```bash
# 1. Disable login
php artisan user:disable-login user@example.com

# 2. Set status to inactive
php artisan user:set-status user@example.com inactive

# 3. Verify
php artisan user:show-info user@example.com
```

### Transferring Vessel Ownership

```bash
# 1. Remove current owner
php artisan user:remove-owner REG-12345

# 2. Set new owner
php artisan user:set-owner newowner@example.com REG-12345

# 3. Verify
php artisan user:list-owners
```

### Bulk Operations

```bash
# List all paid users
php artisan user:list-paid --with-vessels

# Check limits for all users
php artisan user:check-limits

# Enable login for all users (use with caution)
php artisan user:enable-login dummy --all --force
```

## Database Columns Used

### Users Table
- `user_type` - ENUM('paid_system', 'employee_of_vessel')
- `login_permitted` - BOOLEAN
- `status` - ENUM('active', 'inactive', 'on_leave')
- `administrative` - BOOLEAN
- `temporary_password` - VARCHAR(255)

### Vessels Table
- `owner_id` - BIGINT UNSIGNED (foreign key to users)

## Future Enhancements

Once subscription columns are added to the database, additional commands will be available:

- `subscription:activate` - Activate user subscription
- `subscription:expire` - Expire user subscription
- `subscription:set-plan` - Set subscription plan
- `subscription:set-limit` - Set vessel/feature limits
- `subscription:check-expired` - Check and expire outdated subscriptions
- `subscription:extend-trial` - Extend trial period
- `subscription:renew` - Renew subscription

## Safety Features

All commands include:
- **Confirmation prompts** (unless `--force` is used)
- **Validation** of user and vessel existence
- **Warnings** for potentially destructive operations
- **Information display** before making changes
- **Error handling** for invalid inputs

## Examples

### Example 1: Onboard a New Paid User

```bash
# User already exists, just needs to be upgraded
php artisan user:set-paid john@example.com
php artisan user:enable-login john@example.com
php artisan user:set-status john@example.com active
php artisan user:show-info john@example.com --detailed
```

### Example 2: Suspend a User

```bash
php artisan user:set-status john@example.com inactive
php artisan user:disable-login john@example.com
php artisan user:show-info john@example.com
```

### Example 3: Audit All Paid Users

```bash
# List all paid users with vessel counts
php artisan user:list-paid --with-vessels

# Check limits for all
php artisan user:check-limits

# List all owners
php artisan user:list-owners
```

### Example 4: Transfer Ownership

```bash
# Check current owner
php artisan user:show-info oldowner@example.com --detailed

# Remove old owner
php artisan user:remove-owner REG-12345

# Set new owner
php artisan user:set-owner newowner@example.com REG-12345

# Verify
php artisan user:list-owners
```

## Troubleshooting

### User Not Found
```
Error: User not found: user@example.com
```
**Solution:** Check the email or ID is correct. Use `user:list-paid` to see available users.

### Vessel Not Found
```
Error: Vessel not found: REG-12345
```
**Solution:** Check the vessel ID or registration number is correct.

### User Must Be Paid System
```
Error: User must be 'paid_system' to own vessels.
```
**Solution:** First run `php artisan user:set-paid user@example.com`

### Permission Denied
If you get permission errors, ensure you're running the commands with appropriate system permissions.

## Related Documentation

- [SaaS User Management Analysis](./saas-user-management-analysis.md) - Complete database analysis
- [Database Schema](./database-schema.md) - Full database structure
- [User Model](../app/Models/User.php) - User model implementation

