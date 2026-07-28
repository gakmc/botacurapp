<?php
// DB diagnostic - remover después
$host = '127.0.0.1';
$db   = 'cbo56863_botacurapp';
$user = 'cbo56863';
$pass = 'gZbQTjPFVYDzRzTdNmmA';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Conexión fallida: ' . $e->getMessage());
}

// hostname MySQL
$host_row = $pdo->query("SELECT @@hostname AS h, @@version AS v")->fetch(PDO::FETCH_ASSOC);

// sueldos semana 20 Jul - 26 Jul
$stmt = $pdo->prepare("
    SELECT s.id, s.id_user, s.dia_trabajado, s.valor_dia, s.sub_sueldo, s.total_pagar,
           u.name AS user_name
    FROM sueldos s
    LEFT JOIN users u ON u.id = s.id_user
    WHERE s.dia_trabajado BETWEEN '2026-07-20' AND '2026-07-26'
    ORDER BY s.id_user, s.dia_trabajado
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = $pdo->query("SELECT COUNT(*) FROM sueldos WHERE dia_trabajado BETWEEN '2026-07-20' AND '2026-07-26'")->fetchColumn();

header('Content-Type: text/html; charset=utf-8');
echo "<pre style='font-size:13px'>";
echo "MySQL host: {$host_row['h']}  version: {$host_row['v']}\n";
echo "Registros 20-26 Jul: $total\n\n";
foreach ($rows as $r) {
    echo "id={$r['id']}  user_id={$r['id_user']}  nombre={$r['user_name']}  dia={$r['dia_trabajado']}  total={$r['total_pagar']}\n";
}
echo "</pre>";
