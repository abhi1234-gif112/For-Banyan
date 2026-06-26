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

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
$offset  = ($page - 1) * $perPage;

$where  = ['client_id = ?'];
$params = [$clientId];

if (!empty($_GET['action_type'])) {
    $where[]  = 'action_type = ?';
    $params[] = sanitise($_GET['action_type']);
}

$whereSQL = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM actions WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];

$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare(
    "SELECT id, client_id, action_type, status, output_language,
     triggered_by_alert_id, triggered_by_individual_id, created_at,
     LEFT(output_content, 300) as preview,
     CHAR_LENGTH(output_content) as full_length
     FROM actions WHERE $whereSQL
     ORDER BY created_at DESC LIMIT ? OFFSET ?"
);
$stmt->execute($params);
$actions = $stmt->fetchAll();

respond(true, [
    'actions'    => $actions,
    'pagination' => [
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => (int) ceil($total / $perPage),
    ],
]);
