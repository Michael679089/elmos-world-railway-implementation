# ---------------------
# 1. Base image for PHP
# ---------------------
FROM php:8.2-fpm

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# PROJECT FILES
COPY . /var/www/html

# PERMISSIONS
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# APACHE CONFIG
RUN a2enmod rewrite

# PORT
EXPOSE 80

# ENVIRONMENT
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# CONFIGURE APACHE ROOT
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# LARAVEL DEPENDENCIES
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# CREATE .env IF MISSING
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# For making a build
RUN npm run build

# START APACHE
CMD ["apache2-foreground"]