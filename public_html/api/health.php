<?php
ini_set('max_execution_time', 10);
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$dbOk = false;
try { $pdo = getDB(); $pdo->query('SELECT 1'); $dbOk = true; } catch (Exception $e) {}

$reportsWritable = is_writable(REPORTS_PATH);

http_response_code($dbOk ? 200 : 503);
echo json_encode([
    'success' => $dbOk,
    'data'    => [
        'db'               => $dbOk,
        'php'              => PHP_VERSION,
        'server'           => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache',
        'reports_writable' => $reportsWritable,
    ],
    'time' => date('c'),
]);
