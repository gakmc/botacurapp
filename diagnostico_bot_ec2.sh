#!/bin/bash
# ============================================================
# DIAGNÓSTICO BOT WHATSAPP — EC2 Producción
# Ejecutar desde Git Bash:
#   bash diagnostico_bot_ec2.sh
# ============================================================

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PEM="$SCRIPT_DIR/botacurapp.pem"
EC2="ubuntu@54.233.214.142"
SSH="ssh -i $PEM -o StrictHostKeyChecking=no -o ConnectTimeout=20"

chmod 600 "$PEM" 2>/dev/null

echo "========================================"
echo "  DIAGNÓSTICO BOT — Producción EC2"
echo "========================================"

echo ""
echo "[1] Variables de entorno críticas..."
$SSH $EC2 '
APP_DIR=$(for dir in /var/www/html/botacurapp /var/www/botacurapp /home/ubuntu/botacurapp; do [ -f $dir/artisan ] && echo $dir && break; done)
echo "  APP_DIR: $APP_DIR"
grep -E "^(APP_URL|BOT_SECRET|ANTHROPIC_API_KEY|META_WHATSAPP_TOKEN|META_PHONE_NUMBER_ID|META_VERIFY_TOKEN|WEBPAY_ENV|WEBPAY_COMMERCE_CODE)" $APP_DIR/.env | sed "s/=.*/=✓/" 2>/dev/null
'

echo ""
echo "[2] Últimas 50 líneas del log de Laravel (filtrado bot/webhook)..."
$SSH $EC2 '
APP_DIR=$(for dir in /var/www/html/botacurapp /var/www/botacurapp /home/ubuntu/botacurapp; do [ -f $dir/artisan ] && echo $dir && break; done)
tail -200 $APP_DIR/storage/logs/laravel.log 2>/dev/null | grep -i "whatsapp\|bot\|webhook\|Error\|Exception\|Claude\|Webpay\|Anthropic" | tail -50
'

echo ""
echo "[3] Verificar webhook accesible desde internet..."
curl -s -o /dev/null -w "  Webhook GET status: %{http_code}\n" \
  "https://app.botacura.cl/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=botacura_webhook_verify_2024&hub.challenge=TEST123"

echo ""
echo "[4] Verificar ruta bot-ai/ping..."
curl -s "https://app.botacura.cl/api/bot-ai/ping" | head -c 200
echo ""

echo ""
echo "[5] Últimas migraciones ejecutadas..."
$SSH $EC2 '
APP_DIR=$(for dir in /var/www/html/botacurapp /var/www/botacurapp /home/ubuntu/botacurapp; do [ -f $dir/artisan ] && echo $dir && break; done)
cd $APP_DIR && php artisan migrate:status 2>/dev/null | tail -20
'

echo ""
echo "========================================"
echo "  FIN DIAGNÓSTICO"
echo "========================================"
