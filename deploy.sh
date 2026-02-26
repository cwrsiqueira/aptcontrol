#!/bin/bash
# Deploy APTControl - execução segura em produção
# Uso: ./deploy.sh
# Primeira vez no servidor: chmod +x deploy.sh

set -e

echo "=== Backup do banco ==="
cp database/database.sqlite database/database.sqlite.backup.$(date +%Y%m%d_%H%M%S)

echo "=== Modo manutenção ==="
php artisan down

echo "=== Atualizando código ==="
git pull

echo "=== Dependências ==="
composer install --no-dev --optimize-autoloader

echo "=== Migrations ==="
php artisan migrate --force

echo "=== Cache ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Voltar ao normal ==="
php artisan up

echo "=== Deploy concluído! ==="
