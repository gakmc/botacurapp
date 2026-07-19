<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$tok = isset($_GET['token']) ? $_GET['token'] : '';
if ($tok !== 'fix_botacura_2026') { echo 'denied'; exit; }
$base = '/home3/cbo56863/botacurApp';
echo "=== FIX DEPLOY === " . date('Y-m-d H:i:s') . "\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "Public dir: " . __DIR__ . "\n";
echo "App dir exists: " . (is_dir($base) ? "YES" : "NO") . "\n";
echo "artisan: " . (file_exists("$base/artisan") ? "YES" : "NO") . "\n";
echo "TinajaCtrl: " . (file_exists("$base/app/Http/Controllers/TinajaController.php") ? "YES" : "NO") . "\n";
echo "routes/api.php: " . (file_exists("$base/routes/api.php") ? "YES" : "NO") . "\n";

// Run composer dump-autoload
$php = PHP_BINARY;
echo "\n=== COMPOSER DUMP-AUTOLOAD ===\n";
$out = array();
exec("cd $base && $php vendor/bin/composer dump-autoload --optimize 2>&1", $out, $code);
echo implode("\n", array_slice($out, -5)) . "\nExit: $code\n";

// Route clear
echo "\n=== ARTISAN ROUTE:CLEAR ===\n";
$out2 = array();
exec("cd $base && $php artisan route:clear 2>&1", $out2, $c2);
echo implode("\n", $out2) . "\nExit: $c2\n";

// Route list - find tinaja
echo "\n=== ROUTE LIST (tinaja) ===\n";
$out3 = array();
exec("cd $base && $php artisan route:list 2>&1 | grep -i tinaja", $out3, $c3);
echo (empty($out3) ? "(tinaja routes NOT found)" : implode("\n", $out3)) . "\nExit: $c3\n";

// All IoT routes
$out4 = array();
exec("cd $base && $php artisan route:list 2>&1 | grep iot", $out4);
echo "\nIoT routes: " . (empty($out4) ? "(none)" : implode("\n", $out4)) . "\n";

// Check classmap
echo "\n=== CLASSMAP CHECK ===\n";
$classmap_file = "$base/vendor/composer/autoload_classmap.php";
if (file_exists($classmap_file)) {
    $classmap = include $classmap_file;
    $key = 'App\\Http\\Controllers\\TinajaController';
    echo "In classmap: " . (isset($classmap[$key]) ? "YES => " . $classmap[$key] : "NOT FOUND") . "\n";
} else {
    echo "classmap file not found\n";
}

// Test call the controller class
echo "\n=== LOAD AUTOLOADER ===\n";
require_once "$base/vendor/autoload.php";
echo "TinajaController class: " . (class_exists('App\\Http\\Controllers\\TinajaController') ? "FOUND" : "NOT FOUND") . "\n";

// Tail laravel.log
echo "\n=== LARAVEL LOG (last 30 lines) ===\n";
$logfile = "$base/storage/logs/laravel.log";
if (file_exists($logfile)) {
    $lines = array();
    exec("tail -30 $logfile 2>&1", $lines);
    echo implode("\n", $lines) . "\n";
} else {
    echo "(log not found)\n";
}

echo "\n=== DONE ===\n";
?>
