#!/bin/sh
set -e

echo "🚀 Starting Tavira application..."

# Quick database connectivity check (reduced timeout for faster startup)
if [ "${DB_HOST:-}" ]; then
    echo "⏳ Checking database connection..."
    max_tries=10
    tries=0
    until php artisan db:show > /dev/null 2>&1 || [ $tries -eq $max_tries ]; do
        tries=$((tries + 1))
        echo "   Waiting for database... ($tries/$max_tries)"
        sleep 1
    done

    if [ $tries -eq $max_tries ]; then
        echo "⚠️  Warning: Could not connect to database after $max_tries attempts"
        echo "   Application will continue but may not function properly"
    else
        echo "✅ Database connected"
    fi
fi

# Run migrations automatically if enabled
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    echo "🗄️  Running database migrations..."
    php artisan migrate --force --isolated || {
        echo "⚠️  Migration failed, continuing anyway..."
    }
fi

echo "🎉 Application ready!"

# Execute the main command (php-fpm)
exec "$@"
