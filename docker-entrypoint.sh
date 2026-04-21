#!/bin/bash
set -e

cd /var/www

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

exec "$@"
