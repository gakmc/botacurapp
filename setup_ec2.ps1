Set-Location "C:\xampp\htdocs\proyectos\botacurapp"
$bash = "C:\Program Files\Git\bin\bash.exe"

Write-Host "==========================================" -ForegroundColor Green
Write-Host " Setup MySQL en EC2 + Importar datos" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green

# Verificar dumps
if (-not (Test-Path "dump_botacurapp.sql")) { Write-Host "ERROR: No se encontro dump_botacurapp.sql" -ForegroundColor Red; Read-Host; exit 1 }
if (-not (Test-Path "dump_botacura_iot.sql")) { Write-Host "ERROR: No se encontro dump_botacura_iot.sql" -ForegroundColor Red; Read-Host; exit 1 }

# Escribir script bash que se ejecutara en EC2 via stdin
$remoteScript = @"
#!/bin/bash
set -e
sudo apt-get update -qq
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql
sudo mysql -e "CREATE DATABASE IF NOT EXISTS cbo56863_botacurapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS cbo56863_botacura_iot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'cbo56863'@'localhost' IDENTIFIED BY 'gZbQTjPFVYDzRzTdNmmA';"
sudo mysql -e "ALTER USER 'cbo56863'@'localhost' IDENTIFIED BY 'gZbQTjPFVYDzRzTdNmmA';"
sudo mysql -e "GRANT ALL PRIVILEGES ON cbo56863_botacurapp.* TO 'cbo56863'@'localhost';"
sudo mysql -e "GRANT ALL PRIVILEGES ON cbo56863_botacura_iot.* TO 'cbo56863'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo "MySQL OK"
"@
[System.IO.File]::WriteAllText("$PWD\setup_remote.sh", $remoteScript, [System.Text.Encoding]::UTF8)

Write-Host ""
Write-Host "[1/3] Instalando MySQL en EC2 y configurando usuario..." -ForegroundColor Cyan
& $bash -c "ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@54.233.214.142 'bash -s' < setup_remote.sh"
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR en paso 1" -ForegroundColor Red; Read-Host; exit 1 }

Write-Host ""
Write-Host "[2/3] Copiando dumps a EC2 (93MB, puede tardar varios minutos)..." -ForegroundColor Cyan
& $bash -c "scp -i botacurapp.pem -o StrictHostKeyChecking=no dump_botacurapp.sql dump_botacura_iot.sql ubuntu@54.233.214.142:/tmp/"
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR en paso 2" -ForegroundColor Red; Read-Host; exit 1 }

Write-Host ""
Write-Host "[3/3] Importando databases en EC2..." -ForegroundColor Cyan
& $bash -c "ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@54.233.214.142 'sudo mysql cbo56863_botacurapp < /tmp/dump_botacurapp.sql && sudo mysql cbo56863_botacura_iot < /tmp/dump_botacura_iot.sql && rm /tmp/dump_botacurapp.sql /tmp/dump_botacura_iot.sql && echo Import_OK'"
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR en paso 3" -ForegroundColor Red; Read-Host; exit 1 }

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host " LISTO! Ahora ejecuta push_and_deploy.bat " -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Read-Host "Presiona Enter para salir"
