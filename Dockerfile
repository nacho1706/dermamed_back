FROM php:8.4-apache

# -----------------------------------------
# SISTEMA + LIBRERÍAS
# -----------------------------------------
RUN apt-get update && apt-get install -y \
    zip unzip git curl \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libicu-dev libpq-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd zip intl pdo pdo_pgsql bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# -----------------------------------------
# COMPOSER
# -----------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -----------------------------------------
# APACHE
# -----------------------------------------
RUN a2enmod rewrite

# Cambiar document root a /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]

# -----------------------------------------
# DIRECTORIO
# -----------------------------------------
WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-scripts --no-autoloader

# Copiar el resto del proyecto
COPY . .

RUN composer dump-autoload

# Permisos Laravel
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache
