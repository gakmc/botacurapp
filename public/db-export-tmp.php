<?php
/**
 * EXPORTADOR TEMPORAL DE BASE DE DATOS
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. Sube este archivo al servidor de producción (ya está en /public/)
 * 2. Abre en browser: https://app.botacura.cl/db-export-tmp.php?token=botacura2024export
 * 3. Descarga el .sql
 * 4. Importa en phpMyAdmin local (pruebas_botacura)
 * 5. ELIMINA este archivo del servidor después de usarlo
 * ─────────────────────────────────────────────────────────────────────────────
 */

// Token de seguridad — evita que cualquiera lo use
define('TOKEN', 'botacura2024export');

if (empty($_GET['token']) || $_GET['token'] !== TOKEN) {
    http_response_code(403);
    die('Acceso denegado. Usa: ?token=' . TOKEN);
}

// ── Leer credenciales desde el .env de Laravel ──────────────────────────────
$envPath = __DIR__ . '/../.env';
$env = [];
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $env[trim($key)] = trim($val, " \t\n\r\"'");
    }
}

$host = $env['DB_HOST']     ?? '127.0.0.1';
$port = $env['DB_PORT']     ?? '3306';
$db   = $env['DB_DATABASE'] ?? '';
$user = $env['DB_USERNAME'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';

// ── Tablas a exportar ────────────────────────────────────────────────────────
$TABLAS = [
    // Maestros
    'programas',
    'tipos_transacciones',
    'users',
    // Ingresos
    'reservas',
    'ventas',
    'consumos',
    'detalles_consumos',
    'detalle_servicios_extra',
    'ventas_directas',
    'poro_poro_ventas',
    // Egresos
    'honorarios_bte',
    'egresos',
    'sii_resumen_mensual',
];

// Cuántos meses hacia atrás exportar
$MESES = isset($_GET['meses']) ? (int)$_GET['meses'] : 14;
$DESDE = date('Y-m-d', strtotime("-{$MESES} months", strtotime(date('Y-m-01'))));

// ── Conectar ─────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die('Error conexión: ' . $e->getMessage());
}

// ── Headers de descarga ───────────────────────────────────────────────────────
$filename = 'botacura_export_' . date('Ymd_His') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

// ── Función: exportar tabla ───────────────────────────────────────────────────
function exportarTabla(PDO $pdo, $tabla, $stmt) {
    echo "\n-- ─────────────────────────────────────────────\n";
    echo "-- Tabla: {$tabla}\n";
    echo "-- ─────────────────────────────────────────────\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n";
    echo "TRUNCATE TABLE `{$tabla}`;\n";

    $res = $pdo->query("SELECT * FROM `{$tabla}` WHERE " . $stmt);
    $filas = $res->fetchAll(PDO::FETCH_ASSOC);

    if (empty($filas)) {
        echo "-- (sin registros en el período)\n";
        return 0;
    }

    // Columnas
    $cols = '`' . implode('`, `', array_keys($filas[0])) . '`';

    // Insertar en lotes de 200
    $lote = [];
    $total = 0;
    foreach ($filas as $fila) {
        $vals = array_map(function($v) use ($pdo) {
            if ($v === null) return 'NULL';
            return $pdo->quote($v);
        }, array_values($fila));
        $lote[] = '(' . implode(', ', $vals) . ')';
        $total++;

        if (count($lote) >= 200) {
            echo "INSERT INTO `{$tabla}` ({$cols}) VALUES\n" . implode(",\n", $lote) . ";\n";
            $lote = [];
        }
    }
    if (!empty($lote)) {
        echo "INSERT INTO `{$tabla}` ({$cols}) VALUES\n" . implode(",\n", $lote) . ";\n";
    }

    return $total;
}

// ── Cabecera del SQL ──────────────────────────────────────────────────────────
echo "-- ═══════════════════════════════════════════════════════\n";
echo "-- BotacurApp — Export de producción\n";
echo "-- Generado: " . date('Y-m-d H:i:s') . "\n";
echo "-- Base de datos origen: {$db}\n";
echo "-- Período: desde {$DESDE} ({$MESES} meses)\n";
echo "-- IMPORTAR en: pruebas_botacura\n";
echo "-- ═══════════════════════════════════════════════════════\n";
echo "\nSET NAMES utf8;\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n";

// ── Exportar cada tabla ───────────────────────────────────────────────────────
$tablasSinFiltro = ['programas', 'tipos_transacciones', 'users', 'sii_resumen_mensual'];

foreach ($TABLAS as $tabla) {
    try {
        // Verificar que la tabla existe
        $pdo->query("SELECT 1 FROM `{$tabla}` LIMIT 1");
    } catch (Exception $e) {
        echo "\n-- SKIP {$tabla}: no existe\n";
        continue;
    }

    if (in_array($tabla, $tablasSinFiltro)) {
        $stmt = '1=1';
    } elseif ($tabla === 'honorarios_bte') {
        $periodoMin = date('Ym', strtotime("-{$MESES} months"));
        $stmt = "periodo >= '{$periodoMin}'";
    } elseif ($tabla === 'egresos') {
        $periodoMin = date('Y-m', strtotime("-{$MESES} months"));
        $stmt = "periodo_sii >= '{$periodoMin}' OR periodo_sii IS NULL OR created_at >= '{$DESDE}'";
    } elseif ($tabla === 'reservas') {
        $stmt = "fecha_visita >= '{$DESDE}'";
    } elseif (in_array($tabla, ['ventas_directas', 'poro_poro_ventas'])) {
        $stmt = "fecha >= '{$DESDE}'";
    } else {
        $stmt = "created_at >= '{$DESDE}'";
    }

    exportarTabla($pdo, $tabla, $stmt);
}

echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
echo "\n-- ═══════════════════════════════════════════════════════\n";
echo "-- Export completo\n";
echo "-- ═══════════════════════════════════════════════════════\n";
