# IMAGE
FROM php:8.2-apache

# DEPENDENCIES
RUN apt-get update && apt-get install -y \ 
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev zip curl \
    nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# COMPOSER
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# WORKING DIRECTORY
WORKDIR /var/www/html

# COPY PROJECT FILES
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

# INSTALL LARAVEL DEPENDENCIES
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# INSTALL FRONTEND DEPENDENCIES & BUILD
RUN npm install && npm run build

# I want to echo what the directory looks like - recursive
RUN echo "Showing the directory"
RUN ls


# START APACHE
CMD ["apache2-foreground"]
