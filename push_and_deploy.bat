@echo off
title Push + Deploy BotacurApp
cd /d "C:\xampp\htdocs\proyectos\botacurapp"
echo ========================================
echo  Git push + Deploy a produccion
echo ========================================
echo.
echo [1/2] Git push origin Sebastian...
"C:\Program Files\Git\bin\bash.exe" -c "git push origin Sebastian"
if %errorlevel% neq 0 (
    echo ERROR en git push. Abortando.
    pause
    exit /b 1
)
echo.
echo [2/2] Ejecutando deploy.sh...
"C:\Program Files\Git\bin\bash.exe" -c "cd /c/xampp/htdocs/proyectos/botacurapp && bash deploy.sh"
echo.
echo ========================================
echo  Listo. Presiona cualquier tecla.
echo ========================================
pause
