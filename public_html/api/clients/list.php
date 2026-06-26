<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user = requireAuth();
$db   = getDB();

if ($user['role'] === 'super_admin') {
    $stmt = $db->query(
        "SELECT id, name, handle, role, party, constituency, state,
         language_scope, platforms, is_active, created_at
         FROM clients ORDER BY name"
    );
    $clients = $stmt->fetchAll();
} else {
    $ids = json_decode($user['assigned_client_ids'] ?? '[]', true);
    if (empty($ids)) {
        respond(true, ['clients' => []]);
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare(
        "SELECT id, name, handle, role, party, constituency, state,
         language_scope, platforms, is_active, created_at
         FROM clients WHERE id IN ($placeholders) ORDER BY name"
    );
    $stmt->execute($ids);
    $clients = $stmt->fetchAll();
}

foreach ($clients as &$c) {
    $c['language_scope'] = json_decode($c['language_scope'] ?? '[]', true);
    $c['platforms']      = json_decode($c['platforms'] ?? '[]', true);
}

respond(true, ['clients' => $clients]);
