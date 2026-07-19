@echo off
echo Iniciando fix de secret HA... > C:\xampp\htdocs\proyectos\botacurapp\fix_secret_output.txt
echo. >> C:\xampp\htdocs\proyectos\botacurapp\fix_secret_output.txt
py C:\xampp\htdocs\proyectos\botacurapp\ha_fix_secret.py >> C:\xampp\htdocs\proyectos\botacurapp\fix_secret_output.txt 2>&1
if errorlevel 1 (
    echo [py fallo, intentando python...] >> C:\xampp\htdocs\proyectos\botacurapp\fix_secret_output.txt
    python C:\xampp\htdocs\proyectos\botacurapp\ha_fix_secret.py >> C:\xampp\htdocs\proyectos\botacurapp\fix_secret_output.txt 2>&1
)
echo. >> C:\xampp\htdocs\proyectos\botacurapp\fix_secret_output.txt
echo FIN DEL SCRIPT >> C:\xampp\htdocs\proyectos\botacurapp\fix_secret_output.txt
