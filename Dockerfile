# =============================================================================
# Stage 1: Build PHP dependencies (Vendor)
# =============================================================================
FROM php:8.3-fpm-alpine AS vendor

# Install system dependencies needed for Laravel
RUN apk add --no-cache \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    autoconf \
    g++ \
    make \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo_pgsql bcmath intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy only composer files first (for layer caching)
COPY composer.json composer.lock ./

# Install production dependencies and clean cache
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    && composer clear-cache \
    && rm -rf /root/.composer

# Copy application code (excluding node_modules, tests, etc via .dockerignore)
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# =============================================================================
# Stage 2: Build Frontend Assets
# =============================================================================
FROM node:20-alpine AS frontend

WORKDIR /app

# Copy package files first (for layer caching)
COPY package*.json ./

# Install dependencies with clean install
RUN npm ci --only=production=false

# Copy necessary files for build
COPY . .
COPY --from=vendor /app/vendor ./vendor

# Build production assets
RUN npm run build && \
    rm -rf node_modules && \
    npm cache clean --force

# =============================================================================
# Stage 3: Production Image (PHP-FPM Only)
# =============================================================================
FROM php:8.3-fpm-alpine

# Install only runtime dependencies (no build tools)
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    libzip \
    postgresql-libs \
    icu-libs \
    && rm -rf /var/cache/apk/*

# Copy PHP extensions from vendor stage (already compiled)
COPY --from=vendor /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=vendor /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Set working directory
WORKDIR /var/www/html

# Copy application from vendor stage (excludes dev dependencies)
COPY --from=vendor --chown=www-data:www-data /app ./

# Copy built assets from frontend stage
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

# Set proper permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache && \
    chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Configure PHP-FPM to listen on port 9000
RUN sed -i 's/listen = .*/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Switch to non-root user
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
