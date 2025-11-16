# SaaS User Management - Quick Reference

## 🚀 Quick Start

### Easiest Way: Interactive Menu

```bash
php artisan user:manage
```

Just run this command and follow the intuitive menu! No need to remember individual commands.

## Interactive Menu Options

When you run `php artisan user:manage`, you'll see:

```
Main Menu:
  1. Manage User Type (Paid/Unpaid)
  2. Manage Login Access
  3. Manage User Status
  4. Manage Administrative Privileges
  5. Manage Vessel Ownership
  6. View User Information
  7. List Users
  8. Check Limits & Usage
  0. Exit
```

## Common Tasks

### Set User as Paid
1. Run `php artisan user:manage`
2. Select option `1` (Manage User Type)
3. Select option `1` (Set user as Paid)
4. Enter user email or ID
5. Confirm

### Disable User Login
1. Run `php artisan user:manage`
2. Select option `2` (Manage Login Access)
3. Select option `2` (Disable login for user)
4. Enter user email or ID
5. Confirm

### Set Vessel Owner
1. Run `php artisan user:manage`
2. Select option `5` (Manage Vessel Ownership)
3. Select option `1` (Set user as vessel owner)
4. Enter user email or ID
5. Enter vessel ID or registration number
6. Confirm

### View User Info
1. Run `php artisan user:manage`
2. Select option `6` (View User Information)
3. Enter user email or ID

### List All Paid Users
1. Run `php artisan user:manage`
2. Select option `7` (List Users)
3. Select option `1` (List all paid users)

## Individual Commands (If You Prefer)

### User Type
```bash
php artisan user:set-paid user@example.com
php artisan user:set-unpaid user@example.com
```

### Login Access
```bash
php artisan user:enable-login user@example.com
php artisan user:disable-login user@example.com
```

### Status
```bash
php artisan user:set-status user@example.com active
php artisan user:set-status user@example.com inactive
```

### Vessel Ownership
```bash
php artisan user:set-owner user@example.com REG-12345
php artisan user:remove-owner REG-12345
```

### Information
```bash
php artisan user:show-info user@example.com --detailed
php artisan user:list-paid --with-vessels
php artisan user:list-owners
php artisan user:check-limits
```

## Tips

- **Use the interactive menu** (`user:manage`) for easiest management
- **Individual commands** are available if you prefer direct commands
- **All commands support** user ID or email
- **Use `--force`** flag to skip confirmations
- **Use `--help`** on any command for detailed options

## Need Help?

- Full guide: `docs/saas-console-commands-guide.md`
- Database analysis: `docs/saas-user-management-analysis.md`
- Command help: `php artisan user:manage --help`

