#!/bin/sh
set -e

echo "🚀 Starting Tavira application..."

# Wait for database to be ready (optional, useful for initial deployments)
if [ "${DB_HOST:-}" ]; then
    echo "⏳ Waiting for database connection..."
    max_tries=30
    tries=0
    until php artisan db:show > /dev/null 2>&1 || [ $tries -eq $max_tries ]; do
        tries=$((tries + 1))
        echo "   Database not ready, attempt $tries/$max_tries..."
        sleep 2
    done

    if [ $tries -eq $max_tries ]; then
        echo "⚠️  Warning: Could not connect to database after $max_tries attempts"
        echo "   Continuing anyway..."
    else
        echo "✅ Database connection established"
    fi
fi

# Run Laravel optimizations (these need environment variables)
echo "🔧 Running Laravel optimizations..."

# Clear any existing cache first
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Cache configuration for better performance
echo "   - Caching configuration..."
php artisan config:cache

# Skip route caching for multi-tenancy (causes conflicts with duplicate route names between central and tenant apps)
# echo "   - Caching routes..."
# php artisan route:cache

# Cache views for better performance
echo "   - Caching views..."
php artisan view:cache

# Optimize composer autoloader
echo "   - Optimizing autoloader..."
composer dump-autoload --optimize --no-dev --classmap-authoritative 2>/dev/null || true

echo "✅ Optimizations complete"

# Run migrations automatically (optional, comment out if you prefer manual migrations)
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    echo "🗄️  Running database migrations..."
    php artisan migrate --force --isolated
    echo "✅ Migrations complete"
fi

echo "🎉 Application ready!"
echo ""

# Execute the main command (php-fpm)
exec "$@"
