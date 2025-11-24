# SQL Management Quick Reference

Quick reference guide for the SQL management command.

## 🚀 Quick Start

```bash
# Interactive mode
php artisan sql:manage --interactive

# Single query (input and output sent to Discord)
php artisan sql:manage "SELECT * FROM users LIMIT 10"

# Without Discord
php artisan sql:manage "SELECT COUNT(*) FROM vessels" --no-discord
```

**Note:** Both the SQL query (input) and results (output) are sent to Discord, so you can see what query was executed and its results.

## ⚙️ Environment Variables

```env
# Required for Discord (production only by default)
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/1442582149772476519/TcRHFaDEWWl0zzCMLlHUIvigDxtoR6X07gizTXIZnJHXuvZ9Zcbtc02BNN8sJ4c-HMwN

# Production control (default: true = production only)
SQL_DISCORD_ONLY_ON_PRODUCTION=true

# Optional
SQL_DISCORD_WEBHOOK_USERNAME="sql-manager"
SQL_DISCORD_WEBHOOK_AVATAR_URL=
SQL_ALLOW_NON_SELECT=false
```

## 📝 Common Queries

```sql
-- Count records
SELECT COUNT(*) as total FROM users;

-- List tables
SHOW TABLES;

-- Describe table
DESCRIBE users;

-- Query with limit
SELECT * FROM vessels LIMIT 10;

-- Join query
SELECT u.name, v.name as vessel 
FROM users u 
JOIN vessels v ON u.id = v.user_id;
```

## 🔒 Security

- **Default**: Only SELECT, SHOW, DESCRIBE, EXPLAIN allowed
- **To allow other queries**: Set `SQL_ALLOW_NON_SELECT=true` in `.env`
- **Discord**: Only works in production by default

## 📚 Full Documentation

See [SQL Management Setup Guide](./sql-management-setup.md) for complete documentation.

