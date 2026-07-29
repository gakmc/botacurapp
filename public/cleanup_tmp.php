<?php
$base = __DIR__;
$files = ['detalle_sueldos.php', 'insert_sueldos.php', 'check_turnos.php'];
foreach ($files as $f) {
    $path = $base . '/' . $f;
    if (file_exists($path)) {
        unlink($path);
        echo "Eliminado: $f\n";
    } else {
        echo "No existe: $f\n";
    }
}
// Self-destruct
unlink(__FILE__);
echo "Limpieza completa.\n";
