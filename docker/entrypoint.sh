#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ -z "$APP_KEY" ]; then
    echo "ERRO: defina APP_KEY nas variáveis de ambiente do Coolify (php artisan key:generate --show)."
    exit 1
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force --no-interaction

exec apache2-foreground
