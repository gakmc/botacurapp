Set-Location "C:\xampp\htdocs\proyectos\botacurapp"
$bash = "C:\Program Files\Git\bin\bash.exe"

$fixScript = @'
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
'@
[System.IO.File]::WriteAllText("$PWD\fix_migrate.sh", $fixScript, [System.Text.Encoding]::UTF8)

Write-Host "Buscando app y ejecutando migraciones..." -ForegroundColor Cyan
& $bash -c "ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@54.233.214.142 'bash -s' < fix_migrate.sh"

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR" -ForegroundColor Red
} else {
    Write-Host ""
    Write-Host "Listo! Verifica: https://app.botacura.cl" -ForegroundColor Green
}
Read-Host "Presiona Enter para salir"
