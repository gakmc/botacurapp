<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=cbo56863_botacurapp;charset=utf8",
    'cbo56863', 'gZbQTjPFVYDzRzTdNmmA', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

header('Content-Type: text/plain; charset=utf-8');

// 1. Propinas totales por día
$propinas_stmt = $pdo->query("
    SELECT DATE(fecha) as dia, SUM(cantidad) as total_propinas
    FROM propinas
    WHERE fecha BETWEEN '2026-07-20' AND '2026-07-26'
    GROUP BY DATE(fecha)
    ORDER BY dia
");
$propinas_por_dia = [];
foreach ($propinas_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $propinas_por_dia[$row['dia']] = (float)$row['total_propinas'];
}

// 2. Personas asignadas por día
$asig_stmt = $pdo->query("
    SELECT DATE(a.fecha) as dia, COUNT(au.user_id) as total_personas
    FROM asignaciones a
    JOIN asignacion_user au ON au.asignacion_id = a.id
    WHERE a.fecha BETWEEN '2026-07-20' AND '2026-07-26'
    GROUP BY DATE(a.fecha)
    ORDER BY dia
");
$personas_por_dia = [];
foreach ($asig_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $personas_por_dia[$row['dia']] = (int)$row['total_personas'];
}

// Propina por persona por día
$propina_pp = [];
foreach ($propinas_por_dia as $dia => $total) {
    $n = isset($personas_por_dia[$dia]) ? $personas_por_dia[$dia] : 1;
    $propina_pp[$dia] = $n > 0 ? round($total / $n) : 0;
}

echo "=== PROPINAS POR DÍA (Jul 20-26 2026) ===\n";
echo str_pad("Día", 14) . str_pad("Total propinas", 18) . str_pad("Personas", 11) . "Por persona\n";
echo str_repeat("-", 55) . "\n";
foreach ($propinas_por_dia as $dia => $total) {
    $n  = $personas_por_dia[$dia] ?? 0;
    $pp = $propina_pp[$dia] ?? 0;
    echo str_pad($dia, 14) . str_pad("$".number_format($total,0,',','.'), 18) . str_pad($n, 11) . "$".number_format($pp,0,',','.') . "\n";
}
if (empty($propinas_por_dia)) {
    echo "  (Sin propinas registradas esa semana)\n";
}

// 3. Turnos de las personas faltantes
$turnos_stmt = $pdo->query("
    SELECT u.id, u.name, DATE(a.fecha) as dia
    FROM asignaciones a
    JOIN asignacion_user au ON au.asignacion_id = a.id
    JOIN users u ON u.id = au.user_id
    WHERE a.fecha BETWEEN '2026-07-20' AND '2026-07-26'
      AND u.id IN (7, 18, 38, 58, 31, 11, 14, 13)
    ORDER BY u.name, a.fecha
");
$turnos = $turnos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por usuario
$by_user = [];
foreach ($turnos as $t) {
    $uid = $t['id'];
    if (!isset($by_user[$uid])) {
        $by_user[$uid] = ['name' => $t['name'], 'dias' => []];
    }
    $by_user[$uid]['dias'][] = $t['dia'];
}

// Valor día por persona
$valor_dia = [7 => 55000];  // Paula
$default_valor = 45000;

// Sueldos ya existentes esa semana para estos usuarios
$ya_stmt = $pdo->query("
    SELECT id_user, DATE(dia_trabajado) as dia
    FROM sueldos
    WHERE dia_trabajado BETWEEN '2026-07-20' AND '2026-07-26'
      AND id_user IN (7, 18, 38, 58, 31, 11, 14, 13)
");
$ya_existe = [];
foreach ($ya_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ya_existe[$row['id_user'].'_'.$row['dia']] = true;
}

echo "\n\n=== DETALLE POR PERSONA ===\n";
echo str_repeat("=", 65) . "\n";

$gran_total = 0;
foreach ($by_user as $uid => $info) {
    $vd = isset($valor_dia[$uid]) ? $valor_dia[$uid] : $default_valor;
    $nombre = $info['name'];

    echo "\n$nombre (id=$uid)  —  valor_dia=\$".number_format($vd,0,',','.')."\n";
    echo str_pad("  Fecha", 16) . str_pad("Base", 12) . str_pad("Propina", 12) . str_pad("Total día", 12) . "Estado\n";
    echo "  " . str_repeat("-", 55) . "\n";

    $subtotal_base = 0;
    $subtotal_prop = 0;
    $subtotal_tot  = 0;

    foreach ($info['dias'] as $dia) {
        $pp     = isset($propina_pp[$dia]) ? $propina_pp[$dia] : 0;
        $total  = $vd + $pp;
        $estado = isset($ya_existe[$uid.'_'.$dia]) ? 'ya_existe' : 'A INSERTAR';

        echo str_pad("  ".$dia, 16)
           . str_pad("$".number_format($vd,0,',','.'), 12)
           . str_pad("$".number_format($pp,0,',','.'), 12)
           . str_pad("$".number_format($total,0,',','.'), 12)
           . $estado . "\n";

        $subtotal_base += $vd;
        $subtotal_prop += $pp;
        $subtotal_tot  += $total;
    }

    echo "  " . str_repeat("-", 55) . "\n";
    echo str_pad("  SUBTOTAL", 16)
       . str_pad("$".number_format($subtotal_base,0,',','.'), 12)
       . str_pad("$".number_format($subtotal_prop,0,',','.'), 12)
       . "$".number_format($subtotal_tot,0,',','.') . "\n";

    $gran_total += $subtotal_tot;
}

echo "\n" . str_repeat("=", 65) . "\n";
echo "GRAN TOTAL A INSERTAR: $".number_format($gran_total,0,',','.') . "\n";
echo str_repeat("=", 65) . "\n";
echo "\nSi confirmas, ejecuta: https://app.botacura.cl/insert_sueldos.php?confirm=si\n";
