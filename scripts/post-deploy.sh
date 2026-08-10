#!/bin/bash
# scripts/post-deploy.sh
# Jalankan setelah deploy production (Railway / Render / VPS)
# Pastikan di-run sebagai: bash scripts/post-deploy.sh

set -e

echo "🚀 Post-deploy script Aksara dimulai..."

# 1. Jalankan migration
echo "📦 Running migrations..."
php artisan migrate --no-interaction --force

# 2. Cache config, routes, views
echo "⚡ Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Clear stale cache
echo "🧹 Clearing stale cache..."
php artisan cache:clear

# 4. (Opsional) Seed data demo jika environment staging
if [ "$APP_ENV" = "staging" ]; then
    echo "🌱 Seeding demo data (staging only)..."
    php artisan db:seed --class=DemoDataSeeder --no-interaction --force
fi

echo "✅ Post-deploy selesai! Aksara siap digunakan."
