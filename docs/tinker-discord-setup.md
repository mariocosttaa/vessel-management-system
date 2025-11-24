# Tinker Discord Integration Setup Guide

This guide explains how to configure Laravel Tinker to send execution results to Discord channels using webhooks.

## 📑 Table of Contents

- [Overview](#-overview)
- [Quick Setup](#-quick-setup)
  - [Complete .env Configuration](#complete-env-configuration)
- [Detailed Setup](#-detailed-setup)
  - [Step 1: Create Discord Webhook](#step-1-create-discord-webhook)
  - [Step 2: Environment Variables Reference](#step-2-environment-variables-reference)
  - [Step 3: Test the Integration](#step-3-test-the-integration)
- [Usage](#-usage)
  - [Single Command Mode](#single-command-mode)
  - [Interactive Mode](#interactive-mode)
- [Message Format](#-message-format)
- [Production-Only Execution](#production-only-execution)
- [Security Considerations](#️-security-considerations)
- [Troubleshooting](#-troubleshooting)
- [Quick Reference](#-quick-reference)
- [Setup Checklist](#-setup-checklist)

## 📋 Overview

The Tinker Discord integration allows you to:
- Execute PHP code via Laravel Tinker and send results to Discord
- Monitor code execution in real-time through Discord channels
- View formatted output with execution time and status
- Control whether execution works in all environments or production only
- Execute code interactively or via single commands

## 🚀 Quick Setup

### Complete .env Configuration

Here's the complete Tinker Discord configuration for your `.env` file:

```env
# ============================================
# Tinker Discord Integration Configuration
# ============================================

# Restrict Tinker Discord to production only (optional)
# If true: Tinker Discord only works in production environment
# If false or not set: Tinker Discord works in all environments (default behavior)
TINKER_ONLY_ON_PRODUCTION=true

# Discord webhook URL for Tinker execution results
TINKER_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/1442582556028440857/9cpx3w_lMdqp7N3OWfyctV--ri2gxpH9kdxtiyQJcKnVzJ6-n1SafSrKhoR5we5irbob

# Optional: Custom username for Discord bot (default: tinker-manager)
TINKER_DISCORD_WEBHOOK_USERNAME="tinker-manager"

# Optional: Avatar URL for Discord bot
TINKER_DISCORD_WEBHOOK_AVATAR_URL=
```

**Quick Start:**
1. Copy the webhook URL from your Discord server
2. Paste it into your `.env` file as `TINKER_DISCORD_WEBHOOK_URL`
3. Set `TINKER_ONLY_ON_PRODUCTION=true` for production-only execution
4. Run `php artisan config:clear`
5. Test with `php artisan tinker:discord "echo 'Hello World';"`

## 📖 Detailed Setup

### Step 1: Create Discord Webhook

1. **Open your Discord server**
2. **Navigate to Server Settings** → **Integrations** → **Webhooks**
3. **Create a new webhook** for the channel where you want to receive Tinker execution results
4. **Copy the webhook URL** (looks like: `https://discord.com/api/webhooks/...`)

### Step 2: Environment Variables Reference

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `TINKER_DISCORD_WEBHOOK_URL` | Webhook URL for Tinker execution results | `https://discord.com/api/webhooks/...` |

#### Optional Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `TINKER_ONLY_ON_PRODUCTION` | `true` | If `true`, only allows execution in production environment |
| `TINKER_DISCORD_WEBHOOK_USERNAME` | `tinker-manager` | Bot username for Discord messages |
| `TINKER_DISCORD_WEBHOOK_AVATAR_URL` | `null` | Avatar URL for Discord bot |

**Important Notes:**
- By default, Tinker Discord integration works **only in production** (`TINKER_ONLY_ON_PRODUCTION=true`)
- Set `TINKER_ONLY_ON_PRODUCTION=false` to allow execution in all environments
- This allows you to test Tinker Discord in development, but restrict it to production when needed

### Step 3: Test the Integration

Test the Tinker Discord integration with a simple command:

```bash
php artisan tinker:discord "echo 'Hello from Tinker!';"
```

You should see:
1. The code execution output in your terminal
2. A message in your Discord channel with the code and results

## 💻 Usage

### Single Command Mode

Execute a single PHP code snippet. **Both the code (input) and output are sent to Discord:**

```bash
php artisan tinker:discord "User::count()"
```

```bash
php artisan tinker:discord "DB::table('users')->count()"
```

```bash
php artisan tinker:discord "Log::info('Test message')"
```

**Important:** The PHP code you execute (input) will always be displayed in the Discord message along with the execution results. This allows you to see what code was run and its output.

### Interactive Mode

Run Tinker in interactive mode to execute multiple commands:

```bash
php artisan tinker:discord --interactive
```

Or simply:

```bash
php artisan tinker:discord
```

In interactive mode:
- Enter PHP code to execute
- Results are displayed and sent to Discord
- Type `exit`, `quit`, or `q` to exit

**Example Interactive Session:**

```
═══════════════════════════════════════════════════════════
           Laravel Tinker - Discord Integration
═══════════════════════════════════════════════════════════

PHP code will be executed and results sent to Discord.
Type "exit" or "quit" to exit.

Enter PHP code to execute: User::count()
Executing PHP code...

42

Status: Success
Execution Time: 0.05s
Sending results to Discord...
✅ Results sent to Discord successfully.

Enter PHP code to execute: exit
Goodbye!
```

## 🎨 Message Format

Discord messages are sent as rich embeds with:

- **Color coding** by execution status:
  - 🟢 Green: Successful execution
  - 🔴 Red: Error during execution

- **Structured information**:
  - Execution status (Success/Error)
  - Execution time
  - PHP code that was executed
  - Output/result from execution
  - Timestamp

**Example Discord Message:**

```
┌─────────────────────────────────────┐
│ Laravel Tinker Execution            │
├─────────────────────────────────────┤
│ Status: ✅ Success                  │
│ Execution Time: 0.05s               │
│                                     │
│ Code:                               │
│ ```php                              │
│ User::count()                       │
│ ```                                 │
│                                     │
│ Output:                             │
│ ```                                 │
│ 42                                  │
│ ```                                 │
└─────────────────────────────────────┘
```

## 🔒 Production-Only Execution

By default, Tinker Discord integration works **only in production**. To change this behavior:

1. Set `TINKER_ONLY_ON_PRODUCTION=false` in your `.env` file
2. Set `APP_ENV=production` in your production environment
3. Tinker Discord will be automatically restricted based on environment

**How it works:**
- **Default behavior**: Tinker Discord only works in production (`TINKER_ONLY_ON_PRODUCTION=true`)
- **Unrestricted mode**: Set `TINKER_ONLY_ON_PRODUCTION=false` to allow execution in all environments
- If `TINKER_ONLY_ON_PRODUCTION` is `true`, execution is blocked in non-production environments

**Example:**
```env
# Production .env - Restrict to production only (default)
APP_ENV=production
TINKER_ONLY_ON_PRODUCTION=true
TINKER_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# Development .env - Allow in all environments
APP_ENV=local
TINKER_ONLY_ON_PRODUCTION=false
TINKER_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
```

## 🛡️ Security Considerations

1. **Webhook URLs are sensitive**: Never commit them to version control
2. **Use environment variables**: Always store webhooks in `.env` file
3. **Production-only by default**: The integration is restricted to production by default
4. **Code execution**: Be careful with code you execute - it runs with full application permissions
5. **Error handling**: Failed webhook requests won't break your application
6. **Output truncation**: Long outputs are automatically truncated to fit Discord's limits

## 🐛 Troubleshooting

### Command not found

If you get `Command "tinker:discord" is not defined`:

1. Clear the command cache: `php artisan config:clear`
2. Check that the command file exists: `app/Console/Commands/TinkerCommand.php`
3. Ensure Laravel can autoload the command (should be automatic)

### Execution blocked in development

If you see "Tinker Discord integration is only available in production environment":

1. Set `TINKER_ONLY_ON_PRODUCTION=false` in your `.env` file
2. Run `php artisan config:clear`
3. Try the command again

### Webhook not configured

If you see "Tinker Discord webhook URL is not configured":

1. Add `TINKER_DISCORD_WEBHOOK_URL` to your `.env` file
2. Run `php artisan config:clear`
3. Try the command again

### Messages not appearing in Discord

1. **Test webhook configuration**: Run `php artisan discord:test` to test all Discord webhooks
2. **Check webhook URL**: Verify the URL is correct and active
3. **Check environment**: Make sure `.env` variables are loaded (`php artisan config:clear`)
4. **Check permissions**: Ensure the webhook has permission to post in the channel
5. **Check logs**: Review `storage/logs/laravel.log` for errors

### Code execution errors

If code execution fails:

1. Check the error message in the terminal output
2. Verify the PHP code syntax is correct
3. Ensure all required classes/facades are available
4. Check application logs for detailed error information

## 📋 Quick Reference

### Environment Variables Summary

```env
# Production Control
TINKER_ONLY_ON_PRODUCTION=true  # true = production only, false = all environments

# Webhook URL (Required)
TINKER_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# Bot Name (Optional)
TINKER_DISCORD_WEBHOOK_USERNAME="tinker-manager"

# Avatar URL (Optional)
TINKER_DISCORD_WEBHOOK_AVATAR_URL=
```

### Common Commands

```bash
# Execute single command
php artisan tinker:discord "User::count()"

# Interactive mode
php artisan tinker:discord --interactive

# Or simply
php artisan tinker:discord

# Clear config cache after .env changes
php artisan config:clear
```

### Example Code Snippets

```php
# Count users
User::count()

# Get all users
User::all()

# Create a user
User::create(['name' => 'Test', 'email' => 'test@example.com'])

# Database query
DB::table('users')->count()

# Log a message
Log::info('Test message from Tinker')

# Get configuration
config('app.name')

# Get environment
app()->environment()
```

## ✅ Setup Checklist

- [ ] Created Discord webhook in Discord server
- [ ] Added webhook URL to `.env` file as `TINKER_DISCORD_WEBHOOK_URL`
- [ ] Set `TINKER_ONLY_ON_PRODUCTION` if needed (default: `true`)
- [ ] Cleared config cache (`php artisan config:clear`)
- [ ] Tested with sample code execution
- [ ] Verified messages appear in Discord channel
- [ ] Configured bot username if needed (optional)
- [ ] Reviewed security considerations
- [ ] Verified production-only restriction works correctly

## 🎯 Production Deployment

For production environments, recommended configuration:

```env
APP_ENV=production
TINKER_ONLY_ON_PRODUCTION=true
TINKER_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
TINKER_DISCORD_WEBHOOK_USERNAME="tinker-manager"
```

This ensures:
- ✅ Tinker Discord only works in production
- ✅ Execution results are sent to Discord
- ✅ No test/development executions reach production Discord
- ✅ Proper bot identification in Discord

## 🔗 Related Documentation

- [Discord Webhook Test Guide](./discord-webhook-test-guide.md) - Test all Discord webhook configurations
- [VPS Management Setup](./vps-management-setup.md) - VPS command execution
- [SQL Management Setup](./sql-management-setup.md) - SQL query execution

---

**Need help?** 
- Test webhook configuration: `php artisan discord:test`
- Check the application logs: `storage/logs/laravel.log`
- Review the implementation: `app/Actions/TinkerAction.php`
- Check the command: `app/Console/Commands/TinkerCommand.php`

