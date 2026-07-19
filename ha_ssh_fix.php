<?php
header('Content-Type: text/plain; charset=utf-8');

$host = '192.168.100.73';
$user = 'botacura';
$pass = 'Botacura13274';
$config_dir = '/home/botacura/homeassistant/config';

if (!function_exists('ssh2_connect')) {
    echo "SSH2 no disponible.\n";
    echo "Extensions: " . implode(', ', get_loaded_extensions()) . "\n";
    exit;
}

$connection = @ssh2_connect($host, 22);
if (!$connection) { echo "ERROR: No se pudo conectar\n"; exit; }
if (!@ssh2_auth_password($connection, $user, $pass)) { echo "ERROR: Auth fallida\n"; exit; }

echo "Conectado!\n\n";

function run($conn, $cmd) {
    $s = ssh2_exec($conn, $cmd);
    stream_set_blocking($s, true);
    $out = stream_get_contents($s);
    fclose($s);
    return $out;
}

echo "=== secrets.yaml ===\n";
echo run($connection, "cat $config_dir/secrets.yaml 2>/dev/null || echo '(no existe)'");

echo "\n=== Referencias a botacura_iot_token ===\n";
echo run($connection, "grep -rn 'botacura_iot_token' $config_dir/ 2>/dev/null || echo '(ninguna)'");

$count = intval(trim(run($connection, "grep -c 'botacura_iot_token' $config_dir/secrets.yaml 2>/dev/null || echo 0")));
echo "\nYa en secrets.yaml: $count\n";

if ($count == 0) {
    $r = run($connection, "printf '\\nbotacura_iot_token: token_pendiente_configurar\\n' >> $config_dir/secrets.yaml && echo OK");
    echo "Agregar resultado: $r\n";
    echo "=== secrets.yaml FINAL ===\n";
    echo run($connection, "cat $config_dir/secrets.yaml");
}

echo "\nDone!\n";
