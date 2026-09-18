@echo off
title Setup MySQL en EC2 + Importar DB
cd /d "C:\xampp\htdocs\proyectos\botacurapp"
echo =========================================
echo  Setup MySQL en EC2 + Importar datos
echo =========================================
echo.

if not exist dump_botacurapp.sql (
    echo ERROR: No se encontro dump_botacurapp.sql en esta carpeta.
    echo Exporta primero desde tu gestor de DB.
    pause
    exit /b 1
)
if not exist dump_botacura_iot.sql (
    echo ERROR: No se encontro dump_botacura_iot.sql en esta carpeta.
    echo Exporta primero desde tu gestor de DB.
    pause
    exit /b 1
)

echo [1/4] Instalando MySQL en EC2 y creando usuario/bases de datos...
"C:\Program Files\Git\bin\bash.exe" -c "
ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@54.233.214.142 '
  sudo apt-get update -qq &&
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server &&
  sudo systemctl start mysql &&
  sudo systemctl enable mysql &&
  sudo mysql -e \"CREATE DATABASE IF NOT EXISTS cbo56863_botacurapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\" &&
  sudo mysql -e \"CREATE DATABASE IF NOT EXISTS cbo56863_botacura_iot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\" &&
  sudo mysql -e \"CREATE USER IF NOT EXISTS '"'"'cbo56863'"'"'@'"'"'localhost'"'"' IDENTIFIED BY '"'"'gZbQTjPFVYDzRzTdNmmA'"'"';\" &&
  sudo mysql -e \"ALTER USER '"'"'cbo56863'"'"'@'"'"'localhost'"'"' IDENTIFIED BY '"'"'gZbQTjPFVYDzRzTdNmmA'"'"';\" &&
  sudo mysql -e \"GRANT ALL PRIVILEGES ON cbo56863_botacurapp.* TO '"'"'cbo56863'"'"'@'"'"'localhost'"'"';\" &&
  sudo mysql -e \"GRANT ALL PRIVILEGES ON cbo56863_botacura_iot.* TO '"'"'cbo56863'"'"'@'"'"'localhost'"'"';\" &&
  sudo mysql -e \"FLUSH PRIVILEGES;\" &&
  echo OK MySQL listo
'
"
if %errorlevel% neq 0 (
    echo ERROR configurando MySQL en EC2.
    pause
    exit /b 1
)

echo.
echo [2/4] Copiando dumps a EC2...
"C:\Program Files\Git\bin\bash.exe" -c "scp -i botacurapp.pem -o StrictHostKeyChecking=no dump_botacurapp.sql dump_botacura_iot.sql ubuntu@54.233.214.142:/tmp/"
if %errorlevel% neq 0 (
    echo ERROR copiando dumps a EC2.
    pause
    exit /b 1
)

echo.
echo [3/4] Importando bases de datos en EC2...
"C:\Program Files\Git\bin\bash.exe" -c "
ssh -i botacurapp.pem -o StrictHostKeyChecking=no ubuntu@54.233.214.142 '
  echo Importando cbo56863_botacurapp... &&
  sudo mysql cbo56863_botacurapp < /tmp/dump_botacurapp.sql &&
  echo Importando cbo56863_botacura_iot... &&
  sudo mysql cbo56863_botacura_iot < /tmp/dump_botacura_iot.sql &&
  rm /tmp/dump_botacurapp.sql /tmp/dump_botacura_iot.sql &&
  echo OK importacion completa
'
"
if %errorlevel% neq 0 (
    echo ERROR importando datos.
    pause
    exit /b 1
)

echo.
echo [4/4] Actualizando .env.production a DB local (127.0.0.1)...
"C:\Program Files\Git\bin\bash.exe" -c "sed -i 's/DB_HOST=ap4.cpanelhost.cl/DB_HOST=127.0.0.1/g; s/DB_IOT_HOST=ap4.cpanelhost.cl/DB_IOT_HOST=127.0.0.1/g' .env.production && echo OK .env.production actualizado"

echo.
echo =========================================
echo  Listo! Ahora ejecuta push_and_deploy.bat
echo =========================================
pause
