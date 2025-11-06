#!/bin/bash

# Quick script to seed provider categories in staging

set -e

NAMESPACE="staging"

echo "🌱 Seeding Provider Categories in Staging..."

POD=$(kubectl get pods -l app=tavira-staging -n "$NAMESPACE" -o jsonpath='{.items[0].metadata.name}')

if [ -z "$POD" ]; then
    echo "❌ No pod found"
    exit 1
fi

echo "📦 Using pod: $POD"
echo "🔄 Running CentralProviderCategorySeeder..."

kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan db:seed --class=CentralProviderCategorySeeder --force

echo "✅ Categories seeded successfully!"
echo ""
echo "Verifying categories were created:"
kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); echo 'Total categories: ' . \App\Models\Central\ProviderCategory::count() . PHP_EOL;"
