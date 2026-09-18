#!/bin/bash
set -e

# Detectar directorio del app
APP_DIR=""
for dir in /var/www/html/botacurapp /var/www/botacurapp /home/ubuntu/botacurapp /var/www/html; do
  if [ -f "$dir/artisan" ]; then APP_DIR="$dir"; break; fi
done

if [ -z "$APP_DIR" ]; then
  echo "ERROR: No se encontro artisan en ninguna ruta conocida"
  exit 1
fi
echo "App encontrado en: $APP_DIR"

# Ejecutar migraciones (auth ya fue corregida)
cd "$APP_DIR"
echo "Ejecutando migraciones..."
php artisan migrate --force
echo "Migrate_OK"