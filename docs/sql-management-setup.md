# SQL Management Setup Guide

This guide explains how to configure and use the SQL management command to execute SQL queries and send results to Discord channels (production only).

## 📑 Table of Contents

- [Overview](#-overview)
- [Quick Setup](#-quick-setup)
  - [Complete .env Configuration](#complete-env-configuration)
- [Detailed Setup](#-detailed-setup)
  - [Step 1: Create Discord Webhook](#step-1-create-discord-webhook)
  - [Step 2: Environment Variables Reference](#step-2-environment-variables-reference)
  - [Step 3: Test the Command](#step-3-test-the-command)
- [Usage](#-usage)
  - [Interactive Mode](#interactive-mode)
  - [Single Query Mode](#single-query-mode)
  - [Command Options](#command-options)
- [Security Features](#-security-features)
- [Query Types](#-query-types)
- [Discord Integration](#-discord-integration)
- [Production-Only Logging](#production-only-logging)
- [Troubleshooting](#-troubleshooting)
- [Quick Reference](#-quick-reference)
- [Setup Checklist](#-setup-checklist)

## 📋 Overview

The SQL management command allows you to:
- Execute SQL queries directly from the terminal
- View formatted results in the console
- Automatically send query results to Discord (production only)
- Restrict query types for security (SELECT only by default)
- Use interactive mode for multiple queries
- Track query execution time and row counts

## 🚀 Quick Setup

### Complete .env Configuration

Here's the complete SQL management configuration for your `.env` file:

```env
# ============================================
# SQL Management Configuration
# ============================================

# Restrict SQL Discord logging to production only (default: true)
# If true: Discord logs only work in production environment
# If false: Discord logs work in all environments
SQL_DISCORD_ONLY_ON_PRODUCTION=true

# Discord webhook URL for SQL query results (production only)
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/1442582149772476519/TcRHFaDEWWl0zzCMLlHUIvigDxtoR6X07gizTXIZnJHXuvZ9Zcbtc02BNN8sJ4c-HMwN

# Optional: Custom username for Discord bot (default: sql-manager)
SQL_DISCORD_WEBHOOK_USERNAME="sql-manager"

# Optional: Avatar URL for Discord bot
SQL_DISCORD_WEBHOOK_AVATAR_URL=

# Allow non-SELECT queries (default: false)
# If true: Allows INSERT, UPDATE, DELETE, etc.
# If false: Only SELECT, SHOW, DESCRIBE, EXPLAIN allowed
SQL_ALLOW_NON_SELECT=false
```

**Quick Start:**
1. Copy the webhook URL from your Discord server
2. Paste it into your `.env` file as `SQL_DISCORD_WEBHOOK_URL`
3. Set `SQL_DISCORD_ONLY_ON_PRODUCTION=true` (default)
4. Run `php artisan config:clear`
5. Test with `php artisan sql:manage --interactive`

## 📖 Detailed Setup

### Step 1: Create Discord Webhook

1. **Open your Discord server**
2. **Navigate to Server Settings** → **Integrations** → **Webhooks**
3. **Create a new webhook** for SQL query results
4. **Copy the webhook URL** (looks like: `https://discord.com/api/webhooks/...`)

### Step 2: Environment Variables Reference

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `SQL_DISCORD_WEBHOOK_URL` | Webhook URL for SQL query results | `https://discord.com/api/webhooks/...` |

#### Optional Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `SQL_DISCORD_ONLY_ON_PRODUCTION` | `true` | If `true`, only sends logs in production environment |
| `SQL_DISCORD_WEBHOOK_USERNAME` | `sql-manager` | Bot username for SQL logs |
| `SQL_DISCORD_WEBHOOK_AVATAR_URL` | `null` | Avatar URL for Discord bot |
| `SQL_ALLOW_NON_SELECT` | `false` | If `true`, allows INSERT, UPDATE, DELETE queries |

**Important Notes:**
- By default, Discord logging works **only in production** environment
- Set `SQL_DISCORD_ONLY_ON_PRODUCTION=false` to enable in all environments
- By default, only SELECT queries are allowed for security
- Set `SQL_ALLOW_NON_SELECT=true` to allow other query types (use with caution)

### Step 3: Test the Command

Test the SQL management command:

```bash
# Interactive mode
php artisan sql:manage --interactive

# Single query
php artisan sql:manage "SELECT * FROM users LIMIT 5"

# Single query without Discord
php artisan sql:manage "SELECT COUNT(*) FROM vessels" --no-discord
```

## 💻 Usage

### Interactive Mode

Run the command in interactive mode to execute multiple queries:

```bash
php artisan sql:manage --interactive
```

**Interactive Commands:**
- Type SQL queries to execute them
- Type `help` to see available commands
- Type `exit`, `quit`, or `q` to exit

**Example Session:**
```
Enter SQL query: SELECT * FROM users LIMIT 5
Executing SQL query...

+----+-------+------------------+
| id | name  | email            |
+----+-------+------------------+
| 1  | John  | john@example.com |
| 2  | Jane  | jane@example.com |
...

✅ Query executed successfully
Rows returned: 5
Execution time: 0.05s

Sending results to Discord...
✅ Results sent to Discord successfully.

Enter SQL query: exit
Goodbye!
```

### Single Query Mode

Execute a single query directly:

```bash
php artisan sql:manage "SELECT COUNT(*) as total FROM vessels"
```

### Command Options

```bash
# Interactive mode
php artisan sql:manage --interactive

# Single query
php artisan sql:manage "SELECT * FROM users"

# Skip Discord (execute locally only)
php artisan sql:manage "SELECT * FROM users" --no-discord
```

## 🔒 Security Features

### Query Type Restrictions

By default, only **read-only** queries are allowed:
- ✅ `SELECT` - Query data
- ✅ `SHOW` - Show database information
- ✅ `DESCRIBE` / `DESC` - Show table structure
- ✅ `EXPLAIN` - Explain query execution

**Restricted by default:**
- ❌ `INSERT` - Insert data
- ❌ `UPDATE` - Update data
- ❌ `DELETE` - Delete data
- ❌ `DROP` - Drop tables/databases
- ❌ `ALTER` - Alter table structure
- ❌ `CREATE` - Create tables/databases
- ❌ `TRUNCATE` - Truncate tables

### Enabling Non-SELECT Queries

**⚠️ Warning:** Only enable this if you understand the risks!

```env
SQL_ALLOW_NON_SELECT=true
```

After enabling, you can execute INSERT, UPDATE, DELETE, etc.:

```bash
php artisan sql:manage "UPDATE users SET status = 'active' WHERE id = 1"
```

## 📊 Query Types

### SELECT Queries

```sql
-- Basic select
SELECT * FROM users LIMIT 10;

-- Count records
SELECT COUNT(*) as total FROM vessels;

-- Join queries
SELECT u.name, v.name as vessel_name 
FROM users u 
JOIN vessels v ON u.id = v.user_id;

-- Aggregations
SELECT status, COUNT(*) as count 
FROM users 
GROUP BY status;
```

### SHOW Queries

```sql
-- Show all tables
SHOW TABLES;

-- Show databases
SHOW DATABASES;

-- Show table status
SHOW TABLE STATUS;
```

### DESCRIBE Queries

```sql
-- Describe table structure
DESCRIBE users;

-- Alternative syntax
DESC vessels;
```

### EXPLAIN Queries

```sql
-- Explain query execution plan
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

## 🔗 Discord Integration

### Message Format

Discord messages include:
- **Status**: ✅ Success or ❌ Error
- **Row Count**: Number of rows returned
- **Execution Time**: Query execution time in seconds
- **Query**: The SQL query that was executed
- **Results Preview**: First 5 rows of results (if any)

### Example Discord Message

```
SQL Query Execution
Status: ✅ Success
Rows: 10
Execution Time: 0.05s

Query:
SELECT * FROM users LIMIT 10

Result:
Rows: 10

Preview (first 5 rows):
[
  {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  ...
]

... and 5 more rows
```

## 🏭 Production-Only Logging

By default, Discord logging **only works in production** environment.

**How it works:**
- **Default behavior**: Discord logs only work in production (`APP_ENV=production`)
- **Development mode**: Set `SQL_DISCORD_ONLY_ON_PRODUCTION=false` to enable in all environments
- If `SQL_DISCORD_ONLY_ON_PRODUCTION` is `true` and you're not in production, Discord logging is disabled

**Example Configurations:**

```env
# Production .env - Discord enabled (default)
APP_ENV=production
SQL_DISCORD_ONLY_ON_PRODUCTION=true
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# Development .env - Discord disabled (default)
APP_ENV=local
SQL_DISCORD_ONLY_ON_PRODUCTION=true  # Discord disabled in dev
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# Development .env - Discord enabled (for testing)
APP_ENV=local
SQL_DISCORD_ONLY_ON_PRODUCTION=false  # Discord enabled in dev
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
```

## 🐛 Troubleshooting

### Command not found

```bash
# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear
```

### Discord webhook not working

1. **Check webhook URL**: Verify the URL is correct and active
2. **Check environment**: Ensure `APP_ENV=production` if `SQL_DISCORD_ONLY_ON_PRODUCTION=true`
3. **Check permissions**: Ensure the webhook has permission to post in the channel
4. **Check logs**: Review `storage/logs/laravel.log` for errors

### Query execution failed

1. **Check query syntax**: Ensure SQL syntax is correct
2. **Check permissions**: Ensure database user has necessary permissions
3. **Check query type**: Only SELECT queries allowed by default
4. **Check timeout**: Queries timeout after 30 seconds by default

### Too many results

- Results are automatically truncated in Discord (first 5 rows shown)
- Console output shows all results
- Use `LIMIT` in your queries to restrict results

## 📋 Quick Reference

### Environment Variables Summary

```env
# Production Control
SQL_DISCORD_ONLY_ON_PRODUCTION=true  # true = production only, false = all environments

# Webhook URL (Required for Discord)
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# Bot Configuration (Optional)
SQL_DISCORD_WEBHOOK_USERNAME="sql-manager"
SQL_DISCORD_WEBHOOK_AVATAR_URL=

# Security (Optional)
SQL_ALLOW_NON_SELECT=false  # true = allow INSERT/UPDATE/DELETE, false = SELECT only
```

### Common Commands

```bash
# Interactive mode
php artisan sql:manage --interactive

# Single query
php artisan sql:manage "SELECT * FROM users LIMIT 10"

# Without Discord
php artisan sql:manage "SELECT COUNT(*) FROM vessels" --no-discord

# Clear config cache
php artisan config:clear
```

### Example Queries

```sql
-- Count records
SELECT COUNT(*) as total FROM users;

-- List tables
SHOW TABLES;

-- Describe table
DESCRIBE users;

-- Query with conditions
SELECT * FROM vessels WHERE status = 'active';

-- Join query
SELECT u.name, v.name as vessel 
FROM users u 
JOIN vessels v ON u.id = v.user_id;
```

## ✅ Setup Checklist

- [ ] Created Discord webhook in Discord server
- [ ] Added webhook URL to `.env` file as `SQL_DISCORD_WEBHOOK_URL`
- [ ] Set `SQL_DISCORD_ONLY_ON_PRODUCTION=true` (default, production only)
- [ ] Cleared config cache (`php artisan config:clear`)
- [ ] Tested with sample query (`php artisan sql:manage --interactive`)
- [ ] Verified results appear in console
- [ ] Verified results appear in Discord (production only)
- [ ] Reviewed security settings (`SQL_ALLOW_NON_SELECT=false` by default)
- [ ] Configured bot username if needed (`SQL_DISCORD_WEBHOOK_USERNAME`)
- [ ] Tested in production environment

## 🎯 Production Deployment

For production environments, recommended configuration:

```env
APP_ENV=production
SQL_DISCORD_ONLY_ON_PRODUCTION=true
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
SQL_DISCORD_WEBHOOK_USERNAME="sql-manager"
SQL_ALLOW_NON_SELECT=false
```

This ensures:
- ✅ SQL queries can be executed from terminal
- ✅ Results are sent to Discord in production only
- ✅ Only SELECT queries allowed (secure by default)
- ✅ No test/development queries reach production Discord

## 🔗 Related Documentation

- [Discord Logging Setup](./discord-logging-setup.md) - Application logging to Discord
- [VPS Management Setup](./vps-management-setup.md) - VPS command execution
- [Laravel Database Documentation](https://laravel.com/docs/database)

---

**Need help?** 
- Check the application logs: `storage/logs/laravel.log`
- Review the implementation: `app/Actions/SqlAction.php`
- Check the command: `app/Console/Commands/SqlManageCommand.php`

