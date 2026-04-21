#!/bin/bash
set -e

cd /var/www

# Ensure .env exists (fallback to .env.example)
if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

# Generate APP_KEY if missing
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL at ${DB_HOST:-postgres}:${DB_PORT:-5434}..."
until php -r "new PDO('pgsql:host=${DB_HOST:-postgres};port=${DB_PORT:-5434};dbname=${DB_DATABASE:-digidittos_cms}', '${DB_USERNAME:-digidittos}', '${DB_PASSWORD:-secret}');" 2>/dev/null; do
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

# Refresh config/route caches so new env values take effect
php artisan config:clear
php artisan route:clear

echo "DigiDittos CMS backend is ready!"

exec "$@"
