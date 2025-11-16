# Coolify Queue Worker Setup Guide

Simple guide to set up Laravel queue worker in Coolify using the database driver, following [Laravel's official documentation](https://laravel.com/docs/12.x/queues).

## Quick Summary

**What you need to do:**
1. Set `QUEUE_CONNECTION=database` in environment variables
2. Create jobs table (run migration)
3. **Create `nixpacks.toml` file** in your project root (runs worker in same container)
4. **Commit and redeploy** - worker starts automatically

**Benefits:**
- ✅ No duplication - worker runs in same container as web server
- ✅ Efficient - no extra resources needed
- ✅ Automatic restarts if worker crashes

## Prerequisites

1. **Database queue driver configured** (easiest option, no Redis needed)
2. **Jobs table created** (Laravel migration)

## Step 1: Configure Queue Connection

In your `.env` file (or Coolify environment variables), set:

```env
QUEUE_CONNECTION=database
```

This tells Laravel to use the database to store queued jobs. No additional services needed!

## Step 2: Create Jobs Table

Make sure you've run the queue table migration:

```bash
php artisan queue:table
php artisan migrate
```

This creates the `jobs` table in your database where Laravel stores queued jobs.

## Step 3: Add Queue Worker to Your Existing Application

**Best Approach: Run worker in the same container using supervisord**

This is the recommended way - it runs the queue worker alongside your web server in the same container, avoiding duplication and saving resources.

### Create `nixpacks.toml` File

In your Laravel project root, create a file named `nixpacks.toml`:

```toml
[phases.setup]
nixPkgs = ["...", "python311Packages.supervisor"]

[phases.build]
cmds = [
    "mkdir -p /etc/supervisor/conf.d/",
    "cp /assets/worker-laravel.conf /etc/supervisor/conf.d/",
    "cp /assets/supervisord.conf /etc/supervisord.conf",
    "chmod +x /assets/start.sh",
    "..."
]

[start]
cmd = '/assets/start.sh'

[staticAssets]
"start.sh" = '''
#!/bin/bash

# Transform the nginx configuration
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /etc/nginx.conf

# Start supervisor
supervisord -c /etc/supervisord.conf -n
'''

"supervisord.conf" = '''
[unix_http_server]
file=/assets/supervisor.sock

[supervisord]
logfile=/var/log/supervisord.log
pidfile=/var/run/supervisord.pid
childlogdir=/var/log/supervisor
nodaemon=true

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///assets/supervisor.sock

[program:worker-laravel]
process_name=%(program_name)s_%(process_num)02d
command=bash -c 'exec php /app/artisan queue:work --queue=emails --sleep=3 --tries=3 --max-time=3600'
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
startsecs=0
stopwaitsecs=3600
stdout_logfile=/var/log/worker-laravel.log
stderr_logfile=/var/log/worker-laravel.log
'''

"worker-laravel.conf" = '''
[program:worker-laravel]
process_name=%(program_name)s_%(process_num)02d
command=bash -c 'exec php /app/artisan queue:work --queue=emails --sleep=3 --tries=3 --max-time=3600'
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
startsecs=0
stopwaitsecs=3600
stdout_logfile=/var/log/worker-laravel.log
stderr_logfile=/var/log/worker-laravel.log
'''
```

**Key Configuration:**
- `numprocs=1`: Number of worker processes (increase if needed)
- `--queue=emails`: Processes the `emails` queue
- `--tries=3`: Retry failed jobs 3 times
- `--max-time=3600`: Restart worker after 1 hour (prevents memory leaks)

### Deploy to Coolify

1. **Commit and push the `nixpacks.toml` file** to your repository
2. **In Coolify**, go to your application
3. **Redeploy** the application (Coolify will automatically detect and use the `nixpacks.toml` file)
4. The queue worker will start automatically alongside your web server

### Verify It's Working

After deployment, check the logs in Coolify:
- You should see both nginx/web server and queue worker processes running
- Queue worker logs will show: `Processing: App\Jobs\SendGroupedEmailNotifications`

**Benefits:**
- ✅ No duplication - same container, same code
- ✅ Efficient resource usage
- ✅ Automatic restarts if worker crashes
- ✅ Centralized logging

#### Alternative: If Coolify Uses Docker Compose

If your Coolify setup uses Docker Compose, you might need to edit a `docker-compose.yml` file:

1. In your application, look for **"Docker Compose"** or **"Compose File"** section
2. Click **"Edit"** or **"View Compose File"**
3. Add a new service:

```yaml
services:
  # ... your existing services (app, database, etc.)
  
  queue-worker:
    image: your-app-image:latest  # Same as your main app
    command: php artisan queue:work --queue=emails --tries=3
    working_dir: /var/www/html
    environment:
      - QUEUE_CONNECTION=database
      # ... copy all env vars from your main app service
    volumes:
      - ./storage:/var/www/html/storage  # If needed
    restart: unless-stopped
    depends_on:
      - app  # Your main app service name
```

4. Click **"Save"** and **"Redeploy"**

### Option B: Using Terminal (If You Have SSH Access)

**Only use this if you have direct SSH/terminal access to your Coolify server.**

If you can SSH into your server, you can manually add a supervisor configuration:

1. **SSH into your server**
2. **Create supervisor config**:
   ```bash
   sudo nano /etc/supervisor/conf.d/laravel-queue-worker.conf
   ```
3. **Add this configuration** (adjust paths):
   ```ini
   [program:laravel-queue-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/your/app/artisan queue:work --queue=emails --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/path/to/your/app/storage/logs/worker.log
   ```
4. **Reload supervisor**:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-queue-worker:*
   ```

**Note**: This method is more complex and requires server access. **Option A (Dashboard) is recommended.**

## Step 4: Verify It's Working

### How to Check if Worker is Running:

1. **In Coolify Dashboard:**
   - Go to your application → **"Logs"** tab
   - You should see logs from both:
     - Your web server (nginx/PHP-FPM)
     - Your queue worker (supervisord/queue:work)
   - Look for queue worker output like:
     ```
     Processing: App\Jobs\SendGroupedEmailNotifications
     Processed: App\Jobs\SendGroupedEmailNotifications
     ```

2. **Check Supervisor Status (via Terminal):**
   - In Coolify, go to your application → **"Terminal"** tab
   - Run: `supervisorctl status`
   - You should see `worker-laravel:worker-laravel_00` with status `RUNNING`

### Test Queue Processing:

**Option 1: Using Coolify's Terminal/Console (if available):**
- In your main application, look for **"Terminal"** or **"Console"** or **"Execute Command"**
- Run:
  ```bash
  php artisan tinker
  ```
- Then:
  ```php
  \App\Jobs\SendGroupedEmailNotifications::dispatch();
  ```
- Check the `queue-worker` logs - you should see the job being processed

**Option 2: Using SSH (if you have access):**
```bash
ssh your-server
cd /path/to/your/app
php artisan tinker
```

```php
\App\Jobs\SendGroupedEmailNotifications::dispatch();
```

### Check Queue Status:

In Tinker (via Coolify terminal or SSH):
```php
DB::table('jobs')->count();  // Should be 0 if worker is processing
DB::table('failed_jobs')->count();  // Should be 0
```

## That's It!

Your queue worker is now running and will automatically process jobs from the `emails` queue.

## Troubleshooting

### Can't Find "Services" or "Workers" Section in Coolify?

**Different Coolify versions have different interfaces. Try these locations:**
- Look for **"Additional Services"** tab
- Check **"Docker Compose"** section (you might need to edit compose file)
- Look for **"Resources"** or **"Components"** 
- Check if there's a **"+"** or **"Add"** button in the main app view
- Some versions have it under **"Settings"** → **"Services"**

**If you still can't find it:**
- Check Coolify documentation for your version
- Look for "worker" or "background job" in Coolify's help/docs
- You might need to use Docker Compose method (Option A, Alternative section above)

### Worker Service Created But Not Running?

1. **Check Status in Coolify:**
   - Go to your application → Services
   - Check if `queue-worker` shows as "Stopped" or "Error"
   - Click on it to see error messages

2. **Common Issues:**
   - **Wrong working directory**: Check your main app's working directory and match it
   - **Missing environment variables**: Worker needs ALL env vars from main app
   - **Wrong Docker image**: Must use same image as main app
   - **Command syntax error**: Make sure command is exactly: `php artisan queue:work --queue=emails --tries=3`

3. **Check Logs:**
   - Click on `queue-worker` service
   - View logs to see specific error messages
   - Common errors:
     - "Could not find artisan" → Wrong working directory
     - "Database connection failed" → Missing DB env vars
     - "Class not found" → Missing dependencies

### Worker Running But Not Processing Jobs?

1. **Verify Queue Connection:**
   - In Coolify, check worker's environment variables
   - Make sure `QUEUE_CONNECTION=database` is set
   - Verify all `DB_*` variables match your main app

2. **Check if Jobs Table Exists:**
   - Use Coolify terminal or SSH:
     ```bash
     php artisan migrate:status
     ```
   - If `jobs` table doesn't exist:
     ```bash
     php artisan queue:table
     php artisan migrate
     ```

3. **Check Worker Logs:**
   - Worker should show: `Waiting for jobs...` or similar
   - If you see errors, they'll tell you what's wrong

4. **Test if Jobs are Being Queued:**
   - Dispatch a test job (see Step 4)
   - Check if it appears in database:
     ```php
     DB::table('jobs')->count();  // Should increase when you dispatch
     ```

### Jobs Failing?

1. **Check Failed Jobs:**
   - In Coolify terminal or SSH:
     ```bash
     php artisan queue:failed
     ```
   - This shows all failed jobs with error messages

2. **Retry Failed Jobs:**
   ```bash
   php artisan queue:retry all
   ```

3. **Check Application Logs:**
   - In Coolify, go to your main application
   - View logs: `storage/logs/laravel.log`
   - Look for error messages related to your jobs

### Worker Keeps Restarting/Crashing?

1. **Check Resource Limits:**
   - In Coolify, check worker service settings
   - Increase memory limit if needed (512MB minimum)
   - Check CPU limits

2. **Check Timeout Settings:**
   - If jobs take long, increase timeout:
     ```
     php artisan queue:work --queue=emails --tries=3 --timeout=300
     ```
   - Update the command in Coolify service settings

3. **Check Logs for Memory Errors:**
   - Look for "out of memory" or "killed" messages
   - Increase memory limit in Coolify service settings

### Still Having Issues?

1. **Verify Main App is Working:**
   - Make sure your main Laravel app is running correctly
   - Test database connection from main app
   - Test mail sending from main app

2. **Compare Configurations:**
   - Worker should have EXACTLY the same environment variables as main app
   - Use Coolify's "Copy from main app" feature if available

3. **Check Coolify Documentation:**
   - Visit Coolify's official documentation
   - Look for "workers" or "background jobs" section
   - Check Coolify version-specific guides

## Reference

- [Laravel Queues Documentation](https://laravel.com/docs/12.x/queues)
- [Laravel Deployment Documentation](https://laravel.com/docs/12.x/deployment)

