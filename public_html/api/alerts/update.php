<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user  = requireAuth();
$db    = getDB();
$input = getInput();
$id    = sanitise($input['id'] ?? '');

if (!$id) {
    respond(false, null, 'Alert ID required', 400);
}

// Verify ownership via client
$stmt = $db->prepare("SELECT client_id FROM alerts WHERE id = ?");
$stmt->execute([$id]);
$alert = $stmt->fetch();
if (!$alert || !canAccessClient($user, $alert['client_id'])) {
    respond(false, null, 'Access denied', 403);
}

$sets   = [];
$params = [];

if (isset($input['is_read'])) {
    $sets[]   = 'is_read = ?';
    $params[] = (int) $input['is_read'];
}
if (isset($input['is_actioned'])) {
    $sets[]   = 'is_actioned = ?';
    $params[] = (int) $input['is_actioned'];
    if ((int) $input['is_actioned'] === 1) {
        $sets[]   = 'resolved_at = NOW()';
    }
}
if (!empty($input['action_taken'])) {
    $sets[]   = 'action_taken = ?';
    $params[] = mb_substr(sanitise($input['action_taken']), 0, 1000);
}

if (empty($sets)) {
    respond(false, null, 'Nothing to update', 400);
}

$params[] = $id;
$db->prepare("UPDATE alerts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);

respond(true, ['updated' => true]);
