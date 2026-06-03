# syntax=docker/dockerfile:1.7

# ─── Stage 1: vendor ──────────────────────────────────────────────────────────
# Resolve Composer dependencies with --no-dev for production. Keeping this
# step in a dedicated stage means the final image never ships Composer itself,
# build caches, or dev packages (phpunit, pail, etc).
FROM composer:2 AS vendor
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress

# ─── Stage 2: runtime ─────────────────────────────────────────────────────────
FROM php:8.4-apache AS runtime

# System + PHP extensions. Kept minimal: only what Laravel + Postgres needs.
RUN apt-get update && apt-get install -y --no-install-recommends \
        zip unzip git curl \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libicu-dev libpq-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd zip intl pdo pdo_pgsql bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# Apache: serve from public/
RUN a2enmod rewrite \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Bring in the prebuilt vendor/ from stage 1, then the application code.
COPY --from=vendor /build/vendor ./vendor
COPY . .

# Composer (binary copied from upstream image) so the post-install scripts
# can regenerate the optimized autoloader against the actual source tree.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

CMD ["apache2-foreground"]
