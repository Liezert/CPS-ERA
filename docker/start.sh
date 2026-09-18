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

# Wait for the database, then migrate. Managed databases are often not reachable
# yet when the container boots, so retry before giving up -- but never start the
# app with an unmigrated schema.
MIGRATE_ATTEMPTS="${MIGRATE_ATTEMPTS:-10}"
MIGRATE_DELAY="${MIGRATE_DELAY:-3}"

attempt=1
until php artisan migrate --force --no-interaction; do
    if [ "$attempt" -ge "$MIGRATE_ATTEMPTS" ]; then
        echo "FATAL: migrations failed after ${MIGRATE_ATTEMPTS} attempts; refusing to start." >&2
        exit 1
    fi
    echo "Migration attempt ${attempt}/${MIGRATE_ATTEMPTS} failed; retrying in ${MIGRATE_DELAY}s..." >&2
    attempt=$((attempt + 1))
    sleep "$MIGRATE_DELAY"
done

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
