#!/usr/bin/env bash
set -e

APP_DIR="/var/www/html/mymaster33"
LARAVEL_DIR="$APP_DIR/myapp"

echo "==> Sync code"
cd "$APP_DIR"
git fetch origin
git reset --hard origin/main

echo "==> Composer install"
cd "$LARAVEL_DIR"
composer install --no-dev --optimize-autoloader

# 如果你确认这次有新增 migration，取消注释下一行
# php artisan migrate --force

echo "==> Clear caches"
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "==> Build frontend"
npm ci
npm run build

echo "==> Permissions"
chown -R www-data:www-data "$LARAVEL_DIR"
chmod -R 775 "$LARAVEL_DIR/storage" "$LARAVEL_DIR/bootstrap/cache"

echo "==> Done"