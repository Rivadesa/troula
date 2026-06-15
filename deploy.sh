#!/usr/bin/env bash
#
# Despliegue / actualización de Troula en el servidor.
# Ejecutar DENTRO de la carpeta de la app (p. ej. ~/www/troula.xeitoso.com/app):
#
#   bash deploy.sh
#
set -euo pipefail

echo "==> git pull"
git pull --ff-only origin main

echo "==> composer install (producción)"
composer install --no-dev --optimize-autoloader

echo "==> migraciones"
php artisan migrate --force

echo "==> enlace de storage"
php artisan storage:link || true

echo "==> recacheo"
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize

echo "==> Despliegue completado."
