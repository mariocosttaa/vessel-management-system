# UserManage Commands Guide

Complete documentation for the UserManage command system - a comprehensive terminal-based management interface for the Vessel Management System.

## 📚 Table of Contents

- [Overview](#overview)
- [System Architecture](#system-architecture)
- [Quick Start](#quick-start)
- [Interactive Menu](#interactive-menu)
- [Command Categories](#command-categories)
- [User Management Commands](#user-management-commands)
- [Vessel Management Commands](#vessel-management-commands)
- [Crew Management Commands](#crew-management-commands)
- [Financial Management Commands](#financial-management-commands)
- [System Management Commands](#system-management-commands)
- [Examples & Use Cases](#examples--use-cases)
- [Best Practices](#best-practices)

---

## Overview

The UserManage command system provides a comprehensive terminal-based interface for managing all aspects of the Vessel Management System. It's organized into a modular structure with an interactive menu and individual commands that can be used directly.

### Key Features

- **Interactive Menu**: Easy-to-use menu-driven interface (`user:manage`)
- **Individual Commands**: Direct command execution for automation
- **Organized Structure**: Commands grouped by functionality
- **Comprehensive Coverage**: Manage users, vessels, crew, finances, and system
- **Safe Operations**: Built-in confirmations and validations

---

## System Architecture

### Directory Structure

```
app/Console/Commands/UserManage/
├── UserManageCommand.php          # Main interactive menu
├── Commands/
│   ├── User/                      # 13 user management commands
│   ├── Vessel/                    # 4 vessel management commands
│   ├── Crew/                      # 3 crew management commands
│   ├── Financial/                 # 4 financial management commands
│   └── System/                    # 2 system management commands
└── Handlers/
    ├── BaseHandler.php            # Base class for handlers
    ├── UserManagementHandler.php
    ├── VesselManagementHandler.php
    ├── CrewManagementHandler.php
    ├── FinancialManagementHandler.php
    └── SystemManagementHandler.php
```

### Design Pattern

The system uses a **Handler Pattern** where:
- **UserManageCommand**: Main entry point with interactive menu
- **Handlers**: Contain business logic for each category
- **Commands**: Individual artisan commands for direct execution

---

## Quick Start

### Interactive Menu

Start the interactive menu:

```bash
php artisan user:manage
```

This will display a menu with all available options organized by category.

### Direct Command Execution

Use individual commands directly:

```bash
# List all paid users
php artisan user:list-paid

# Create a new vessel
php artisan vessel:create

# View system statistics
php artisan system:stats
```

---

## Interactive Menu

The interactive menu (`user:manage`) provides an easy-to-use interface for all operations.

### Menu Structure

```
Main Menu:
═══════════════════════════════════════════════════════════
  USER MANAGEMENT
  1. Manage User Type (Paid/Unpaid)
  2. Manage Login Access
  3. Manage User Status
  4. Manage Administrative Privileges
  5. Manage Vessel Ownership
  6. View User Information
  7. List Users
  8. Check Limits & Usage
  9. Delete Users
 11. Manage Invitation Limits

  VESSEL MANAGEMENT
 20. Create Vessel
 21. List Vessels
 22. View Vessel Details
 23. Update Vessel
 24. Delete Vessels

  CREW MANAGEMENT
 30. List Crew Members
 31. Assign/Remove Crew Member
 32. Manage Crew Positions

  FINANCIAL MANAGEMENT
 40. List Movimentations
 41. List Suppliers
 42. List Mareas
 43. List Maintenances

  SYSTEM MANAGEMENT
 50. View Audit Logs
 51. Manage Attachments
 52. System Statistics

  0. Exit
```

### Navigation

- Enter the number of the option you want
- Enter `0` to exit
- Follow prompts for interactive operations

---

## Command Categories

### 1. User Management Commands

Manage users, their types, permissions, and access.

**Location**: `app/Console/Commands/UserManage/Commands/User/`

### 2. Vessel Management Commands

Create, update, list, and manage vessels.

**Location**: `app/Console/Commands/UserManage/Commands/Vessel/`

### 3. Crew Management Commands

Manage crew members and their positions.

**Location**: `app/Console/Commands/UserManage/Commands/Crew/`

### 4. Financial Management Commands

View financial data including movimentations, suppliers, mareas, and maintenances.

**Location**: `app/Console/Commands/UserManage/Commands/Financial/`

### 5. System Management Commands

System-wide operations like statistics and audit logs.

**Location**: `app/Console/Commands/UserManage/Commands/System/`

---

## User Management Commands

### `user:manage`

Interactive menu for all user management operations.

```bash
php artisan user:manage
```

### `user:set-paid`

Set a user as `paid_system` (allows vessel creation).

```bash
php artisan user:set-paid {user} [--force]

# Examples
php artisan user:set-paid admin@example.com
php artisan user:set-paid 1 --force
```

**Arguments:**
- `user`: User ID or email

**Options:**
- `--force`: Skip confirmation

**What it does:**
- Changes user type to `paid_system`
- Allows user to create and own vessels
- Shows user information before change

---

### `user:set-unpaid`

Set a user as `employee_of_vessel` (cannot create vessels).

```bash
php artisan user:set-unpaid {user} [--force]

# Examples
php artisan user:set-unpaid user@example.com
php artisan user:set-unpaid 5 --force
```

**Arguments:**
- `user`: User ID or email

**Options:**
- `--force`: Skip confirmation

**What it does:**
- Changes user type to `employee_of_vessel`
- Prevents vessel creation
- Warns if user owns vessels

---

### `user:set-employee`

Alias for `user:set-unpaid` - sets user as `employee_of_vessel`.

```bash
php artisan user:set-employee {user} [--force]
```

---

### `user:enable-login`

Enable login access for a user.

```bash
php artisan user:enable-login {user} [--all] [--force]

# Examples
php artisan user:enable-login user@example.com
php artisan user:enable-login user@example.com --force
php artisan user:enable-login --all  # Enable for all users
```

**Arguments:**
- `user`: User ID or email (optional if `--all` is used)

**Options:**
- `--all`: Enable login for all users
- `--force`: Skip confirmation

**What it does:**
- Enables `login_permitted` flag
- Clears temporary password
- Allows user to log in

---

### `user:disable-login`

Disable login access for a user.

```bash
php artisan user:disable-login {user} [--all] [--force]

# Examples
php artisan user:disable-login user@example.com
php artisan user:disable-login --all --force
```

**Arguments:**
- `user`: User ID or email (optional if `--all` is used)

**Options:**
- `--all`: Disable login for all users
- `--force`: Skip confirmation

**What it does:**
- Disables `login_permitted` flag
- Generates temporary password
- Prevents user login

---

### `user:set-status`

Set user status (active, inactive, on_leave).

```bash
php artisan user:set-status {user} {status} [--force]

# Examples
php artisan user:set-status user@example.com active
php artisan user:set-status 1 inactive --force
php artisan user:set-status user@example.com on_leave
```

**Arguments:**
- `user`: User ID or email
- `status`: One of `active`, `inactive`, `on_leave`

**Options:**
- `--force`: Skip confirmation

**Valid Statuses:**
- `active`: User is active
- `inactive`: User is inactive
- `on_leave`: User is on leave

---

### `user:set-administrative`

Grant or remove administrative privileges.

```bash
php artisan user:set-administrative {user} [--remove] [--force]

# Examples
php artisan user:set-administrative user@example.com
php artisan user:set-administrative user@example.com --remove
php artisan user:set-administrative 1 --force
```

**Arguments:**
- `user`: User ID or email

**Options:**
- `--remove`: Remove administrative privileges
- `--force`: Skip confirmation

**What it does:**
- Grants or removes `administrative` flag
- Affects system-wide permissions

---

### `user:set-owner`

Set a user as owner of a vessel.

```bash
php artisan user:set-owner {user} {vessel} [--force]

# Examples
php artisan user:set-owner admin@example.com 1
php artisan user:set-owner 1 TV-001 --force
```

**Arguments:**
- `user`: User ID or email (must be `paid_system`)
- `vessel`: Vessel ID or registration number

**Options:**
- `--force`: Skip confirmation

**Requirements:**
- User must be `paid_system` type
- Vessel must exist

---

### `user:remove-owner`

Remove owner from a vessel.

```bash
php artisan user:remove-owner {vessel} [--force]

# Examples
php artisan user:remove-owner 1
php artisan user:remove-owner TV-001 --force
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Options:**
- `--force`: Skip confirmation

---

### `user:show-info`

Display detailed information about a user.

```bash
php artisan user:show-info {user} [--detailed]

# Examples
php artisan user:show-info user@example.com
php artisan user:show-info 1 --detailed
```

**Arguments:**
- `user`: User ID or email

**Options:**
- `--detailed`: Show detailed information including vessels and roles

**Information Displayed:**
- Basic user information
- User type and status
- Login permissions
- Vessel ownership
- Vessel access through roles
- Account dates
- Permissions summary

---

### `user:list-paid`

List all `paid_system` users.

```bash
php artisan user:list-paid [--status=STATUS] [--with-vessels] [--format=FORMAT]

# Examples
php artisan user:list-paid
php artisan user:list-paid --status=active
php artisan user:list-paid --with-vessels
php artisan user:list-paid --format=json
```

**Options:**
- `--status`: Filter by status (active, inactive, on_leave)
- `--with-vessels`: Show owned vessels count
- `--format`: Output format (table, json)

---

### `user:list-owners`

List all users who own vessels.

```bash
php artisan user:list-owners [--format=FORMAT]

# Examples
php artisan user:list-owners
php artisan user:list-owners --format=json
```

**Options:**
- `--format`: Output format (table, json)

**Information Displayed:**
- User details
- Number of vessels owned
- User type and status

---

### `user:check-limits`

Check user vessel limits and current usage.

```bash
php artisan user:check-limits [user] [--format=FORMAT]

# Examples
php artisan user:check-limits
php artisan user:check-limits user@example.com
php artisan user:check-limits 1 --format=json
```

**Arguments:**
- `user`: User ID or email (optional - checks all if not provided)

**Options:**
- `--format`: Output format (table, json)

**What it shows:**
- Current vessel count
- Vessel limit (when implemented)
- List of owned vessels
- Limit status

---

## Vessel Management Commands

### `vessel:create`

Create a new vessel.

```bash
php artisan vessel:create [--name=NAME] [--registration=REG] [--type=TYPE] [--status=STATUS] [--owner=OWNER] [--capacity=CAP] [--year=YEAR] [--notes=NOTES]

# Examples
php artisan vessel:create
php artisan vessel:create --name="My Vessel" --registration="REG-001" --type=fishing
php artisan vessel:create --name="Cargo Ship" --registration="CS-001" --type=cargo --owner=admin@example.com
```

**Options:**
- `--name`: Vessel name
- `--registration`: Registration number (required, must be unique)
- `--type`: Vessel type (cargo, passenger, fishing, fish, yacht) [default: fishing]
- `--status`: Status (active, suspended, maintenance, inactive) [default: active]
- `--owner`: Owner email (must be `paid_system` user)
- `--capacity`: Capacity
- `--year`: Year built
- `--notes`: Notes

**Vessel Types:**
- `cargo`: Cargo vessel
- `passenger`: Passenger vessel
- `fishing`: Fishing vessel
- `fish`: Fish processing vessel
- `yacht`: Yacht

---

### `vessel:list`

List all vessels.

```bash
php artisan vessel:list [--status=STATUS] [--type=TYPE] [--format=FORMAT]

# Examples
php artisan vessel:list
php artisan vessel:list --status=active
php artisan vessel:list --type=fishing
php artisan vessel:list --format=json
```

**Options:**
- `--status`: Filter by status
- `--type`: Filter by vessel type
- `--format`: Output format (table, json)

**Information Displayed:**
- Vessel details
- Owner information
- Crew count
- Movimentations count
- Mareas count
- Maintenances count

---

### `vessel:show`

Display detailed information about a vessel.

```bash
php artisan vessel:show {vessel}

# Examples
php artisan vessel:show 1
php artisan vessel:show TV-001
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Information Displayed:**
- Complete vessel information
- Owner details
- Crew members count
- Financial data counts
- Operational data counts

---

### `vessel:update`

Update vessel information.

```bash
php artisan vessel:update {vessel} [--name=NAME] [--registration=REG] [--type=TYPE] [--status=STATUS] [--capacity=CAP] [--year=YEAR] [--notes=NOTES] [--owner=OWNER]

# Examples
php artisan vessel:update 1 --name="Updated Name"
php artisan vessel:update TV-001 --status=maintenance
php artisan vessel:update 1 --owner=admin@example.com
php artisan vessel:update 1 --owner=  # Remove owner (empty value)
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Options:**
- `--name`: New name
- `--registration`: New registration number
- `--type`: Vessel type
- `--status`: Status
- `--capacity`: Capacity
- `--year`: Year built
- `--notes`: Notes
- `--owner`: Owner email (leave empty to remove)

---

## Crew Management Commands

### `crew:list`

List crew members for a vessel.

```bash
php artisan crew:list {vessel} [--format=FORMAT]

# Examples
php artisan crew:list 1
php artisan crew:list TV-001
php artisan crew:list 1 --format=json
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Options:**
- `--format`: Output format (table, json)

**Information Displayed:**
- Crew member details
- Position information
- Status

---

### `crew:assign`

Assign or remove a crew member from a vessel.

```bash
php artisan crew:assign {user} {vessel} [--remove]

# Examples
php artisan crew:assign user@example.com 1
php artisan crew:assign 5 TV-001
php artisan crew:assign user@example.com 1 --remove
```

**Arguments:**
- `user`: User ID or email
- `vessel`: Vessel ID or registration number

**Options:**
- `--remove`: Remove crew member from vessel instead

**What it does:**
- Assigns user to vessel (sets `vessel_id`)
- Or removes user from vessel (sets `vessel_id` to null)

---

### `crew-positions:assign-roles`

Assign default vessel roles to crew positions based on position hierarchy.

```bash
php artisan crew-positions:assign-roles [--dry-run] [--force]

# Examples
php artisan crew-positions:assign-roles
php artisan crew-positions:assign-roles --dry-run
php artisan crew-positions:assign-roles --force
```

**Options:**
- `--dry-run`: Show what would be changed without making changes
- `--force`: Force update even if role is already assigned

**What it does:**
- Automatically assigns vessel roles to crew positions
- Based on position hierarchy and default role mappings

---

## Financial Management Commands

### `movimentation:list`

List movimentations (transactions) for a vessel.

```bash
php artisan movimentation:list {vessel} [--limit=LIMIT] [--format=FORMAT]

# Examples
php artisan movimentation:list 1
php artisan movimentation:list TV-001 --limit=50
php artisan movimentation:list 1 --format=json
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Options:**
- `--limit`: Number of records to show [default: 20]
- `--format`: Output format (table, json)

**Information Displayed:**
- Transaction number
- Type (income/expense/transfer)
- Amount and currency
- Date
- Status

---

### `supplier:list`

List suppliers for a vessel.

```bash
php artisan supplier:list {vessel} [--format=FORMAT]

# Examples
php artisan supplier:list 1
php artisan supplier:list TV-001
php artisan supplier:list 1 --format=json
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Options:**
- `--format`: Output format (table, json)

**Information Displayed:**
- Company name
- Contact information
- Address

---

### `marea:list`

List mareas (fishing trips) for a vessel.

```bash
php artisan marea:list {vessel} [--format=FORMAT]

# Examples
php artisan marea:list 1
php artisan marea:list TV-001
php artisan marea:list 1 --format=json
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Options:**
- `--format`: Output format (table, json)

**Information Displayed:**
- Marea number
- Name and description
- Status
- Departure and return dates

---

### `maintenance:list`

List maintenances for a vessel.

```bash
php artisan maintenance:list {vessel} [--format=FORMAT]

# Examples
php artisan maintenance:list 1
php artisan maintenance:list TV-001
php artisan maintenance:list 1 --format=json
```

**Arguments:**
- `vessel`: Vessel ID or registration number

**Options:**
- `--format`: Output format (table, json)

**Information Displayed:**
- Maintenance number
- Name and description
- Status
- Start and end dates

---

## System Management Commands

### `system:stats`

Display system statistics.

```bash
php artisan system:stats [--format=FORMAT]

# Examples
php artisan system:stats
php artisan system:stats --format=json
```

**Options:**
- `--format`: Output format (table, json)

**Information Displayed:**
- Total users (paid/unpaid breakdown)
- Total vessels (active breakdown)
- Total crew members
- Financial data counts
- Operational data counts
- Vessel owners summary

---

### `audit:view`

View audit logs.

```bash
php artisan audit:view [--limit=LIMIT] [--format=FORMAT]

# Examples
php artisan audit:view
php artisan audit:view --limit=50
php artisan audit:view --format=json
```

**Options:**
- `--limit`: Number of logs to show [default: 20]
- `--format`: Output format (table, json)

**Information Displayed:**
- User who performed action
- Action type
- Model affected
- Timestamp

---

## Examples & Use Cases

### Common Workflows

#### 1. Onboarding a New Paid User

```bash
# 1. Set user as paid
php artisan user:set-paid newuser@example.com

# 2. Enable login
php artisan user:enable-login newuser@example.com

# 3. Create their first vessel
php artisan vessel:create --name="My First Vessel" --registration="MFV-001" --owner=newuser@example.com

# 4. Verify setup
php artisan user:show-info newuser@example.com --detailed
```

#### 2. Managing Crew Members

```bash
# 1. List crew for a vessel
php artisan crew:list 1

# 2. Assign a crew member
php artisan crew:assign crew@example.com 1

# 3. View updated crew list
php artisan crew:list 1
```

#### 3. System Health Check

```bash
# 1. Check system statistics
php artisan system:stats

# 2. Check user limits
php artisan user:check-limits

# 3. View recent audit logs
php artisan audit:view --limit=20
```

#### 4. Vessel Management

```bash
# 1. List all vessels
php artisan vessel:list

# 2. View vessel details
php artisan vessel:show 1

# 3. Update vessel status
php artisan vessel:update 1 --status=maintenance

# 4. Check financial data
php artisan movimentation:list 1
php artisan supplier:list 1
```

#### 5. User Access Management

```bash
# 1. Disable login for a user
php artisan user:disable-login user@example.com

# 2. Change user status
php artisan user:set-status user@example.com inactive

# 3. Re-enable login
php artisan user:enable-login user@example.com
```

### Interactive Menu Workflow

```bash
# Start interactive menu
php artisan user:manage

# Navigate through options:
# 1. Select option number (e.g., "20" for Create Vessel)
# 2. Follow prompts
# 3. Confirm actions
# 4. Return to menu or exit
```

---

## Best Practices

### 1. Use Interactive Menu for Exploration

When learning the system or performing ad-hoc operations, use the interactive menu:

```bash
php artisan user:manage
```

### 2. Use Direct Commands for Automation

For scripts, CI/CD, or scheduled tasks, use direct commands:

```bash
# In a script
php artisan user:list-paid --format=json > users.json
php artisan system:stats --format=json > stats.json
```

### 3. Always Verify Before Destructive Operations

Use `--force` sparingly. Always verify information first:

```bash
# Good practice
php artisan user:show-info user@example.com
php artisan vessel:show 1
# Then perform action
php artisan user:set-paid user@example.com
```

### 4. Use JSON Format for Scripts

When integrating with other tools, use JSON format:

```bash
php artisan user:list-paid --format=json
php artisan vessel:list --format=json
```

### 5. Check Limits Regularly

Monitor system usage:

```bash
# Check all users
php artisan user:check-limits

# Check specific user
php artisan user:check-limits user@example.com
```

### 6. Review Audit Logs

Keep track of system changes:

```bash
# Recent activity
php artisan audit:view --limit=50

# Export for analysis
php artisan audit:view --limit=100 --format=json > audit.json
```

---

## Command Reference Quick Sheet

### User Commands
```bash
user:manage                  # Interactive menu
user:set-paid {user}         # Set as paid_system
user:set-unpaid {user}       # Set as employee_of_vessel
user:enable-login {user}     # Enable login
user:disable-login {user}    # Disable login
user:set-status {user} {status}  # Set status
user:set-administrative {user}   # Set admin
user:set-owner {user} {vessel}   # Set vessel owner
user:show-info {user}        # Show user info
user:list-paid               # List paid users
user:list-owners             # List vessel owners
user:check-limits [user]     # Check limits
```

### Vessel Commands
```bash
vessel:create                # Create vessel
vessel:list                  # List vessels
vessel:show {vessel}         # Show vessel
vessel:update {vessel}       # Update vessel
```

### Crew Commands
```bash
crew:list {vessel}           # List crew
crew:assign {user} {vessel}  # Assign crew
crew-positions:assign-roles  # Assign roles
```

### Financial Commands
```bash
movimentation:list {vessel}   # List transactions
supplier:list {vessel}        # List suppliers
marea:list {vessel}           # List mareas
maintenance:list {vessel}     # List maintenances
```

### System Commands
```bash
system:stats                 # System statistics
audit:view                   # View audit logs
```

---

## Troubleshooting

### Command Not Found

If a command is not found, ensure:
1. Commands are in the correct namespace
2. Run `composer dump-autoload`
3. Clear Laravel cache: `php artisan cache:clear`

### Permission Errors

Some operations require:
- User must be `paid_system` to own vessels
- Proper vessel access for crew operations
- Administrative privileges for system operations

### Data Validation

Commands validate:
- User existence and type
- Vessel existence
- Unique constraints (registration numbers)
- Required relationships

---

## Support

For issues or questions:
1. Check command help: `php artisan {command} --help`
2. Review this documentation
3. Check system logs
4. Use `--dry-run` options when available

---

**Last Updated**: 2025-01-XX
**Version**: 1.0.0

