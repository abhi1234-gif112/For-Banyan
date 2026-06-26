<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user     = requireAuth();
$db       = getDB();
$clientId = sanitise($_GET['client_id'] ?? '');

if (!$clientId || !canAccessClient($user, $clientId)) {
    respond(false, null, 'Access denied', 403);
}

$stmt = $db->prepare(
    "SELECT id, report_type, date_from, date_to, file_name, status, created_at
     FROM reports WHERE client_id = ?
     ORDER BY created_at DESC LIMIT 50"
);
$stmt->execute([$clientId]);
$reports = $stmt->fetchAll();

foreach ($reports as &$r) {
    $r['download_url'] = $r['file_name']
        ? APP_URL . '/reports/files/' . $r['file_name']
        : null;
}

respond(true, ['reports' => $reports]);
