<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=cbo56863_botacurapp;charset=utf8",
    'cbo56863', 'gZbQTjPFVYDzRzTdNmmA', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 1. Turnos asignados semana 20-26 Jul (vía pivot asignacion_user)
$turnos = $pdo->query("
    SELECT a.fecha, u.id AS user_id, u.name
    FROM asignaciones a
    JOIN asignacion_user au ON au.asignacion_id = a.id
    JOIN users u ON u.id = au.user_id
    WHERE a.fecha BETWEEN '2026-07-20' AND '2026-07-26'
    ORDER BY u.name, a.fecha
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Valor/día promedio de cada persona (últimos sueldos 2026)
$rates_stmt = $pdo->query("
    SELECT s.id_user, ROUND(AVG(s.valor_dia)) AS avg_dia
    FROM sueldos s
    WHERE s.dia_trabajado BETWEEN '2026-01-01' AND '2026-07-19'
    GROUP BY s.id_user
");
$rates = [];
foreach ($rates_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $rates[$r['id_user']] = $r['avg_dia'];
}

// 3. Ver qué sueldos YA existen esa semana
$existing = $pdo->query("
    SELECT id_user, dia_trabajado
    FROM sueldos
    WHERE dia_trabajado BETWEEN '2026-07-20' AND '2026-07-26'
")->fetchAll(PDO::FETCH_ASSOC);
$exists_set = [];
foreach ($existing as $e) {
    $exists_set[$e['id_user'].'_'.$e['dia_trabajado']] = true;
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== TURNOS ASIGNADOS 20-26 Jul 2026 ===\n";
echo count($turnos) . " registros encontrados\n\n";

$inserts = [];
$by_user = [];
foreach ($turnos as $t) {
    $uid = $t['user_id'];
    $by_user[$uid]['name'] = $t['name'];
    $by_user[$uid]['dias'][] = $t['fecha'];
    $by_user[$uid]['avg_dia'] = isset($rates[$uid]) ? $rates[$uid] : 0;
}

foreach ($by_user as $uid => $info) {
    $dias = count($info['dias']);
    $avg = $info['avg_dia'];
    $total = $dias * $avg;
    $already = 0;
    foreach ($info['dias'] as $d) {
        if (isset($exists_set[$uid.'_'.$d])) $already++;
    }
    echo "id=$uid  {$info['name']}  dias=$dias  valor_dia=\${$avg}  total=\${$total}  ya_en_sueldos=$already\n";
    echo "  fechas: " . implode(', ', $info['dias']) . "\n";
}

// 4. Generar INSERTs para los que faltan
echo "\n=== SQL PARA INSERTAR LOS FALTANTES ===\n";
echo "-- Pega esto en el gestor de DB de produccion (MySQL local EC2)\n\n";
foreach ($by_user as $uid => $info) {
    foreach ($info['dias'] as $d) {
        $key = $uid.'_'.$d;
        if (!isset($exists_set[$key])) {
            $avg = $info['avg_dia'];
            echo "INSERT INTO sueldos (id_user, dia_trabajado, valor_dia, sub_sueldo, total_pagar, created_at, updated_at) VALUES ($uid, '$d', $avg, $avg, $avg, NOW(), NOW());\n";
            $inserts[] = $uid.'_'.$d;
        }
    }
}
echo "\n-- Total INSERTs a ejecutar: " . count($inserts) . "\n";
