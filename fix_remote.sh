#!/bin/bash
set -e
echo "Cambiando plugin de autenticacion MySQL..."
sudo mysql -e "ALTER USER 'cbo56863'@'localhost' IDENTIFIED WITH mysql_native_password BY 'gZbQTjPFVYDzRzTdNmmA';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo "Auth_OK"

echo "Ejecutando migraciones Laravel..."
cd /var/www/botacurapp
php artisan migrate --force
echo "Migrate_OK"