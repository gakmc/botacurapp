@echo off
cd /d C:\xampp\htdocs\proyectos\botacurapp
C:\xampp\php\php.exe artisan migrate --path=database/migrations/2026_07_20_000003_add_tipo_servicio_to_menus.php
echo.
pause
