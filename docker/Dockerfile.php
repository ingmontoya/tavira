# Imagen base mínima de PHP 8.3 FPM
FROM php:8.3-fpm-alpine AS base

# Instalar dependencias del sistema necesarias para Laravel
RUN apk add --no-cache \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_pgsql pgsql zip intl

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos esenciales primero (para cachear capas)
COPY composer.json composer.lock ./

# Instalar dependencias de producción
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist --optimize-autoloader \
    && rm -rf /root/.composer

# Copiar el resto del código (sin node_modules, sin .git)
COPY . .

# Crear directorios necesarios y ajustar permisos de Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copiar entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
