# ----------------------------------------
# 1) Base Image with PHP + Apache
# ----------------------------------------
FROM php:8.2-apache

# ----------------------------------------
# 2) Install System Dependencies
# ----------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    npm \
 && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
 && rm -rf /var/lib/apt/lists/*

# ----------------------------------------
# 3) Install Composer
# ----------------------------------------
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ----------------------------------------
# 4) Set Working Directory
# ----------------------------------------
WORKDIR /var/www/html

# ----------------------------------------
# 5) Copy Composer Files First (Caching)
# ----------------------------------------
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev, optimized)
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev

# ----------------------------------------
# 6) Copy All Application Files
# ----------------------------------------
COPY . .

# ----------------------------------------
# 7) Laravel Setup
# ----------------------------------------
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
 && if [ ! -f .env ]; then cp .env.example .env; fi \
 && php artisan key:generate || true

# ----------------------------------------
# 8) Enable Apache Rewrite
# ----------------------------------------
RUN a2enmod rewrite

# Update Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ----------------------------------------
# 9) Build Frontend Assets (Vite)
# ----------------------------------------
RUN npm install && npm run build

# ----------------------------------------
# 10) Expose Port & Start Apache
# ----------------------------------------
EXPOSE 80
CMD ["apache2-foreground"]
