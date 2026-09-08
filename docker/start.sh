#!/bin/bash
set -e

cd /var/www/html

# Support dynamic PORT environment variable (e.g. Render, Railway)
if [ -n "$PORT" ]; then
    sed -i "s/listen 10000;/listen $PORT;/g" /etc/nginx/sites-available/default
fi

# Cache Laravel configurations and views if APP_KEY is set
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Run migrations if database is available
php artisan migrate --force || true

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
