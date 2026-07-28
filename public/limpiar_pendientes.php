<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Solo accesible localmente
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
    die('Solo acceso local.');
}

$confirm = $_GET['confirmar'] ?? '';

// Ver qué se va a borrar
$pendientes = DB::table('reservas')
    ->whereIn('estado', ['pendiente', 'pendiente_pago'])
    ->orderBy('fecha_visita')
    ->select('id', 'estado', 'fecha_visita', 'cantidad_personas', 'id_programa')
    ->get();

echo "<h2>Reservas pendiente/pendiente_pago a eliminar</h2>";
echo "<p>Total: <b>{$pendientes->count()}</b></p>";
echo "<table border=1 cellpadding=5>";
echo "<tr><th>ID</th><th>Estado</th><th>Fecha visita</th><th>Personas</th><th>programa_id</th></tr>";
foreach ($pendientes as $r) {
    echo "<tr><td>{$r->id}</td><td>{$r->estado}</td><td>{$r->fecha_visita}</td><td>{$r->cantidad_personas}</td><td>{$r->id_programa}</td></tr>";
}
echo "</table>";

if ($confirm === 'si') {
    // Borrar registros relacionados primero
    $ids = $pendientes->pluck('id')->toArray();

    DB::table('visitas')->whereIn('id_reserva', $ids)->delete();
    DB::table('menus')->whereIn('id_reserva', $ids)->delete();
    DB::table('masajes')->whereIn('id_reserva', $ids)->delete();
    DB::table('ventas')->whereIn('id_reserva', $ids)->delete();
    $deleted = DB::table('reservas')->whereIn('id', $ids)->delete();

    echo "<h3 style='color:green'>✅ Eliminadas $deleted reservas y sus registros relacionados.</h3>";
} else {
    echo "<br><a href='?confirmar=si' style='background:red;color:white;padding:10px;text-decoration:none;font-size:16px'>⚠️ CONFIRMAR ELIMINACIÓN</a>";
    echo "<p style='color:gray'>Esto eliminará TODAS las reservas en estado pendiente y pendiente_pago de toda la base de datos.</p>";
}
