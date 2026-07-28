<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=cbo56863_botacurapp;charset=utf8",
    'cbo56863', 'gZbQTjPFVYDzRzTdNmmA', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

header('Content-Type: text/plain; charset=utf-8');

// 1. Columnas de asignaciones
echo "=== TABLA asignaciones ===\n";
foreach ($pdo->query("DESCRIBE asignaciones")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . " " . $c['Type'] . "\n";
}
echo "\nMuestra 3 filas:\n";
foreach ($pdo->query("SELECT * FROM asignaciones LIMIT 3")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r) . "\n";
}

// 2. Columnas de asistencias
echo "\n=== TABLA asistencias ===\n";
foreach ($pdo->query("DESCRIBE asistencias")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . " " . $c['Type'] . "\n";
}
echo "\nMuestra 3 filas:\n";
foreach ($pdo->query("SELECT * FROM asistencias LIMIT 3")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r) . "\n";
}

// 3. Asignaciones semana 20-26 Jul
echo "\n=== ASIGNACIONES semana 20-26 Jul ===\n";
$cols = array_column($pdo->query("DESCRIBE asignaciones")->fetchAll(PDO::FETCH_ASSOC), 'Field');
// Buscar columna de fecha
$date_col = null;
foreach (['fecha', 'date', 'dia', 'fecha_inicio', 'start_date', 'created_at'] as $c) {
    if (in_array($c, $cols)) { $date_col = $c; break; }
}
if ($date_col) {
    $stmt = $pdo->prepare("SELECT a.*, u.name FROM asignaciones a LEFT JOIN users u ON u.id = a.id_user OR u.id = a.user_id WHERE `$date_col` BETWEEN '2026-07-20' AND '2026-07-26' LIMIT 50");
    try { $stmt->execute(); foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) echo json_encode($r) . "\n"; }
    catch(Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }
} else {
    echo "No se encontró columna de fecha. Columnas: " . implode(', ', $cols) . "\n";
    // intenta con created_at
    try {
        foreach ($pdo->query("SELECT a.*, u.name FROM asignaciones a LEFT JOIN users u ON u.id = a.id_user WHERE a.created_at BETWEEN '2026-07-20' AND '2026-07-27' LIMIT 20")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            echo json_encode($r) . "\n";
        }
    } catch(Exception $e) { echo "Error2: " . $e->getMessage() . "\n"; }
}

// 4. Valor/día histórico de cada funcionario
echo "\n=== VALOR DIA HISTORICO ===\n";
$names2 = ['Paula','Javiera','Juan G','Catalina M','Fernando M','Fernando C','Oliver','Jacinta','Alejandro','Catherine'];
foreach ($names2 as $n) {
    $s = $pdo->prepare("SELECT u.id, u.name, ROUND(AVG(s.valor_dia)) avg, COUNT(*) cnt FROM users u JOIN sueldos s ON s.id_user=u.id WHERE u.name LIKE ? AND s.dia_trabajado>='2026-01-01' GROUP BY u.id,u.name");
    $s->execute(['%'.$n.'%']);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "id={$r['id']} {$r['name']} avg_dia=\${$r['avg']} registros={$r['cnt']}\n";
    }
}

// 5. Asistencias semana
echo "\n=== TABLA asistencia_user ===\n";
foreach ($pdo->query("DESCRIBE asistencia_user")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . " " . $c['Type'] . "\n";
}
