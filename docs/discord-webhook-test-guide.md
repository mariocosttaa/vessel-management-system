# Discord Webhook Test Guide

This guide explains how to use the Discord webhook test command to verify all Discord integrations are configured correctly.

## 📑 Table of Contents

- [Overview](#-overview)
- [Quick Start](#-quick-start)
- [Usage](#-usage)
- [Test Results](#-test-results)
- [Troubleshooting](#-troubleshooting)
- [Quick Reference](#-quick-reference)

## 📋 Overview

The Discord webhook test command allows you to:
- Test all three Discord webhook configurations (VPS, Tinker, SQL)
- Verify webhook URLs are configured correctly
- Check environment restrictions
- Send test messages to Discord channels
- Get recommendations for fixing configuration issues

## 🚀 Quick Start

Run the test command:

```bash
php artisan discord:test
```

The command will:
1. Test VPS Discord webhook configuration
2. Test Tinker Discord webhook configuration
3. Test SQL Discord webhook configuration
4. Display a summary with status for each service
5. Provide recommendations for any issues found

## 💻 Usage

### Basic Test

```bash
php artisan discord:test
```

### What Gets Tested

For each Discord integration, the command checks:

1. **Configuration Check**
   - Verifies webhook URL is set in `.env`
   - Checks if username is configured

2. **Environment Check**
   - Verifies if the integration is enabled in current environment
   - Checks production-only restrictions

3. **Webhook Test**
   - Sends a test message to Discord
   - Verifies the message was received successfully

## 📊 Test Results

### Status Indicators

The test results show:

- **✅ Working**: Webhook is configured, enabled, and test message sent successfully
- **⚠️ Disabled in this environment**: Webhook is configured but disabled due to environment restrictions
- **⚠️ Configured but test failed**: Webhook is configured but test message failed to send
- **❌ Not configured**: Webhook URL is not set in `.env`

### Example Output

```
═══════════════════════════════════════════════════════════
        Discord Webhook Configuration Test
═══════════════════════════════════════════════════════════

🔧 Testing VPS Discord Webhook...

  ✅ Webhook URL configured
     URL: https://discord.com/api/webhooks/...
     Username: vps-manager
  ✅ Environment check passed
  📤 Sending test message...
  ✅ Test message sent successfully!

═══════════════════════════════════════════════════════════
                    Test Summary
═══════════════════════════════════════════════════════════

+----------------+------------+---------+-----------+---------------------------------+
| Service        | Configured | Enabled | Test Sent | Status                          |
+----------------+------------+---------+-----------+---------------------------------+
| VPS Management | ✅ Yes     | ✅ Yes  | ✅ Yes    | ✅ Working                      |
| Tinker Discord | ✅ Yes     | ⚠️  No   | ❌ No     | ⚠️  Disabled in this environment |
| SQL Management | ✅ Yes     | ✅ Yes  | ✅ Yes    | ✅ Working                      |
+----------------+------------+---------+-----------+---------------------------------+

Environment Information:
  Current Environment: local
  Is Production: No

Recommendations:
  • Tinker Discord: Set TINKER_ONLY_ON_PRODUCTION=false to enable in this environment
```

## 🐛 Troubleshooting

### Webhook Not Configured

If you see "Webhook URL not configured":

1. **Check `.env` file**: Ensure the webhook URL is set
   - `VPS_DISCORD_WEBHOOK_URL` for VPS management
   - `TINKER_DISCORD_WEBHOOK_URL` for Tinker Discord
   - `SQL_DISCORD_WEBHOOK_URL` for SQL management

2. **Clear config cache**: Run `php artisan config:clear`

3. **Verify webhook URL**: Check that the URL is correct and active

### Disabled in This Environment

If you see "Disabled in this environment":

1. **Check environment**: Verify `APP_ENV` in `.env`
2. **Check production-only setting**:
   - `VPS_ONLY_ON_PRODUCTION` for VPS management
   - `TINKER_ONLY_ON_PRODUCTION` for Tinker Discord
   - `SQL_DISCORD_ONLY_ON_PRODUCTION` for SQL management

3. **Enable in development** (if needed):
   ```env
   VPS_ONLY_ON_PRODUCTION=false
   TINKER_ONLY_ON_PRODUCTION=false
   SQL_DISCORD_ONLY_ON_PRODUCTION=false
   ```

### Test Message Failed

If test message fails to send:

1. **Check webhook URL**: Verify the URL is correct and active
2. **Check Discord permissions**: Ensure webhook has permission to post in channel
3. **Check network**: Verify server can reach Discord API
4. **Check logs**: Review `storage/logs/laravel.log` for errors

## 📋 Quick Reference

### Command

```bash
# Test all Discord webhooks
php artisan discord:test
```

### Environment Variables

```env
# VPS Management
VPS_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
VPS_ONLY_ON_PRODUCTION=true
VPS_DISCORD_WEBHOOK_USERNAME="vps-manager"

# Tinker Discord
TINKER_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
TINKER_ONLY_ON_PRODUCTION=true
TINKER_DISCORD_WEBHOOK_USERNAME="tinker-manager"

# SQL Management
SQL_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
SQL_DISCORD_ONLY_ON_PRODUCTION=true
SQL_DISCORD_WEBHOOK_USERNAME="sql-manager"
```

### Test Checklist

- [ ] Run `php artisan discord:test`
- [ ] Verify all webhooks are configured
- [ ] Check environment restrictions
- [ ] Verify test messages appear in Discord
- [ ] Fix any configuration issues
- [ ] Re-run test to confirm fixes

## 🔗 Related Documentation

- [VPS Management Setup](./vps-management-setup.md) - VPS Discord integration
- [Tinker Discord Setup](./tinker-discord-setup.md) - Tinker Discord integration
- [SQL Management Setup](./sql-management-setup.md) - SQL Discord integration
- [Discord Logging Setup](./discord-logging-setup.md) - Application logging to Discord

---

**Need help?** 
- Check the application logs: `storage/logs/laravel.log`
- Review the command: `app/Console/Commands/TestDiscordWebhooksCommand.php`
- Review individual action classes: `app/Actions/VpsAction.php`, `app/Actions/TinkerAction.php`, `app/Actions/SqlAction.php`

