# Discord Logging Setup Guide

This guide explains how to configure your Laravel application to send logs to Discord channels using webhooks.

## 📑 Table of Contents

- [Overview](#-overview)
- [Quick Setup](#-quick-setup)
  - [Complete .env Configuration](#complete-env-configuration)
- [Detailed Setup](#-detailed-setup)
  - [Step 1: Create Discord Webhooks](#step-1-create-discord-webhooks)
  - [Step 2: Environment Variables Reference](#step-2-environment-variables-reference)
  - [Step 3: Configure Log Channels](#step-3-configure-log-channels)
  - [Step 4: Test the Integration](#step-4-test-the-integration)
- [Available Log Channels](#-available-log-channels)
- [Log Message Format](#-log-message-format)
- [Advanced Configuration](#-advanced-configuration)
- [Production-Only Logging](#production-only-logging)
- [Security Considerations](#️-security-considerations)
- [Troubleshooting](#-troubleshooting)
- [Example Use Cases](#-example-use-cases)
- [Quick Reference](#-quick-reference)
- [Setup Checklist](#-setup-checklist)
- [Production Deployment](#-production-deployment)

## 📋 Overview

The Discord logging integration allows you to:
- Send application logs to Discord channels in real-time
- Configure different channels for different log levels (info, errors, critical)
- Receive formatted log messages with context information
- Automatically filter sensitive data (passwords, tokens, etc.)
- Control whether logs are sent in all environments or production only

## 🚀 Quick Setup

### Complete .env Configuration

Here's the complete Discord logging configuration for your `.env` file:

```env
# ============================================
# Discord Logging Configuration
# ============================================

# Restrict Discord logging to production only (optional)
# If true: Discord logs only work in production environment
# If false or not set: Discord logs work in all environments (default behavior)
DISCORD_LOGS_ONLY_ON_PRODUCTION=false

# Main Discord webhook (for general logs - log-general channel)
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/1442573026179944571/vKj5aGzbedJDp5rJs0U3Eyq4sRjatwu4mgd96CUubKbJvcL-jnNlrjyQ78AfxvREYhrB

# Optional: Custom username for Discord bot (default: log-general-manager)
DISCORD_WEBHOOK_USERNAME="log-general-manager"

# Optional: Avatar URL for Discord bot
DISCORD_WEBHOOK_AVATAR_URL=

# Separate webhook for errors (log-error channel)
DISCORD_ERRORS_WEBHOOK_URL=https://discord.com/api/webhooks/1442572620154409137/qjc1q42LMhPH-ZRVoB4o1RtXdpxsyOMR2pvIvhl4fPBTwN0RTkaWXNRlE4sfKQVjFjkV

# Optional: Custom username for errors bot (default: log-error-manager)
DISCORD_ERRORS_WEBHOOK_USERNAME="log-error-manager"

# Separate webhook for critical logs (log-critical channel)
DISCORD_CRITICAL_WEBHOOK_URL=https://discord.com/api/webhooks/1442573365159133274/-lfJPIKK5C_0BtyqOrJZwvk2v7sofg_7w2h9cxMrPaOp2gBu1ItPGIgioIaOIPO2C9MR

# Optional: Custom username for critical bot (default: log-critical-manager)
DISCORD_CRITICAL_WEBHOOK_USERNAME="log-critical-manager"

# Log levels (optional, defaults shown)
DISCORD_LOG_LEVEL=info
DISCORD_ERRORS_LOG_LEVEL=error
DISCORD_CRITICAL_LOG_LEVEL=critical

# Include context data in logs (default: true)
DISCORD_INCLUDE_CONTEXT=true

# Maximum message length (default: 2000, Discord limit is 2000)
DISCORD_MAX_MESSAGE_LENGTH=2000

# Configure log stack to include all Discord channels
LOG_STACK=single,discord,discord-errors,discord-critical
```

**Quick Start:**
1. Copy the webhook URLs from your Discord server
2. Paste them into your `.env` file
3. Set `LOG_STACK=single,discord,discord-errors,discord-critical`
4. Run `php artisan config:clear`
5. Test with `php artisan tinker` → `Log::info('Test')`

## 📖 Detailed Setup

### Step 1: Create Discord Webhooks

1. **Open your Discord server**
2. **Navigate to Server Settings** → **Integrations** → **Webhooks**
3. **Create a new webhook** for each channel you want to use:
   - **Logs Channel**: General application logs (info level and above)
   - **Errors Channel**: Error-level logs only
   - **Critical Channel**: Critical and emergency logs only

4. **Copy the webhook URL** for each channel (looks like: `https://discord.com/api/webhooks/...`)

### Step 2: Environment Variables Reference

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DISCORD_WEBHOOK_URL` | Main webhook URL for general logs | `https://discord.com/api/webhooks/...` |
| `DISCORD_ERRORS_WEBHOOK_URL` | Webhook URL for error logs | `https://discord.com/api/webhooks/...` |
| `DISCORD_CRITICAL_WEBHOOK_URL` | Webhook URL for critical logs | `https://discord.com/api/webhooks/...` |
| `LOG_STACK` | Log channels to use | `single,discord,discord-errors,discord-critical` |

#### Optional Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DISCORD_LOGS_ONLY_ON_PRODUCTION` | `false` | If `true`, only sends logs in production environment |
| `DISCORD_WEBHOOK_USERNAME` | `log-general-manager` | Bot username for general logs |
| `DISCORD_ERRORS_WEBHOOK_USERNAME` | `log-error-manager` | Bot username for error logs |
| `DISCORD_CRITICAL_WEBHOOK_USERNAME` | `log-critical-manager` | Bot username for critical logs |
| `DISCORD_WEBHOOK_AVATAR_URL` | `null` | Avatar URL for Discord bots |
| `DISCORD_LOG_LEVEL` | `info` | Minimum log level for general channel |
| `DISCORD_ERRORS_LOG_LEVEL` | `error` | Minimum log level for errors channel |
| `DISCORD_CRITICAL_LOG_LEVEL` | `critical` | Minimum log level for critical channel |
| `DISCORD_INCLUDE_CONTEXT` | `true` | Include context data in log messages |
| `DISCORD_MAX_MESSAGE_LENGTH` | `2000` | Maximum message length (Discord limit) |

**Important Notes:**
- By default, Discord logging works in **all environments** (development, staging, production)
- Set `DISCORD_LOGS_ONLY_ON_PRODUCTION=true` to restrict logging to production only
- If `DISCORD_LOGS_ONLY_ON_PRODUCTION` is not set or `false`, logs will be sent in all environments
- This allows you to test Discord logging in development, but restrict it to production when needed

### Step 3: Configure Log Channels

Update your `LOG_STACK` environment variable to include all Discord channels:

```env
# Send logs to file and all Discord channels
LOG_STACK=single,discord,discord-errors,discord-critical
```

This configuration will:
- Write all logs to file (`single`)
- Send info+ logs to Discord general channel (`discord`)
- Send error+ logs to Discord errors channel (`discord-errors`)
- Send critical+ logs to Discord critical channel (`discord-critical`)

### Step 4: Test the Integration

Test the Discord logging with a simple command:

```bash
php artisan tinker
```

Then run:

```php
Log::info('Test message from Laravel', ['test' => true]);
Log::error('Test error message', ['error_code' => 500]);
Log::critical('Test critical message', ['action' => 'test']);
```

You should see messages appear in your Discord channels!

## 📊 Available Log Channels

### `discord`
- **Level**: `info` (configurable via `DISCORD_LOG_LEVEL`)
- **Purpose**: General application logs
- **Use Case**: All informational logs, warnings, and above

### `discord-errors`
- **Level**: `error` (configurable via `DISCORD_ERRORS_LOG_LEVEL`)
- **Purpose**: Error-level logs only
- **Use Case**: Application errors that need attention

### `discord-critical`
- **Level**: `critical` (configurable via `DISCORD_CRITICAL_LOG_LEVEL`)
- **Purpose**: Critical and emergency logs only
- **Use Case**: System-critical issues requiring immediate attention

## 🎨 Log Message Format

Discord logs are sent as rich embeds with:

- **Color coding** by log level:
  - 🔵 Blue: Info, Notice
  - 🟠 Orange: Warning
  - 🔴 Red: Error, Critical
  - 🟣 Purple: Alert
  - ⚫ Dark Red: Emergency
  - ⚪ Gray: Debug

- **Structured information**:
  - Log level and message
  - Timestamp
  - Context data (if enabled)
  - Important fields (user_id, vessel_id, IP, etc.)

- **Automatic filtering** of sensitive data:
  - Passwords
  - Tokens
  - API keys
  - Secrets

## 🔧 Advanced Configuration

### Using Discord Logs in Code

You can log directly to Discord channels:

```php
use Illuminate\Support\Facades\Log;

// Log to main Discord channel
Log::channel('discord')->info('User logged in', ['user_id' => 1]);

// Log to errors channel
Log::channel('discord-errors')->error('Database connection failed', [
    'host' => 'localhost',
    'database' => 'vessel_db'
]);

// Log to critical channel
Log::channel('discord-critical')->critical('Payment processing failed', [
    'transaction_id' => 12345,
    'amount' => 1000
]);
```

### Stack Configuration

You can combine multiple channels in a stack:

```env
LOG_STACK=single,discord,discord-errors
```

This will:
1. Write to file (`single`)
2. Send info+ logs to Discord (`discord`)
3. Send error+ logs to Discord errors channel (`discord-errors`)

### Production-Only Logging

By default, Discord logging works in **all environments**. To restrict it to production only:

1. Set `DISCORD_LOGS_ONLY_ON_PRODUCTION=true` in your `.env` file
2. Set `APP_ENV=production` in your production environment
3. Discord channels will be automatically filtered from the log stack in non-production environments

**How it works:**
- **Default behavior**: Discord logs work in all environments (development, staging, production)
- **Restricted mode**: Set `DISCORD_LOGS_ONLY_ON_PRODUCTION=true` to only send logs in production
- If `DISCORD_LOGS_ONLY_ON_PRODUCTION` is `false` or not set, logs are sent everywhere
- File logging continues to work in all environments regardless of this setting

**Example:**
```env
# Production .env - Restrict to production only
APP_ENV=production
DISCORD_LOGS_ONLY_ON_PRODUCTION=true
LOG_STACK=single,discord,discord-errors,discord-critical

# Development .env - Logs work everywhere (default)
APP_ENV=local
# DISCORD_LOGS_ONLY_ON_PRODUCTION not set (defaults to false)
LOG_STACK=single,discord,discord-errors,discord-critical  # Discord channels work in dev too

# Development .env - Explicitly allow all environments
APP_ENV=local
DISCORD_LOGS_ONLY_ON_PRODUCTION=false
LOG_STACK=single,discord,discord-errors,discord-critical  # Discord channels work
```

## 🛡️ Security Considerations

1. **Webhook URLs are sensitive**: Never commit them to version control
2. **Use environment variables**: Always store webhooks in `.env` file
3. **Sensitive data filtering**: The handler automatically filters passwords, tokens, and secrets
4. **Rate limiting**: Discord webhooks have rate limits (30 requests per second)
5. **Error handling**: Failed webhook requests won't break your application

## 🐛 Troubleshooting

### Logs not appearing in Discord

1. **Check webhook URL**: Verify the URL is correct and active
2. **Check log level**: Ensure your log level matches the channel configuration
3. **Check environment**: Make sure `.env` variables are loaded (`php artisan config:clear`)
4. **Check permissions**: Ensure the webhook has permission to post in the channel

### Too many messages

1. **Increase log level**: Set `DISCORD_LOG_LEVEL=warning` or `error`
2. **Use separate channels**: Use `discord-errors` or `discord-critical` only
3. **Filter in code**: Only log important events to Discord

### Message too long

1. **Reduce context**: Set `DISCORD_INCLUDE_CONTEXT=false`
2. **Reduce max length**: Set `DISCORD_MAX_MESSAGE_LENGTH=1000`
3. **Filter context**: Only include important fields in your log context

## 📝 Example Use Cases

### Production Error Monitoring

```env
LOG_STACK=daily,discord-errors
DISCORD_ERRORS_LOG_LEVEL=error
```

### Development Debugging

```env
LOG_STACK=single,discord
DISCORD_LOG_LEVEL=debug
DISCORD_INCLUDE_CONTEXT=true
```

### Critical Alerts Only

```env
LOG_STACK=single,discord-critical
DISCORD_CRITICAL_LOG_LEVEL=critical
```

## 🔗 Related Documentation

- [Laravel Logging Documentation](https://laravel.com/docs/logging)
- [Discord Webhooks Documentation](https://discord.com/developers/docs/resources/webhook)
- [Monolog Documentation](https://github.com/Seldaek/monolog)

## 📋 Quick Reference

### Environment Variables Summary

```env
# Production Control
DISCORD_LOGS_ONLY_ON_PRODUCTION=false  # true = production only, false = all environments

# Webhook URLs (Required)
DISCORD_WEBHOOK_URL=...
DISCORD_ERRORS_WEBHOOK_URL=...
DISCORD_CRITICAL_WEBHOOK_URL=...

# Bot Names (Optional)
DISCORD_WEBHOOK_USERNAME="log-general-manager"
DISCORD_ERRORS_WEBHOOK_USERNAME="log-error-manager"
DISCORD_CRITICAL_WEBHOOK_USERNAME="log-critical-manager"

# Log Levels (Optional)
DISCORD_LOG_LEVEL=info
DISCORD_ERRORS_LOG_LEVEL=error
DISCORD_CRITICAL_LOG_LEVEL=critical

# Configuration (Optional)
DISCORD_INCLUDE_CONTEXT=true
DISCORD_MAX_MESSAGE_LENGTH=2000

# Log Stack (Required)
LOG_STACK=single,discord,discord-errors,discord-critical
```

### Common Commands

```bash
# Clear config cache after .env changes
php artisan config:clear

# Test Discord logging
php artisan tinker
# Then run: Log::info('Test message');

# Check current configuration
php artisan config:show logging.channels.discord
```

## ✅ Setup Checklist

- [ ] Created Discord webhook(s) in Discord server
- [ ] Added webhook URL(s) to `.env` file
- [ ] Configured `LOG_STACK` to include Discord channels
- [ ] Set `DISCORD_LOGS_ONLY_ON_PRODUCTION` if needed
- [ ] Cleared config cache (`php artisan config:clear`)
- [ ] Tested with sample log messages
- [ ] Verified logs appear in Discord channels
- [ ] Configured appropriate log levels
- [ ] Set up separate channels for errors/critical (optional)
- [ ] Reviewed security considerations
- [ ] Verified bot names appear correctly in Discord

## 🎯 Production Deployment

For production environments, recommended configuration:

```env
APP_ENV=production
DISCORD_LOGS_ONLY_ON_PRODUCTION=true
LOG_STACK=daily,discord,discord-errors,discord-critical
DISCORD_LOG_LEVEL=info
DISCORD_ERRORS_LOG_LEVEL=error
DISCORD_CRITICAL_LOG_LEVEL=critical
```

This ensures:
- ✅ Logs are written to daily files
- ✅ Discord logs only work in production
- ✅ Different log levels go to appropriate channels
- ✅ No test/development logs reach production Discord

---

**Need help?** 
- Check the application logs: `storage/logs/laravel.log`
- Review the implementation: `app/Logging/DiscordWebhookHandler.php`
- Check configuration: `config/logging.php`

