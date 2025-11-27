#!/bin/bash
set -e

# Ensure storage directories exist and have correct permissions
# This runs at container startup to handle volume mounts

echo "Ensuring storage directories exist and have correct permissions..."

# Create storage directories if they don't exist
mkdir -p /var/www/html/storage/app/private
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/testing
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Set ownership to www-data:www-data for all storage
chown -R www-data:www-data /var/www/html/storage || true

# Set permissions for directories: 775 (rwxrwxr-x)
# Owner and group can read, write, execute; others can read and execute
find /var/www/html/storage -type d -exec chmod 775 {} \; || true

# Set permissions for files: 664 (rw-rw-r--)
# Owner and group can read and write; others can read
find /var/www/html/storage -type f -exec chmod 664 {} \; || true

# Ensure specific subdirectories have correct permissions (775 for directories)
chmod 775 /var/www/html/storage || true
chmod 775 /var/www/html/storage/app || true
chmod 775 /var/www/html/storage/app/private || true
chmod 775 /var/www/html/storage/app/public || true
chmod 775 /var/www/html/storage/framework || true
chmod 775 /var/www/html/storage/framework/cache || true
chmod 775 /var/www/html/storage/framework/cache/data || true
chmod 775 /var/www/html/storage/framework/sessions || true
chmod 775 /var/www/html/storage/framework/testing || true
chmod 775 /var/www/html/storage/framework/views || true
chmod 775 /var/www/html/storage/logs || true

# Ensure all existing log files have correct permissions (664 for files)
if [ -d /var/www/html/storage/logs ]; then
    find /var/www/html/storage/logs -type f -exec chmod 664 {} \; || true
    find /var/www/html/storage/logs -type f -exec chown www-data:www-data {} \; || true
fi

# Also ensure bootstrap/cache and bootstrap/ssr have correct permissions
chown -R www-data:www-data /var/www/html/bootstrap/cache || true
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \; || true
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \; || true

# Ensure SSR directory exists and has correct permissions
mkdir -p /var/www/html/bootstrap/ssr || true
chown -R www-data:www-data /var/www/html/bootstrap/ssr || true
find /var/www/html/bootstrap/ssr -type d -exec chmod 775 {} \; || true
find /var/www/html/bootstrap/ssr -type f -exec chmod 664 {} \; || true

echo "Storage directories and files initialized with correct permissions."

# Execute the original command (supervisord)
exec "$@"

