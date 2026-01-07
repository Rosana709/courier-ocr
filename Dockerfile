FROM php:8.3-fpm

# Installer dépendances système et PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libpq-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd pdo_pgsql zip \
 && docker-php-ext-enable gd pdo_pgsql zip

# Copier Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le code source
COPY . /var/www/html
WORKDIR /var/www/html

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Expose le port HTTP 80
EXPOSE 80

# Lance le serveur PHP intégré sur le port 80, en servant le dossier 'public'
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]