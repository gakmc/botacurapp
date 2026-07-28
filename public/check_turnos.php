<?php
// Diagnóstico turnos semana 20-26 Jul - remover después
$host = '127.0.0.1'; $db = 'cbo56863_botacurapp';
$user = 'cbo56863'; $pass = 'gZbQTjPFVYDzRzTdNmmA';
$pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ver tablas relacionadas con turnos/horarios
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<b>Tablas:</b> " . implode(', ', $tables) . "<br><br>";

// Buscar tablas de turnos
$turno_tables = array_filter($tables, fn($t) => stripos($t,'turno') !== false || stripos($t,'horario') !== false || stripos($t,'asignacion') !== false || stripos($t,'asig') !== false);
echo "<b>Tablas de turnos:</b> " . (implode(', ', $turno_tables) ?: 'ninguna') . "<br><br>";

foreach ($turno_tables as $t) {
    echo "<b>Columnas de $t:</b> ";
    $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $cols) . "<br>";
    // muestra registros de la semana 20-26 Jul
    try {
        $rows = $pdo->query("SELECT * FROM `$t` WHERE created_at BETWEEN '2026-07-20' AND '2026-07-27' OR (SELECT 1 FROM DUAL WHERE 1=0) LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) { echo "<pre>" . print_r($rows[0], true) . "</pre>"; }
    } catch(Exception $e) { /* intentar con fecha */ }
    echo "<br>";
}

// Valor por día de cada funcionario (promedio de sus últimos sueldos)
$target_users = ['Paula Riquelme','Javiera Castro','Juan Guzmán','Juan Guzman','Catalina Maureira',
    'Fernando Morales','Fernando Castro','Oliver Espinoza','Jacinta Guzmán','Jacinta Guzman',
    'Alejandro Cadiz','Catherine Caldentey'];

echo "<hr><b>Valor/día de cada funcionario (promedio últimos 3 meses):</b><br><br>";
echo "<pre>";
$stmt = $pdo->prepare("
    SELECT u.id, u.name,
           ROUND(AVG(s.valor_dia)) as promedio_dia,
           COUNT(s.id) as total_registros,
           MAX(s.dia_trabajado) as ultimo_dia
    FROM users u
    JOIN sueldos s ON s.id_user = u.id
    WHERE s.dia_trabajado >= '2026-04-01'
    AND (u.name LIKE ? OR u.name LIKE ? OR u.name LIKE ? OR u.name LIKE ?
         OR u.name LIKE ? OR u.name LIKE ? OR u.name LIKE ? OR u.name LIKE ?
         OR u.name LIKE ? OR u.name LIKE ?)
    GROUP BY u.id, u.name
    ORDER BY u.name
");
$stmt->execute(['%Paula%','%Javiera%','%Juan G%','%Catalina M%',
                '%Fernando M%','%Fernando C%','%Oliver%','%Jacinta%',
                '%Alejandro%','%Catherine%']);
$rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rates as $r) {
    echo "id={$r['id']}  {$r['name']}  valor_dia=\${$r['promedio_dia']}  registros={$r['total_registros']}  ultimo={$r['ultimo_dia']}\n";
}
echo "</pre>";

// También mostrar si hay tabla de reservas u otro lugar donde se infiera quién trabajó
echo "<hr><b>¿Tablas con fecha de trabajo?</b><br>";
$work_tables = array_filter($tables, fn($t) => in_array($t, ['reservas','asig_turno','turno','turnos','horarios','horario','asig_turnos']));
foreach($work_tables as $wt) {
    echo "<b>$wt</b>: ";
    $cols = $pdo->query("DESCRIBE `$wt`")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $cols) . "<br>";
}
