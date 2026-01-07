FROM php:8.3-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install gd pdo_pgsql zip intl \
    && a2enmod rewrite

# Configuration Apache pour Symfony
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le code
COPY . /var/www/html
WORKDIR /var/www/html

# Supprimer les configs locales et préparer l'environnement de build
RUN rm -rf var/cache/* var/log/* \
    && mkdir -p var/cache var/log public/uploads \
    && chown -R www-data:www-data var public/uploads

# Variables d'environnement pour le build
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Installation des dépendances et warmup
RUN composer install --no-dev --optimize-autoloader --no-scripts \
    && composer dump-autoload --optimize --classmap-authoritative --no-dev

# Port dynamique pour Render
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Commande de démarrage
CMD ["apache2-foreground"]