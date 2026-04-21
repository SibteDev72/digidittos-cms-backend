# ───────────────────────────────────────────────
# DigiDittos CMS — Laravel backend
# ───────────────────────────────────────────────
FROM php:8.2-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    dos2unix \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP upload limits
RUN echo "upload_max_filesize=128M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=128M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time=120" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Composer deps first (cached layer)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction

# App source
COPY . .

# Autoload — Laravel reads config from real env vars at runtime
# (Render injects them directly), so we don't need a .env file in
# the image. `.env.example` isn't committed so we can't copy it.
RUN composer dump-autoload --optimize

# Storage dirs + perms
RUN mkdir -p storage/framework/{sessions,views,cache/data} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Entrypoint — strip Windows CRLF so `bash` won't fail with "bad interpreter"
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN dos2unix /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# 9005 is the local default; Render injects its own $PORT at runtime
# and the CMD below honours it, so the container works in both envs.
EXPOSE 9005

# Use PHP's built-in server directly against public/index.php instead
# of `php artisan serve`. Artisan's wrapper spawns subprocesses that
# can lose env vars between the server boot and request handling,
# which manifests as Laravel reading fallback config (sqlite,
# http://localhost) even when the container has the right env set.
# Hitting public/index.php directly keeps everything in one process.
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-9005} -t public public/index.php"]
