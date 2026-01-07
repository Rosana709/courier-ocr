FROM php:8.3-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_pgsql zip \
    && docker-php-ext-enable pdo_pgsql zip

# Copier Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le code source
COPY . /var/www/html
WORKDIR /var/www/html

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader
