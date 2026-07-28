Set-Location "C:\xampp\htdocs\proyectos\botacurapp"
$bash = "C:\Program Files\Git\bin\bash.exe"

Write-Host "========================================" -ForegroundColor Green
Write-Host " Git push + Deploy a produccion" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

Write-Host ""
Write-Host "[1/2] Git push origin Sebastian..." -ForegroundColor Cyan
& $bash -c "git push origin Sebastian"
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR en git push" -ForegroundColor Red; Read-Host; exit 1 }

Write-Host ""
Write-Host "[2/2] Ejecutando deploy.sh en EC2..." -ForegroundColor Cyan
& $bash -c "cd /c/xampp/htdocs/proyectos/botacurapp && bash deploy.sh"
if ($LASTEXITCODE -ne 0) { Write-Host "ERROR en deploy" -ForegroundColor Red; Read-Host; exit 1 }

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " Deploy completado exitosamente!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Read-Host "Presiona Enter para salir"
