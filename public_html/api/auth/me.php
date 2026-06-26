<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user = requireAuth();
$db   = getDB();

$stmt = $db->prepare("SELECT id, email, role, assigned_client_ids, last_login, created_at FROM users WHERE id = ?");
$stmt->execute([$user['user_id']]);
$row = $stmt->fetch();

if (!$row) {
    respond(false, null, 'User not found', 404);
}

$row['assigned_client_ids'] = json_decode($row['assigned_client_ids'] ?? '[]', true);
respond(true, $row);
