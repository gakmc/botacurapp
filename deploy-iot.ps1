# deploy-iot.ps1 — Despliega el endpoint IoT de tinajas en el servidor AWS
# Ejecutar desde PowerShell en la carpeta botacurapp

$KEY = "$env:USERPROFILE\Desktop\botacurapp\botacurapp.pem"
$SERVER = "ubuntu@ec2-54-233-214-142.sa-east-1.compute.amazonaws.com"

Write-Host "=== PASO 1: Buscando la ruta del proyecto en AWS ===" -ForegroundColor Cyan
$appPath = ssh -i $KEY $SERVER "find /var/www /home/ubuntu -name 'artisan' 2>/dev/null | head -3"
Write-Host "artisan encontrado en: $appPath"

if (-not $appPath) {
    Write-Host "No se encontro artisan. Buscando mas amplio..." -ForegroundColor Yellow
    ssh -i $KEY $SERVER "ls /var/www/ 2>/dev/null; ls /home/ubuntu/ 2>/dev/null"
    $appDir = Read-Host "Ingresa la ruta del proyecto (ej: /var/www/botacurapp)"
} else {
    # artisan esta en /ruta/del/proyecto/artisan -> sacar el directorio
    $appDir = ($appPath -split "`n")[0].TrimEnd("/artisan").Trim()
}

$publicDir = "$appDir/public"
Write-Host "Directorio publico: $publicDir" -ForegroundColor Green

Write-Host ""
Write-Host "=== PASO 2: Creando endpoint standalone IoT ===" -ForegroundColor Cyan

# Contenido del archivo PHP en base64 para transferirlo sin problemas de escape
$phpContent = @'
<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
$env = [];
foreach (file(dirname(__DIR__, 5) . '/.env') as $line) {
  $line = trim($line);
  if ($line && $line[0] !== '#' && strpos($line,'=') !== false) {
    [$k,$v] = explode('=',$line,2);
    $env[trim($k)] = trim($v," \"'");
  }
}
try {
  $pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'],
    $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
  $sql = "SELECT r.fecha_visita, v.horario_tinaja, CONCAT(r.fecha_visita,' ',v.horario_tinaja) AS datetime_reserva, c.nombre_cliente AS cliente FROM visitas v JOIN reservas r ON v.id_reserva = r.id LEFT JOIN clientes c ON r.cliente_id = c.id WHERE v.horario_tinaja IS NOT NULL AND TIME_FORMAT(v.horario_tinaja,'%i') = :m AND CONCAT(r.fecha_visita,' ',v.horario_tinaja) > NOW() ORDER BY r.fecha_visita ASC, v.horario_tinaja ASC LIMIT 1";
  function getNext($pdo, $sql, $m) {
    $s = $pdo->prepare($sql);
    $s->execute([':m' => $m]);
    $r = $s->fetch(PDO::FETCH_OBJ);
    if (!$r) return null;
    return ['fecha_visita' => $r->fecha_visita, 'horario' => substr($r->horario_tinaja, 0, 5), 'datetime_reserva' => $r->datetime_reserva, 'cliente' => $r->cliente ?? 'Sin nombre'];
  }
  echo json_encode(['ok' => true, 'tinaja_1' => getNext($pdo, $sql, '45'), 'tinaja_2' => getNext($pdo, $sql, '15'), 'consultado_en' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
'@

# Transferir el archivo via SSH
$b64 = [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($phpContent))

$sshCmd = @"
set -e
mkdir -p $publicDir/api/iot/tinajas/proxima-reserva
echo '$b64' | base64 -d > $publicDir/api/iot/tinajas/proxima-reserva/index.php
echo "Archivo creado OK"
cat $publicDir/api/iot/tinajas/proxima-reserva/index.php | head -3
"@

ssh -i $KEY $SERVER $sshCmd

Write-Host ""
Write-Host "=== PASO 3: Verificando endpoint ===" -ForegroundColor Cyan
Start-Sleep -Seconds 2
$result = Invoke-WebRequest -Uri "https://app.botacura.cl/api/iot/tinajas/proxima-reserva" -UseBasicParsing -ErrorAction SilentlyContinue
Write-Host "HTTP Status: $($result.StatusCode)"
Write-Host "Respuesta: $($result.Content)"

Write-Host ""
Write-Host "=== LISTO ===" -ForegroundColor Green
Read-Host "Presiona Enter para cerrar"
