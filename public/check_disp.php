<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fecha = $_GET['fecha'] ?? '2026-07-26';
$estadosOcupados = ['pendiente', 'pendiente_pago', 'pago_parcial', 'pagado', 'confirmado'];

echo "<h2>Reservas activas para $fecha</h2>";
$reservas = DB::table('reservas as r')
    ->join('programas as p', 'r.id_programa', '=', 'p.id')
    ->where('r.fecha_visita', $fecha)
    ->whereIn('r.estado', $estadosOcupados)
    ->select('r.id', 'r.estado', 'r.cantidad_personas', 'p.nombre_programa', 'p.espacio_tipo')
    ->get();

echo "<table border=1 cellpadding=5>";
echo "<tr><th>ID</th><th>Estado</th><th>Personas</th><th>Programa</th><th>espacio_tipo</th></tr>";
$slots = 0;
foreach ($reservas as $r) {
    $s = $r->cantidad_personas >= 5 ? 2 : 1;
    $slots += $s;
    echo "<tr><td>{$r->id}</td><td>{$r->estado}</td><td>{$r->cantidad_personas}</td><td>{$r->nombre_programa}</td><td>{$r->espacio_tipo}</td></tr>";
}
echo "</table>";
echo "<p>Slots tinaja usados: <b>$slots / 16</b></p>";

// Contar por espacio_tipo
echo "<h3>Espacios usados por tipo</h3>";
$tipos = ['estacion_economico'=>2,'estacion_intermedio'=>2,'estacion_full'=>5,'terraza'=>6,'reposera'=>4];
foreach ($tipos as $tipo => $max) {
    $count = DB::table('reservas as r')
        ->join('programas as p', 'r.id_programa', '=', 'p.id')
        ->where('r.fecha_visita', $fecha)
        ->whereIn('r.estado', $estadosOcupados)
        ->where('p.espacio_tipo', $tipo)
        ->count();
    $color = $count >= $max ? 'red' : 'green';
    echo "<p style='color:$color'>$tipo: $count / $max</p>";
}

// Pool flexible (terraza+reposera)
$pool = DB::table('reservas as r')
    ->join('programas as p', 'r.id_programa', '=', 'p.id')
    ->where('r.fecha_visita', $fecha)
    ->whereIn('r.estado', $estadosOcupados)
    ->whereIn('p.espacio_tipo', ['terraza', 'reposera'])
    ->count();
$color = $pool >= 10 ? 'red' : 'green';
echo "<p style='color:$color'>Pool terraza+reposera: $pool / 10</p>";

// Programas y sus espacio_tipo
echo "<h3>Todos los programas y espacio_tipo</h3>";
$progs = DB::table('programas')->where('estado','activo')->select('id','nombre_programa','espacio_tipo')->get();
echo "<table border=1 cellpadding=5><tr><th>ID</th><th>Programa</th><th>espacio_tipo</th></tr>";
foreach ($progs as $p) {
    echo "<tr><td>{$p->id}</td><td>{$p->nombre_programa}</td><td>{$p->espacio_tipo}</td></tr>";
}
echo "</table>";
