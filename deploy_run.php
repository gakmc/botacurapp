<?php
// Script de deploy temporal - ELIMINAR DESPUES DE USAR
// Acceder via: https://app.botacura.cl/deploy_run.php?token=botacura_deploy_2026

$token = $_GET['token'] ?? '';
if ($token !== 'botacura_deploy_2026') {
    die('Acceso denegado');
}

$base = dirname(__FILE__);
$output = [];

function run($cmd, &$output) {
    $out = [];
    exec($cmd . ' 2>&1', $out, $code);
    $output[] = "$ $cmd";
    $output[] = implode("\n", $out);
    $output[] = "Exit: $code";
    $output[] = "---";
    return $code;
}

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:12px;'>";
echo "=== BOTACURAPP DEPLOY ===\n";
echo "Dir: $base\n\n";

// 1. Git status
run("cd $base && git status 2>&1 | head -5", $output);
run("cd $base && git remote -v 2>&1", $output);

// 2. Git pull desde upstream (swimmerw/botacurapp)
echo "=== GIT PULL ===\n";
run("cd $base && git fetch --all 2>&1", $output);
run("cd $base && git pull origin main 2>&1", $output);

// 3. Composer install (sin dev)
echo "=== COMPOSER ===\n";
run("cd $base && composer install --no-dev --optimize-autoloader 2>&1 | tail -5", $output);

// 4. Artisan commands
echo "=== ARTISAN ===\n";
run("cd $base && php artisan migrate --force 2>&1", $output);
run("cd $base && php artisan config:cache 2>&1", $output);
run("cd $base && php artisan route:cache 2>&1", $output);
run("cd $base && php artisan view:cache 2>&1", $output);

// 5. Verificar endpoint IoT
run("curl -s https://app.botacura.cl/api/iot/tinajas/proxima-reserva 2>&1 | head -c 200", $output);

foreach ($output as $line) {
    echo htmlspecialchars($line) . "\n";
}

echo "\n=== DONE - ELIMINA ESTE ARCHIVO ===\n";
echo "</pre>";
?>
