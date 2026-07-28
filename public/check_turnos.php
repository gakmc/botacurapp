<?php
$host = '127.0.0.1'; $db = 'cbo56863_botacurapp';
$user = 'cbo56863'; $pass = 'gZbQTjPFVYDzRzTdNmmA';
$pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Todas las tablas
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<b>Tablas:</b> " . implode(', ', $tables) . "<br><br>";

// Tablas de turnos
$turno_tables = [];
foreach ($tables as $t) {
    if (stripos($t,'turno') !== false || stripos($t,'horario') !== false || stripos($t,'asig') !== false) {
        $turno_tables[] = $t;
    }
}
echo "<b>Tablas turno/horario/asig:</b> " . (implode(', ', $turno_tables) ?: 'ninguna') . "<br><br>";

foreach ($turno_tables as $t) {
    $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_COLUMN);
    echo "<b>$t</b>: " . implode(', ', $cols) . "<br>";
    $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "  Total registros: $cnt<br>";
    // muestra muestra
    $sample = $pdo->query("SELECT * FROM `$t` LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    if ($sample) echo "<pre style='font-size:11px'>" . print_r($sample, true) . "</pre>";
    echo "<br>";
}

// Valor por día de cada funcionario (de sueldos recientes)
echo "<hr><b>Valor/día histórico de los funcionarios faltantes:</b><br><pre>";
$names = ['Paula', 'Javiera', 'Juan G', 'Catalina M', 'Fernando M', 'Fernando C', 'Oliver', 'Jacinta', 'Alejandro', 'Catherine'];
foreach ($names as $n) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, ROUND(AVG(s.valor_dia)) as avg_dia, COUNT(s.id) as cnt
        FROM users u JOIN sueldos s ON s.id_user = u.id
        WHERE u.name LIKE ? AND s.dia_trabajado >= '2026-01-01'
        GROUP BY u.id, u.name
    ");
    $stmt->execute(['%'.$n.'%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "id={$r['id']}  {$r['name']}  avg_dia=\${$r['avg_dia']}  registros={$r['cnt']}\n";
    }
}
echo "</pre>";

// Reservas de esa semana (para ver quién trabajó)
if (in_array('reservas', $tables)) {
    echo "<hr><b>Reservas 20-26 Jul (para inferir quién trabajó):</b><br><pre style='font-size:11px'>";
    $reservas = $pdo->query("SELECT * FROM reservas WHERE fecha_reserva BETWEEN '2026-07-20' AND '2026-07-26' LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
    echo print_r($reservas, true);
    echo "</pre>";
}
