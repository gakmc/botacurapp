<?php
// Script de inserción de sueldos 20-26 Jul 2026 - REMOVER DESPUES
if (!isset($_GET['confirm'])) {
    die("Agrega ?confirm=si a la URL para ejecutar los INSERTs");
}

$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=cbo56863_botacurapp;charset=utf8",
    'cbo56863', 'gZbQTjPFVYDzRzTdNmmA', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

header('Content-Type: text/plain; charset=utf-8');

$inserts = [
    // Paula Riquelme (id=7): $55.000/día - 3 días
    [7,  '2026-07-24', 55000],
    [7,  '2026-07-25', 55000],
    [7,  '2026-07-26', 55000],
    // Javiera Castro (id=18): $45.000/día - 2 días
    [18, '2026-07-24', 45000],
    [18, '2026-07-25', 45000],
    // Catalina Maureira (id=38): $45.000/día - 2 días
    [38, '2026-07-25', 45000],
    [38, '2026-07-26', 45000],
    // Fernando Morales (id=58): $45.000/día - 2 días
    [58, '2026-07-25', 45000],
    [58, '2026-07-26', 45000],
    // Juan Guzmán (id=31): $45.000/día - 2 días
    [31, '2026-07-25', 45000],
    [31, '2026-07-26', 45000],
    // Natalia Madariaga (id=11): $45.000/día - 2 días
    [11, '2026-07-25', 45000],
    [11, '2026-07-26', 45000],
    // Oliver Espinoza (id=14): $45.000/día - 2 días
    [14, '2026-07-25', 45000],
    [14, '2026-07-26', 45000],
    // Fernando Castro (id=13): $45.000/día - 1 día
    [13, '2026-07-25', 45000],
];

$stmt = $pdo->prepare("
    INSERT IGNORE INTO sueldos (id_user, dia_trabajado, valor_dia, sub_sueldo, total_pagar, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
");

$ok = 0; $skip = 0;
foreach ($inserts as $r) {
    [$uid, $dia, $val] = $r;
    // Verificar si ya existe
    $check = $pdo->prepare("SELECT id FROM sueldos WHERE id_user=? AND dia_trabajado=?");
    $check->execute([$uid, $dia]);
    if ($check->fetch()) {
        echo "SKIP: user_id=$uid dia=$dia (ya existe)\n";
        $skip++;
    } else {
        $stmt->execute([$uid, $dia, $val, $val, $val]);
        echo "OK:   user_id=$uid dia=$dia valor=\${$val}\n";
        $ok++;
    }
}

echo "\n=== RESULTADO ===\n";
echo "Insertados: $ok\n";
echo "Omitidos (ya existían): $skip\n";
echo "\nVerifica en https://app.botacura.cl/sueldos\n";
