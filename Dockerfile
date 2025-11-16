# Multi-stage build for Laravel application with queue worker
FROM php:8.3-fpm as base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    nano \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Copy application files (needed for composer post-install scripts)
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction --prefer-dist

# Install npm dependencies and build assets
RUN npm ci && npm run build

# Remove dev dependencies for production (optional - keeps image smaller)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || true

# Ensure storage/app directories exist (will be overridden by volume mount if configured)
RUN mkdir -p /var/www/html/storage/app/private \
    && mkdir -p /var/www/html/storage/app/public \
    && chown -R www-data:www-data /var/www/html/storage/app

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Create supervisor configuration directories
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor

# Supervisor main configuration
RUN echo '[supervisord]' > /etc/supervisor/supervisord.conf \
    && echo 'nodaemon=true' >> /etc/supervisor/supervisord.conf \
    && echo 'user=root' >> /etc/supervisor/supervisord.conf \
    && echo 'logfile=/var/log/supervisor/supervisord.log' >> /etc/supervisor/supervisord.conf \
    && echo 'pidfile=/var/run/supervisord.pid' >> /etc/supervisor/supervisord.conf \
    && echo '' >> /etc/supervisor/supervisord.conf \
    && echo '[include]' >> /etc/supervisor/supervisord.conf \
    && echo 'files = /etc/supervisor/conf.d/*.conf' >> /etc/supervisor/supervisord.conf

# Supervisor programs configuration
RUN echo '[program:nginx]' > /etc/supervisor/conf.d/nginx.conf \
    && echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/nginx.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/nginx.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/nginx.conf \
    && echo 'stdout_logfile=/var/log/nginx/access.log' >> /etc/supervisor/conf.d/nginx.conf \
    && echo 'stderr_logfile=/var/log/nginx/error.log' >> /etc/supervisor/conf.d/nginx.conf

RUN echo '[program:php-fpm]' > /etc/supervisor/conf.d/php-fpm.conf \
    && echo 'command=php-fpm -F' >> /etc/supervisor/conf.d/php-fpm.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/php-fpm.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/php-fpm.conf \
    && echo 'stdout_logfile=/var/log/php-fpm.log' >> /etc/supervisor/conf.d/php-fpm.conf \
    && echo 'stderr_logfile=/var/log/php-fpm.log' >> /etc/supervisor/conf.d/php-fpm.conf

RUN echo '[program:queue-worker]' > /etc/supervisor/conf.d/queue-worker.conf \
    && echo 'command=php /var/www/html/artisan queue:work --queue=emails --tries=3 --timeout=90 --sleep=3 --max-time=3600' >> /etc/supervisor/conf.d/queue-worker.conf \
    && echo 'autostart=true' >> /etc/supervisor/conf.d/queue-worker.conf \
    && echo 'autorestart=true' >> /etc/supervisor/conf.d/queue-worker.conf \
    && echo 'stopasgroup=true' >> /etc/supervisor/conf.d/queue-worker.conf \
    && echo 'killasgroup=true' >> /etc/supervisor/conf.d/queue-worker.conf \
    && echo 'stdout_logfile=/var/www/html/storage/logs/queue-worker.log' >> /etc/supervisor/conf.d/queue-worker.conf \
    && echo 'stderr_logfile=/var/www/html/storage/logs/queue-worker.log' >> /etc/supervisor/conf.d/queue-worker.conf

# Configure nginx for Laravel with Vite support
RUN rm -f /etc/nginx/sites-enabled/default \
    && echo 'server {' > /etc/nginx/sites-available/laravel \
    && echo '    listen 80;' >> /etc/nginx/sites-available/laravel \
    && echo '    server_name _;' >> /etc/nginx/sites-available/laravel \
    && echo '    root /var/www/html/public;' >> /etc/nginx/sites-available/laravel \
    && echo '    index index.php index.html;' >> /etc/nginx/sites-available/laravel \
    && echo '' >> /etc/nginx/sites-available/laravel \
    && echo '    # Serve Vite assets from build directory' >> /etc/nginx/sites-available/laravel \
    && echo '    location /build {' >> /etc/nginx/sites-available/laravel \
    && echo '        alias /var/www/html/public/build;' >> /etc/nginx/sites-available/laravel \
    && echo '        expires 1y;' >> /etc/nginx/sites-available/laravel \
    && echo '        add_header Cache-Control "public, immutable";' >> /etc/nginx/sites-available/laravel \
    && echo '        access_log off;' >> /etc/nginx/sites-available/laravel \
    && echo '    }' >> /etc/nginx/sites-available/laravel \
    && echo '' >> /etc/nginx/sites-available/laravel \
    && echo '    # Serve static files directly' >> /etc/nginx/sites-available/laravel \
    && echo '    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {' >> /etc/nginx/sites-available/laravel \
    && echo '        expires 1y;' >> /etc/nginx/sites-available/laravel \
    && echo '        add_header Cache-Control "public, immutable";' >> /etc/nginx/sites-available/laravel \
    && echo '        access_log off;' >> /etc/nginx/sites-available/laravel \
    && echo '    }' >> /etc/nginx/sites-available/laravel \
    && echo '' >> /etc/nginx/sites-available/laravel \
    && echo '    location / {' >> /etc/nginx/sites-available/laravel \
    && echo '        try_files $uri $uri/ /index.php?$query_string;' >> /etc/nginx/sites-available/laravel \
    && echo '    }' >> /etc/nginx/sites-available/laravel \
    && echo '' >> /etc/nginx/sites-available/laravel \
    && echo '    location ~ \.php$ {' >> /etc/nginx/sites-available/laravel \
    && echo '        fastcgi_pass 127.0.0.1:9000;' >> /etc/nginx/sites-available/laravel \
    && echo '        fastcgi_index index.php;' >> /etc/nginx/sites-available/laravel \
    && echo '        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' >> /etc/nginx/sites-available/laravel \
    && echo '        include fastcgi_params;' >> /etc/nginx/sites-available/laravel \
    && echo '    }' >> /etc/nginx/sites-available/laravel \
    && echo '}' >> /etc/nginx/sites-available/laravel \
    && ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel \
    && rm -f /etc/nginx/sites-enabled/default

# Expose port 80
EXPOSE 80

# Start supervisor (which manages nginx, php-fpm, and queue worker)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]

