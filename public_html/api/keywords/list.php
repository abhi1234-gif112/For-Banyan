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
    "SELECT id, keyword, type, language, is_active, created_at
     FROM keywords WHERE client_id = ?
     ORDER BY type, keyword"
);
$stmt->execute([$clientId]);
respond(true, ['keywords' => $stmt->fetchAll()]);
