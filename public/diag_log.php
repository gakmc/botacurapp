<?php
// Diagnóstico temporal - remover después
$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logPath)) { die('Log no encontrado en: ' . $logPath); }
$lines = file($logPath);
$last  = array_slice($lines, -80);
echo '<pre style="font-size:12px;word-wrap:break-word;">';
echo htmlspecialchars(implode('', $last));
echo '</pre>';
