#!/bin/bash
set -e

cd /var/www

# AGGRESSIVELY clear all compiled/cached files FIRST, before any
# artisan command runs. If bootstrap/cache/config.php was baked into
# the image during composer install, it would shadow the env vars
# Render injects and every `env()` call would return its fallback
# (sqlite, http://localhost, debug=false). Deleting the files
# directly sidesteps the need to run artisan (which itself would
# use the stale config).
rm -f bootstrap/cache/config.php \
      bootstrap/cache/routes-v7.php \
      bootstrap/cache/routes.php \
      bootstrap/cache/services.php \
      bootstrap/cache/packages.php \
      bootstrap/cache/events.php 2>/dev/null || true
find storage/framework/views -type f -name "*.php" -delete 2>/dev/null || true
echo "Cleared bootstrap + view caches."

# Log which env vars the PHP process actually sees, so we can tell
# at-a-glance in Render logs whether the dashboard's env vars are
# reaching the container. Values are masked (only presence + length
# shown) so secrets don't leak.
echo "===== ENV VAR VISIBILITY ====="
for key in APP_ENV APP_DEBUG APP_URL APP_KEY DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME SESSION_DRIVER CACHE_STORE; do
    val="${!key:-}"
    if [ -z "$val" ]; then
        echo "  $key = <MISSING>"
    else
        len=${#val}
        preview=$(echo "$val" | cut -c1-8)
        echo "  $key = ${preview}… (len=$len)"
    fi
done
echo "=============================="

# APP_KEY resolution.
#
# In production (Render/Docker Compose with env vars) Laravel reads
# APP_KEY straight from the process environment — no `.env` file
# needed. If it's already set we skip generation so the key stays
# stable across deploys (regenerating on every boot would invalidate
# all sessions and any encrypted columns).
#
# In local dev without an env var we create a throwaway `.env` and
# let `php artisan key:generate` write to it.
if [ -z "${APP_KEY:-}" ] || ! echo "${APP_KEY}" | grep -q "^base64:"; then
    echo "APP_KEY not set in environment — generating a local one..."
    [ -f .env ] || echo "APP_KEY=" > .env
    php artisan key:generate --force
else
    echo "Using APP_KEY from environment."
fi

# Wait for PostgreSQL to be ready. Default port is 5432 (standard —
# matches Render's managed Postgres). Local docker-compose overrides
# it to 5434 via DB_PORT in .env.
echo "Waiting for PostgreSQL at ${DB_HOST:-postgres}:${DB_PORT:-5432}..."
until php -r "new PDO('pgsql:host=${DB_HOST:-postgres};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-digidittos_cms}', '${DB_USERNAME:-digidittos}', '${DB_PASSWORD:-secret}');" 2>/dev/null; do
    echo "PostgreSQL not ready yet, retrying in 2s..."
    sleep 2
done
echo "PostgreSQL is ready!"

# Migrate schema
echo "Running migrations..."
php artisan migrate --force

# Seed database (idempotent — seeders use updateOrCreate)
echo "Seeding database..."
php artisan db:seed --force

# Create storage symlink for public access
php artisan storage:link --force 2>/dev/null || true

# Ensure upload directories exist
mkdir -p /var/www/storage/app/public/uploads/media \
         /var/www/storage/app/public/uploads/services
chown -R www-data:www-data /var/www/storage/app/public/uploads

# Make sure Laravel's framework dirs are writable — Render's
# persistent disk remounts /var/www/storage/app/public at runtime
# and can shadow the perms set during the image build.
mkdir -p /var/www/storage/framework/{sessions,views,cache/data} \
         /var/www/storage/logs \
         /var/www/bootstrap/cache \
         /var/www/storage/app/public
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R ug+rw /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Refresh config/route/view caches so new env values take effect.
# We explicitly clear (not cache) so env var changes always win on
# the next request without a rebuild.
php artisan config:clear
php artisan route:clear
php artisan view:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "DigiDittos CMS backend is ready!"

# Stream Laravel's own log to stdout so exceptions show up in Render's
# log viewer. Without this, a bootstrap-time exception only lands in
# storage/logs/laravel.log and the request returns Symfony's generic
# 500 page with no way to see what threw.
LOG_FILE=/var/www/storage/logs/laravel.log
touch "$LOG_FILE" 2>/dev/null || true
chown www-data:www-data "$LOG_FILE" 2>/dev/null || true
chmod 664 "$LOG_FILE" 2>/dev/null || true
tail -n 0 -F "$LOG_FILE" 2>/dev/null &

exec "$@"
