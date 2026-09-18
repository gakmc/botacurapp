@echo off
title Migrar DB a EC2
cd /d "C:\xampp\htdocs\proyectos\botacurapp"
echo =========================================
echo  MIGRAR DB de cPanel a EC2
echo =========================================
echo.

set PEM=botacurapp.pem
set EC2=ubuntu@18.229.55.160
set CPANEL_HOST=ap4.cpanelhost.cl
set DB_USER=cbo56863
set DB_PASS=gZbQTjPFVYDzRzTdNmmA
set DB1=cbo56863_botacurapp
set DB2=cbo56863_botacura_iot
set DUMP1=dump_botacurapp.sql
set DUMP2=dump_botacura_iot.sql

echo [1/5] Exportando %DB1% desde cPanel...
"C:\xampp\mysql\bin\mysqldump.exe" -h %CPANEL_HOST% -u %DB_USER% -p%DB_PASS% --single-transaction --routines --triggers %DB1% > %DUMP1%
if %errorlevel% neq 0 (
    echo ERROR al exportar %DB1%. Verifica conexion a cPanel.
    pause
    exit /b 1
)
echo   OK - %DUMP1% creado.

echo.
echo [2/5] Exportando %DB2% desde cPanel...
"C:\xampp\mysql\bin\mysqldump.exe" -h %CPANEL_HOST% -u %DB_USER% -p%DB_PASS% --single-transaction --routines --triggers %DB2% > %DUMP2%
if %errorlevel% neq 0 (
    echo ERROR al exportar %DB2%. Verifica conexion a cPanel.
    pause
    exit /b 1
)
echo   OK - %DUMP2% creado.

echo.
echo [3/5] Instalando MySQL en EC2 y creando bases de datos...
"C:\Program Files\Git\bin\bash.exe" -c "
ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@18.229.55.160 '
  echo Instalando MySQL...
  sudo apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
  sudo systemctl start mysql
  sudo systemctl enable mysql
  echo Creando usuario y bases de datos...
  sudo mysql -e \"CREATE DATABASE IF NOT EXISTS cbo56863_botacurapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"
  sudo mysql -e \"CREATE DATABASE IF NOT EXISTS cbo56863_botacura_iot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"
  sudo mysql -e \"CREATE USER IF NOT EXISTS '"'"'cbo56863'"'"'@'"'"'localhost'"'"' IDENTIFIED BY '"'"'gZbQTjPFVYDzRzTdNmmA'"'"';\"
  sudo mysql -e \"GRANT ALL PRIVILEGES ON cbo56863_botacurapp.* TO '"'"'cbo56863'"'"'@'"'"'localhost'"'"';\"
  sudo mysql -e \"GRANT ALL PRIVILEGES ON cbo56863_botacura_iot.* TO '"'"'cbo56863'"'"'@'"'"'localhost'"'"';\"
  sudo mysql -e \"FLUSH PRIVILEGES;\"
  echo OK - MySQL configurado en EC2
'
"
if %errorlevel% neq 0 (
    echo ERROR configurando MySQL en EC2.
    pause
    exit /b 1
)

echo.
echo [4/5] Importando dumps a EC2...
"C:\Program Files\Git\bin\bash.exe" -c "
ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@18.229.55.160 'sudo mysql cbo56863_botacurapp' < dump_botacurapp.sql
"
if %errorlevel% neq 0 (
    echo ERROR importando %DB1% a EC2.
    pause
    exit /b 1
)
echo   OK - %DB1% importado.

"C:\Program Files\Git\bin\bash.exe" -c "
ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@18.229.55.160 'sudo mysql cbo56863_botacura_iot' < dump_botacura_iot.sql
"
if %errorlevel% neq 0 (
    echo ERROR importando %DB2% a EC2.
    pause
    exit /b 1
)
echo   OK - %DB2% importado.

echo.
echo [5/5] Actualizando .env.production con DB_HOST=127.0.0.1...
"C:\Program Files\Git\bin\bash.exe" -c "
sed -i 's/DB_HOST=ap4.cpanelhost.cl/DB_HOST=127.0.0.1/' .env.production
echo OK - .env.production actualizado
cat .env.production | grep DB_HOST
"

echo.
echo =========================================
echo  DB migrada a EC2. Ejecuta push_and_deploy.bat
echo =========================================
pause
