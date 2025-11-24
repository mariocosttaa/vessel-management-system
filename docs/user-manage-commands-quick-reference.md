# UserManage Commands - Quick Reference

Quick reference card for all UserManage commands.

## 🚀 Quick Start

```bash
# Interactive menu
php artisan user:manage

# List all commands
php artisan list | grep -E "(user:|vessel:|crew:|movimentation:|supplier:|marea:|maintenance:|system:|audit:)"
```

---

## 📋 Command Quick Reference

### User Management

| Command | Description | Example |
|---------|-------------|---------|
| `user:manage` | Interactive menu | `php artisan user:manage` |
| `user:set-paid {user}` | Set user as paid_system | `php artisan user:set-paid admin@example.com` |
| `user:set-unpaid {user}` | Set user as employee_of_vessel | `php artisan user:set-unpaid user@example.com` |
| `user:set-employee {user}` | Set user as employee | `php artisan user:set-employee user@example.com` |
| `user:enable-login {user}` | Enable login access | `php artisan user:enable-login user@example.com` |
| `user:disable-login {user}` | Disable login access | `php artisan user:disable-login user@example.com` |
| `user:set-status {user} {status}` | Set user status | `php artisan user:set-status user@example.com active` |
| `user:set-administrative {user}` | Grant admin privileges | `php artisan user:set-administrative user@example.com` |
| `user:set-owner {user} {vessel}` | Set vessel owner | `php artisan user:set-owner admin@example.com 1` |
| `user:remove-owner {vessel}` | Remove vessel owner | `php artisan user:remove-owner 1` |
| `user:show-info {user}` | Show user details | `php artisan user:show-info user@example.com` |
| `user:list-paid` | List paid users | `php artisan user:list-paid` |
| `user:list-owners` | List vessel owners | `php artisan user:list-owners` |
| `user:check-limits [user]` | Check user limits | `php artisan user:check-limits` |

### Vessel Management

| Command | Description | Example |
|---------|-------------|---------|
| `vessel:create` | Create new vessel | `php artisan vessel:create` |
| `vessel:list` | List all vessels | `php artisan vessel:list` |
| `vessel:show {vessel}` | Show vessel details | `php artisan vessel:show 1` |
| `vessel:update {vessel}` | Update vessel | `php artisan vessel:update 1 --name="New Name"` |

### Crew Management

| Command | Description | Example |
|---------|-------------|---------|
| `crew:list {vessel}` | List crew members | `php artisan crew:list 1` |
| `crew:assign {user} {vessel}` | Assign crew member | `php artisan crew:assign user@example.com 1` |
| `crew-positions:assign-roles` | Assign roles to positions | `php artisan crew-positions:assign-roles` |

### Financial Management

| Command | Description | Example |
|---------|-------------|---------|
| `movimentation:list {vessel}` | List transactions | `php artisan movimentation:list 1` |
| `supplier:list {vessel}` | List suppliers | `php artisan supplier:list 1` |
| `marea:list {vessel}` | List mareas | `php artisan marea:list 1` |
| `maintenance:list {vessel}` | List maintenances | `php artisan maintenance:list 1` |

### System Management

| Command | Description | Example |
|---------|-------------|---------|
| `system:stats` | System statistics | `php artisan system:stats` |
| `audit:view` | View audit logs | `php artisan audit:view` |

---

## 🎯 Common Options

Most commands support these options:

- `--force`: Skip confirmation prompts
- `--format=json`: Output in JSON format (for list commands)
- `--help`: Show command help

---

## 📝 Common Workflows

### Onboard New User
```bash
php artisan user:set-paid user@example.com
php artisan user:enable-login user@example.com
php artisan vessel:create --owner=user@example.com
```

### Check System Health
```bash
php artisan system:stats
php artisan user:check-limits
php artisan audit:view --limit=20
```

### Manage Vessel
```bash
php artisan vessel:list
php artisan vessel:show 1
php artisan crew:list 1
php artisan movimentation:list 1
```

---

## 📚 Full Documentation

For complete documentation, see: `docs/user-manage-commands-guide.md`

