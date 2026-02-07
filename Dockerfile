FROM php:8.4-apache

# 1. Sistema y Librerías
RUN apt-get update && apt-get install -y \
    zip unzip git curl \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libicu-dev libpq-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd zip intl pdo pdo_pgsql bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# 2. Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Apache Config
RUN a2enmod rewrite
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# 4. Dependencias (Cache Layer)
COPY composer.json composer.lock ./
# En el build de la imagen instalamos todo. 
# En prod se puede usar --no-dev, pero para simplificar mantenemos esto por ahora.
RUN composer install --no-scripts --no-autoloader

# 5. Código Fuente
COPY . .

# 6. Finalización
RUN composer dump-autoload --optimize
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# CMD por defecto de Apache
CMD ["apache2-foreground"]