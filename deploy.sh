#!/bin/bash
# ============================================================
# DEPLOY BOTACURAPP → EC2 PRODUCCIÓN
# Ejecutar desde Git Bash en la carpeta del proyecto:
#   bash deploy.sh
# ============================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PEM="$SCRIPT_DIR/botacurapp.pem"
EC2="ubuntu@54.233.214.142"
SSH="ssh -i $PEM -o StrictHostKeyChecking=no -o ConnectTimeout=20"

echo "========================================"
echo "  DEPLOY BotacurApp → Producción"
echo "========================================"

chmod 600 "$PEM"

# 1. Verificar conexión
echo ""
echo "[1/7] Verificando SSH..."
$SSH $EC2 "echo '  SSH OK'"

# 2. Detectar directorio del app
echo ""
echo "[2/7] Detectando directorio del app..."
APP_DIR=$($SSH $EC2 "
  for dir in /var/www/html/botacurapp /var/www/botacurapp /home/ubuntu/botacurapp /var/www/html; do
    if [ -f \$dir/artisan ]; then echo \$dir; break; fi
  done
")
echo "  App en: $APP_DIR"

if [ -z "$APP_DIR" ]; then
  echo "ERROR: No se encontró el directorio del app en EC2"
  exit 1
fi

# 3. Git pull
echo ""
echo "[3/7] Actualizando código (git pull)..."
$SSH $EC2 "cd $APP_DIR && git fetch origin && git pull origin Sebastian"

# 4. Composer install
echo ""
echo "[4/7] Instalando dependencias PHP..."
$SSH $EC2 "cd $APP_DIR && composer install --no-dev --optimize-autoloader --no-interaction"

# 5. Subir .env de producción
echo ""
echo "[5/7] Subiendo .env de producción..."
scp -i "$PEM" -o StrictHostKeyChecking=no \
  "$SCRIPT_DIR/.env.production" \
  $EC2:$APP_DIR/.env

# 6. Migraciones + limpiar caché
echo ""
echo "[6/7] Ejecutando migraciones y limpiando caché..."
$SSH $EC2 "
  cd $APP_DIR
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  php artisan route:clear
  php artisan migrate --force
  php artisan config:cache
"

# 7. Permisos storage
echo ""
echo "[7/7] Ajustando permisos..."
$SSH $EC2 "
  cd $APP_DIR
  sudo chown -R www-data:www-data storage bootstrap/cache
  sudo chmod -R 775 storage bootstrap/cache
"

echo ""
echo "========================================"
echo "  DEPLOY COMPLETADO"
echo "  URL: https://app.botacura.cl"
echo "========================================"
