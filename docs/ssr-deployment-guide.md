# SSR Deployment Guide

This guide documents the Server-Side Rendering (SSR) deployment configuration for the Vessel Management System.

## Overview

The application uses Inertia.js with Vue 3 SSR to pre-render pages on the server for improved SEO, faster initial page loads, and better social media sharing.

## Docker Configuration

### Build Process

The Dockerfile has been configured to:

1. **Build SSR Bundle**: Uses `npm run build:ssr` to build both client and server bundles
2. **Create SSR Directory**: Creates `/var/www/html/bootstrap/ssr` with proper permissions
3. **Start SSR Server**: Automatically starts the SSR server via Supervisor

### Supervisor Configuration

The SSR server is managed by Supervisor alongside other services:

- **nginx**: Web server
- **php-fpm**: PHP FastCGI Process Manager
- **queue-worker**: Laravel queue worker
- **scheduler**: Laravel task scheduler
- **ssr-server**: Inertia SSR server (NEW)

### SSR Server Configuration

The SSR server runs via:
```bash
php artisan inertia:start-ssr
```

This command:
- Starts a Node.js server on port `13714` (default)
- Uses the SSR bundle located at `bootstrap/ssr/ssr.js`
- Runs with cluster mode enabled (multiple worker processes)
- Logs to `/var/www/html/storage/logs/ssr-server.log`

## Environment Variables

### Required Configuration

In your `.env` file, ensure SSR is enabled:

```env
# SSR is enabled by default in config/inertia.php
# Optionally override the SSR URL if needed:
INERTIA_SSR_URL=http://127.0.0.1:13714
```

**Note**: For Docker deployments, `127.0.0.1:13714` is correct since all services run in the same container.

### Production Considerations

1. **SSR URL**: The default `http://127.0.0.1:13714` works for single-container deployments
2. **Port**: Port `13714` is internal to the container and doesn't need to be exposed
3. **Node.js**: Node.js is kept in the production image to run the SSR server

## File Structure

```
/var/www/html/
├── bootstrap/
│   └── ssr/
│       ├── ssr.js              # SSR bundle (built)
│       ├── ssr-manifest.json   # SSR manifest
│       └── assets/             # SSR assets
├── public/
│   └── build/                 # Client-side assets
└── storage/
    └── logs/
        └── ssr-server.log     # SSR server logs
```

## Permissions

The following directories have proper permissions set:

- `bootstrap/ssr/`: `775` for directories, `664` for files
- Owned by `www-data:www-data`
- Permissions are set both in Dockerfile and entrypoint script

## Verification

### Check SSR Server Status

```bash
# Inside container
supervisorctl status ssr-server
```

### Check SSR Logs

```bash
# Inside container
tail -f /var/www/html/storage/logs/ssr-server.log
```

### Verify SSR is Working

1. Visit any public page (landing, login, register, etc.)
2. View page source (right-click → View Page Source)
3. Look for `data-page` attribute in the HTML
4. Verify full HTML content is present (not just `<div id="app"></div>`)

## Troubleshooting

### SSR Server Not Starting

1. **Check logs**: `tail -f /var/www/html/storage/logs/ssr-server.log`
2. **Verify bundle exists**: `ls -la /var/www/html/bootstrap/ssr/ssr.js`
3. **Check permissions**: `ls -la /var/www/html/bootstrap/ssr/`
4. **Restart SSR server**: `supervisorctl restart ssr-server`

### SSR Bundle Missing

If the SSR bundle is missing, rebuild it:

```bash
# Inside container or during build
npm run build:ssr
```

### Port Already in Use

If port `13714` is already in use, change it in `.env`:

```env
INERTIA_SSR_URL=http://127.0.0.1:13715
```

Then restart the SSR server.

## Production Deployment Checklist

- [x] Dockerfile builds SSR bundle (`npm run build:ssr`)
- [x] SSR directory created with proper permissions
- [x] Supervisor configured to start SSR server
- [x] Entrypoint script sets SSR directory permissions
- [x] Node.js installed in production image
- [x] SSR server logs to storage/logs
- [x] SSR URL configured (default: `http://127.0.0.1:13714`)

## Performance Considerations

1. **Cluster Mode**: SSR server runs with cluster mode enabled (multiple workers)
2. **Caching**: SSR responses are not cached by default (handled by Inertia)
3. **Memory**: Each SSR worker process uses memory; monitor if needed
4. **Restarts**: SSR server auto-restarts on failure via Supervisor

## Security

- SSR server runs on `127.0.0.1` (localhost only)
- Port `13714` is not exposed outside the container
- SSR server runs as `www-data` user (via Supervisor)
- No external network access required for SSR server

## Additional Resources

- [Inertia.js SSR Documentation](https://inertiajs.com/server-side-rendering)
- [Laravel Inertia SSR Guide](https://inertiajs.com/server-side-rendering#laravel)

