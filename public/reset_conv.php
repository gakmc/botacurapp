<?php
// SOLO PARA DESARROLLO LOCAL — borrar después de usar
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$telefono = $_GET['tel'] ?? '56961910398';

$rows = \Illuminate\Support\Facades\DB::table('bot_conversaciones')
    ->where('usuario_id', $telefono)
    ->get(['id','usuario_id','telefono','ultimo_mensaje']);

echo "<pre>Conversaciones de {$telefono}:\n";
foreach ($rows as $r) {
    echo "  id={$r->id}  usuario_id={$r->usuario_id}  ultimo_msg=" . substr($r->ultimo_mensaje ?? '', 0, 40) . "\n";
}

$deleted = \Illuminate\Support\Facades\DB::table('bot_conversaciones')
    ->where('usuario_id', $telefono)
    ->delete();

echo "\nBorradas: {$deleted}\n</pre>";
echo "<p>✅ Historial de {$telefono} limpiado. Ya puede iniciar conversación nueva.</p>";
