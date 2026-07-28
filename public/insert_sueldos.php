<?php
// Script de inserción de sueldos 20-26 Jul 2026 con propinas reales - REMOVER DESPUES
if (!isset($_GET['confirm'])) {
    die("Agrega ?confirm=si a la URL para ejecutar los INSERTs");
}

$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=cbo56863_botacurapp;charset=utf8",
    'cbo56863', 'gZbQTjPFVYDzRzTdNmmA', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

header('Content-Type: text/plain; charset=utf-8');

// Igual a CerrarSueldosSemanal: propinas totales por día
$propinas_stmt = $pdo->query("
    SELECT DATE(fecha) as dia, SUM(cantidad) as total_propinas
    FROM propinas
    WHERE fecha BETWEEN '2026-07-20' AND '2026-07-26'
    GROUP BY DATE(fecha)
");
$propinas_por_dia = [];
foreach ($propinas_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $propinas_por_dia[$row['dia']] = (float)$row['total_propinas'];
}

// Cantidad de usuarios asignados por día (igual que el comando)
$asig_stmt = $pdo->query("
    SELECT DATE(a.fecha) as dia, COUNT(au.user_id) as total_usuarios
    FROM asignaciones a
    JOIN asignacion_user au ON au.asignacion_id = a.id
    WHERE a.fecha BETWEEN '2026-07-20' AND '2026-07-26'
    GROUP BY DATE(a.fecha)
");
$usuarios_por_dia = [];
foreach ($asig_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $usuarios_por_dia[$row['dia']] = (int)$row['total_usuarios'];
}

// Propina por persona por día
echo "=== PROPINAS POR DÍA (semana Jul 20-26) ===\n";
$propina_por_persona = [];
foreach ($propinas_por_dia as $dia => $total) {
    $n = isset($usuarios_por_dia[$dia]) ? $usuarios_por_dia[$dia] : 0;
    $pp = $n > 0 ? round($total / $n) : 0;
    $propina_por_persona[$dia] = $pp;
    echo "  $dia: total_propinas=\$$total, personal=$n, por_persona=\$$pp\n";
}
if (empty($propinas_por_dia)) {
    echo "  (Sin propinas registradas esa semana)\n";
}
echo "\n";

// Personas a insertar: valor_dia correcto por persona
// Paula (id=7): $55.000/día, resto: $45.000/día
$inserts = [
    [7,  '2026-07-24', 55000],
    [7,  '2026-07-25', 55000],
    [7,  '2026-07-26', 55000],
    [18, '2026-07-24', 45000],
    [18, '2026-07-25', 45000],
    [38, '2026-07-25', 45000],
    [38, '2026-07-26', 45000],
    [58, '2026-07-25', 45000],
    [58, '2026-07-26', 45000],
    [31, '2026-07-25', 45000],
    [31, '2026-07-26', 45000],
    [11, '2026-07-25', 45000],
    [11, '2026-07-26', 45000],
    [14, '2026-07-25', 45000],
    [14, '2026-07-26', 45000],
    [13, '2026-07-25', 45000],
];

$ok = 0; $skip = 0;
echo "=== INSERTANDO SUELDOS ===\n";
foreach ($inserts as $r) {
    [$uid, $dia, $val_dia] = $r;
    $propina = isset($propina_por_persona[$dia]) ? $propina_por_persona[$dia] : 0;
    $sub     = $val_dia + $propina;
    $total   = $sub;

    $check = $pdo->prepare("SELECT id FROM sueldos WHERE id_user=? AND dia_trabajado=?");
    $check->execute([$uid, $dia]);
    if ($check->fetch()) {
        echo "SKIP: user_id=$uid dia=$dia (ya existe)\n";
        $skip++;
    } else {
        $pdo->prepare("
            INSERT INTO sueldos (id_user, dia_trabajado, valor_dia, sub_sueldo, total_pagar, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ")->execute([$uid, $dia, $val_dia, $sub, $total]);
        echo "OK:   user_id=$uid dia=$dia base=\${$val_dia} propina=\${$propina} total=\${$total}\n";
        $ok++;
    }
}

echo "\n=== RESULTADO ===\n";
echo "Insertados: $ok\n";
echo "Omitidos (ya existían): $skip\n";
echo "\nVerifica en https://app.botacura.cl/sueldos\n";
