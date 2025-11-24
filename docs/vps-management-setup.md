# VPS Management Setup Guide

This guide explains how to configure your Laravel application to execute terminal commands on your VPS and send results to Discord channels using webhooks.

## 📑 Table of Contents

- [Overview](#-overview)
- [Quick Setup](#-quick-setup)
  - [Complete .env Configuration](#complete-env-configuration)
- [Detailed Setup](#-detailed-setup)
  - [Step 1: Create Discord Webhook](#step-1-create-discord-webhook)
  - [Step 2: Environment Variables Reference](#step-2-environment-variables-reference)
  - [Step 3: Test the Integration](#step-3-test-the-integration)
- [Usage](#-usage)
- [Security Features](#-security-features)
- [Allowed Commands](#-allowed-commands)
- [Production-Only Mode](#production-only-mode)
- [Troubleshooting](#-troubleshooting)
- [Example Use Cases](#-example-use-cases)
- [Quick Reference](#-quick-reference)
- [Setup Checklist](#-setup-checklist)

## 📋 Overview

The VPS management integration allows you to:
- Execute terminal commands on your VPS server from Laravel
- Automatically send command results to Discord channels
- Monitor server status and execute maintenance commands
- Only work in production environment (by default) for security
- Whitelist-based command security to prevent dangerous operations

## 🚀 Quick Setup

### Complete .env Configuration

Here's the complete VPS management configuration for your `.env` file:

```env
# ============================================
# VPS Management Configuration
# ============================================

# Restrict VPS management to production only (default: true)
# If true: VPS commands only work in production environment
# If false: VPS commands work in all environments
VPS_ONLY_ON_PRODUCTION=true

# Discord webhook URL for VPS command results
VPS_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/1442581258008793244/zk_bj9DQuGNGdqHbjaGqDMh5FOXCDhLdsgYdF1BPB9KzBnaZAed-LbEu1i3tnhRPNhtW

# Optional: Custom username for Discord bot (default: vps-manager)
VPS_DISCORD_WEBHOOK_USERNAME="vps-manager"

# Optional: Avatar URL for Discord bot
VPS_DISCORD_WEBHOOK_AVATAR_URL=

# Optional: Comma-separated list of additional allowed commands
# Example: VPS_ALLOWED_COMMANDS="nginx,apache2,mysql"
VPS_ALLOWED_COMMANDS=
```

**Quick Start:**
1. Copy the webhook URL from your Discord server
2. Paste it into your `.env` file as `VPS_DISCORD_WEBHOOK_URL`
3. Ensure `VPS_ONLY_ON_PRODUCTION=true` (default) for security
4. Run `php artisan config:clear`
5. Test with `php artisan vps:manage "ls -la"`

## 📖 Detailed Setup

### Step 1: Create Discord Webhook

1. **Open your Discord server**
2. **Navigate to Server Settings** → **Integrations** → **Webhooks**
3. **Create a new webhook** for VPS command results
4. **Copy the webhook URL** (looks like: `https://discord.com/api/webhooks/...`)

### Step 2: Environment Variables Reference

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `VPS_DISCORD_WEBHOOK_URL` | Webhook URL for VPS command results | `https://discord.com/api/webhooks/...` |

#### Optional Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `VPS_ONLY_ON_PRODUCTION` | `true` | If `true`, only allows commands in production environment |
| `VPS_DISCORD_WEBHOOK_USERNAME` | `vps-manager` | Bot username for Discord messages |
| `VPS_DISCORD_WEBHOOK_AVATAR_URL` | `null` | Avatar URL for Discord bot |
| `VPS_ALLOWED_COMMANDS` | `null` | Comma-separated list of additional allowed commands |

**Important Notes:**
- By default, VPS management works **only in production** environment
- Set `VPS_ONLY_ON_PRODUCTION=false` to allow in all environments (not recommended)
- Commands are whitelisted for security - only safe commands are allowed by default

### Step 3: Test the Integration

Test the VPS management with a simple command:

```bash
# Single command
php artisan vps:manage "ls -la"

# Interactive mode
php artisan vps:manage --interactive
```

You should see:
1. Command execution in terminal
2. Results displayed in terminal
3. Results sent to Discord channel

## 💻 Usage

### Single Command Mode

Execute a single command and send results to Discord:

```bash
php artisan vps:manage "df -h"
php artisan vps:manage "docker ps"
php artisan vps:manage "systemctl status nginx"
```

### Interactive Mode

Run multiple commands in an interactive session:

```bash
php artisan vps:manage --interactive
```

Then enter commands one by one:
```
Enter command to execute: ls -la
Enter command to execute: df -h
Enter command to execute: exit
```

### Available Commands

The command supports both modes:

```bash
# Single command
php artisan vps:manage "your-command-here"

# Interactive mode
php artisan vps:manage --interactive
# or
php artisan vps:manage -i
```

## 🔒 Security Features

### Production-Only Mode

By default, VPS management only works in production:

```env
# Production .env - Only works in production (default)
APP_ENV=production
VPS_ONLY_ON_PRODUCTION=true

# Development .env - Won't work (default behavior)
APP_ENV=local
VPS_ONLY_ON_PRODUCTION=true  # Commands will be blocked

# Development .env - Allow in all environments (not recommended)
APP_ENV=local
VPS_ONLY_ON_PRODUCTION=false  # Commands will work
```

### Command Whitelist

Commands are whitelisted for security. Only safe commands are allowed by default:

**Default Allowed Commands:**
- System: `ls`, `pwd`, `whoami`, `date`, `uptime`
- Monitoring: `df`, `free`, `ps`, `top`, `htop`
- Services: `systemctl`, `service`, `journalctl`
- Containers: `docker`, `docker-compose`
- Development: `git`, `composer`, `php`, `artisan`
- Node: `npm`, `node`, `yarn`
- File operations: `cat`, `tail`, `head`, `grep`, `find`
- System info: `du`, `stat`, `uname`, `hostname`

**Adding Custom Commands:**

```env
# Add custom commands to whitelist
VPS_ALLOWED_COMMANDS="nginx,apache2,mysql,redis"
```

## 📊 Allowed Commands

### Default Whitelist

The following commands are allowed by default:

**System Commands:**
- `ls` - List directory contents
- `pwd` - Print working directory
- `whoami` - Display current user
- `date` - Display date and time
- `uptime` - Show system uptime

**Monitoring Commands:**
- `df` - Disk space usage
- `free` - Memory usage
- `ps` - Process status
- `top` - Display processes
- `htop` - Interactive process viewer

**Service Management:**
- `systemctl` - Systemd service manager
- `service` - Service management
- `journalctl` - Systemd journal viewer

**Container Commands:**
- `docker` - Docker CLI
- `docker-compose` - Docker Compose

**Development Tools:**
- `git` - Git version control
- `composer` - PHP dependency manager
- `php` - PHP interpreter
- `artisan` - Laravel CLI

**Node.js Tools:**
- `npm` - Node package manager
- `node` - Node.js runtime
- `yarn` - Yarn package manager

**File Operations:**
- `cat` - Display file contents
- `tail` - Display file tail
- `head` - Display file head
- `grep` - Search text
- `find` - Find files

**System Information:**
- `du` - Disk usage
- `stat` - File statistics
- `uname` - System information
- `hostname` - Hostname

### Adding Custom Commands

To add custom commands to the whitelist:

```env
# Single command
VPS_ALLOWED_COMMANDS="nginx"

# Multiple commands
VPS_ALLOWED_COMMANDS="nginx,apache2,mysql,redis"
```

**Security Note:** Only add commands you trust. Commands are executed with the same permissions as the PHP process.

## 🎯 Production-Only Mode

By default, VPS management only works in production environment for security:

**How it works:**
- **Default behavior**: Commands only work when `APP_ENV=production`
- **Restricted mode**: Set `VPS_ONLY_ON_PRODUCTION=true` (default) to only allow in production
- If `VPS_ONLY_ON_PRODUCTION` is `false`, commands work in all environments

**Example:**
```env
# Production .env - Commands work
APP_ENV=production
VPS_ONLY_ON_PRODUCTION=true
VPS_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# Development .env - Commands blocked (default)
APP_ENV=local
VPS_ONLY_ON_PRODUCTION=true  # Commands will be blocked

# Development .env - Commands work (not recommended)
APP_ENV=local
VPS_ONLY_ON_PRODUCTION=false  # Commands will work
```

## 🐛 Troubleshooting

### Command not executing

1. **Check environment**: Ensure `APP_ENV=production` if `VPS_ONLY_ON_PRODUCTION=true`
2. **Check webhook**: Verify `VPS_DISCORD_WEBHOOK_URL` is set correctly
3. **Check permissions**: Ensure PHP process has permission to execute commands
4. **Check whitelist**: Verify command is in the allowed commands list

### Command not in whitelist

If you get "Command is not allowed" error:

1. **Add to whitelist**: Set `VPS_ALLOWED_COMMANDS` in `.env`
2. **Clear config**: Run `php artisan config:clear`
3. **Retry command**: Execute the command again

### Discord webhook not working

1. **Check webhook URL**: Verify the URL is correct and active
2. **Check permissions**: Ensure webhook has permission to post in channel
3. **Check logs**: Review Laravel logs for webhook errors
4. **Test webhook**: Use Discord webhook tester to verify URL

### Timeout errors

Commands have a default timeout of 60 seconds. For long-running commands:

1. **Check command**: Ensure command completes within timeout
2. **Review output**: Check if command is hanging
3. **Split command**: Break long operations into smaller commands

## 📝 Example Use Cases

### Server Monitoring

```bash
# Check disk space
php artisan vps:manage "df -h"

# Check memory usage
php artisan vps:manage "free -h"

# Check system uptime
php artisan vps:manage "uptime"
```

### Service Management

```bash
# Check service status
php artisan vps:manage "systemctl status nginx"

# View service logs
php artisan vps:manage "journalctl -u nginx -n 50"
```

### Docker Management

```bash
# List containers
php artisan vps:manage "docker ps -a"

# Check Docker Compose status
php artisan vps:manage "docker-compose ps"
```

### Application Maintenance

```bash
# Run Laravel commands
php artisan vps:manage "php artisan queue:work --once"

# Check Git status
php artisan vps:manage "git status"

# View application logs
php artisan vps:manage "tail -n 100 storage/logs/laravel.log"
```

## 🔗 Related Documentation

- [Discord Logging Setup](./discord-logging-setup.md) - Similar Discord integration
- [Laravel Console Commands](https://laravel.com/docs/artisan) - Laravel command documentation

## 📋 Quick Reference

### Environment Variables Summary

```env
# Production Control
VPS_ONLY_ON_PRODUCTION=true  # true = production only, false = all environments

# Webhook URL (Required)
VPS_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# Bot Configuration (Optional)
VPS_DISCORD_WEBHOOK_USERNAME="vps-manager"
VPS_DISCORD_WEBHOOK_AVATAR_URL=

# Security (Optional)
VPS_ALLOWED_COMMANDS="nginx,apache2,mysql"
```

### Common Commands

```bash
# Single command execution
php artisan vps:manage "your-command"

# Interactive mode
php artisan vps:manage --interactive

# Clear config cache after .env changes
php artisan config:clear

# Check if command is available
php artisan list | grep vps
```

### Command Examples

```bash
# System information
php artisan vps:manage "uname -a"
php artisan vps:manage "hostname"
php artisan vps:manage "whoami"

# Disk and memory
php artisan vps:manage "df -h"
php artisan vps:manage "free -h"
php artisan vps:manage "du -sh /var/www"

# Services
php artisan vps:manage "systemctl status nginx"
php artisan vps:manage "systemctl list-units --type=service"

# Docker
php artisan vps:manage "docker ps"
php artisan vps:manage "docker-compose ps"

# Application
php artisan vps:manage "php artisan queue:status"
php artisan vps:manage "tail -n 50 storage/logs/laravel.log"
```

## ✅ Setup Checklist

- [ ] Created Discord webhook in Discord server
- [ ] Added webhook URL to `.env` file as `VPS_DISCORD_WEBHOOK_URL`
- [ ] Set `VPS_ONLY_ON_PRODUCTION=true` (default) for security
- [ ] Configured `VPS_DISCORD_WEBHOOK_USERNAME` if needed (optional)
- [ ] Added custom commands to `VPS_ALLOWED_COMMANDS` if needed (optional)
- [ ] Cleared config cache (`php artisan config:clear`)
- [ ] Tested with sample command (`php artisan vps:manage "ls -la"`)
- [ ] Verified results appear in Discord channel
- [ ] Tested interactive mode (`php artisan vps:manage --interactive`)
- [ ] Reviewed security considerations
- [ ] Verified production-only mode works correctly

## 🎯 Production Deployment

For production environments, recommended configuration:

```env
APP_ENV=production
VPS_ONLY_ON_PRODUCTION=true
VPS_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
VPS_DISCORD_WEBHOOK_USERNAME="vps-manager"
VPS_ALLOWED_COMMANDS="nginx,apache2,mysql,docker"
```

This ensures:
- ✅ Commands only work in production
- ✅ Results are sent to Discord
- ✅ Only whitelisted commands are allowed
- ✅ Custom commands are configured if needed

---

**Need help?** 
- Check the application logs: `storage/logs/laravel.log`
- Review the implementation: `app/Actions/VpsAction.php`
- Review the command: `app/Console/Commands/VpsManageCommand.php`

