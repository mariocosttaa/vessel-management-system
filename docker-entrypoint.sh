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

# Set ownership to www-data:www-data
chown -R www-data:www-data /var/www/html/storage || true

# Set permissions to 755 (rwxr-xr-x)
# Owner (www-data) can read, write, execute
# Group and others can read and execute
chmod -R 755 /var/www/html/storage || true

# Ensure specific subdirectories have correct permissions
chmod 755 /var/www/html/storage/app || true
chmod 755 /var/www/html/storage/app/private || true
chmod 755 /var/www/html/storage/app/public || true
chmod 755 /var/www/html/storage/framework || true
chmod 755 /var/www/html/storage/framework/cache || true
chmod 755 /var/www/html/storage/framework/cache/data || true
chmod 755 /var/www/html/storage/framework/sessions || true
chmod 755 /var/www/html/storage/framework/testing || true
chmod 755 /var/www/html/storage/framework/views || true
chmod 755 /var/www/html/storage/logs || true

# Also ensure bootstrap/cache has correct permissions
chmod -R 755 /var/www/html/bootstrap/cache || true
chown -R www-data:www-data /var/www/html/bootstrap/cache || true

echo "Storage directories initialized with correct permissions."

# Execute the original command (supervisord)
exec "$@"

