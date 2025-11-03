# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copiar package files
COPY package.json package-lock.json ./

# Instalar dependencias de Node
RUN npm ci

# Copiar código fuente
COPY . .

# Build de producción con Vite
RUN npm run build

# Stage 2: Build Landing Page (Nuxt SSG)
FROM node:20-alpine AS landing-builder

WORKDIR /app/landing

# Copy landing package files
COPY landing/package*.json ./

# Install dependencies
RUN npm ci

# Copy landing source files
COPY landing/ ./

# Generate static site
RUN npm run generate

# Stage 3: PHP FPM base image
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
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_pgsql pgsql zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

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

# Copiar los assets compilados del frontend desde el builder
COPY --from=frontend-builder /app/public/build ./public/build

# Copiar landing page static files
COPY --from=landing-builder --chown=www-data:www-data /app/landing/.output/public ./public/landing

# Crear directorios necesarios y ajustar permisos de Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Cambiar al usuario www-data para ejecutar PHP-FPM
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
