FROM php:8.2-apache

# Activer mod_rewrite
RUN a2enmod rewrite

# Installer extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le projet
COPY . /var/www/html

WORKDIR /var/www/html

# Installer les dépendances Symfony
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
