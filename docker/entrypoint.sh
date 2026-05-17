#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ -z "$APP_KEY" ]; then
    echo "ERRO: defina APP_KEY nas variáveis de ambiente do Coolify (php artisan key:generate --show)."
    exit 1
fi

if [ -z "$DB_PASSWORD" ]; then
    echo "ERRO: defina DB_PASSWORD nas variáveis de ambiente do Coolify."
    exit 1
fi

php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

echo "Aguardando MySQL aceitar conexão..."
attempt=0
max_attempts=30
until php -r "
    \$host = getenv('DB_HOST') ?: 'mysql';
    \$port = getenv('DB_PORT') ?: '3306';
    \$db = getenv('DB_DATABASE');
    \$user = getenv('DB_USERNAME');
    \$pass = getenv('DB_PASSWORD');
    new PDO(\"mysql:host={\$host};port={\$port};dbname={\$db}\", \$user, \$pass, [
        PDO::ATTR_TIMEOUT => 3,
    ]);
" 2>/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "ERRO: não foi possível conectar ao MySQL após ${max_attempts} tentativas."
        echo "  Usuário: ${DB_USERNAME:-?} | Banco: ${DB_DATABASE:-?} | Host: ${DB_HOST:-mysql}"
        echo "  Se você alterou DB_PASSWORD depois do primeiro deploy, o volume do MySQL ainda usa a senha antiga."
        echo "  Solução: no Coolify, apague o volume mysql_data e faça Redeploy, OU altere a senha do usuário dentro do MySQL."
        exit 1
    fi
    sleep 2
done
echo "MySQL OK."

php artisan config:cache
php artisan route:cache
php artisan view:cache

if ! php artisan migrate --force --no-interaction; then
    echo "ERRO: migrate falhou. Verifique DB_* no painel do Coolify (senha com @ deve estar entre aspas)."
    exit 1
fi

exec apache2-foreground
