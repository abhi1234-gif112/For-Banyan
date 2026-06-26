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

if (!empty($_GET['severity'])) {
    $where[]  = 'severity = ?';
    $params[] = sanitise($_GET['severity']);
}
if (isset($_GET['is_read'])) {
    $where[]  = 'is_read = ?';
    $params[] = (int) $_GET['is_read'];
}
if (!empty($_GET['alert_type'])) {
    $where[]  = 'alert_type = ?';
    $params[] = sanitise($_GET['alert_type']);
}

$whereSQL = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM alerts WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];

$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare(
    "SELECT * FROM alerts WHERE $whereSQL
     ORDER BY
       FIELD(severity,'critical','high','medium','low'),
       triggered_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute($params);
$alerts = $stmt->fetchAll();

foreach ($alerts as &$alert) {
    $alert['trigger_data'] = json_decode($alert['trigger_data'] ?? '{}', true);
}

// Severity counts
$sevStmt = $db->prepare(
    "SELECT severity, COUNT(*) as count FROM alerts WHERE client_id = ? AND is_read = 0 GROUP BY severity"
);
$sevStmt->execute([$clientId]);
$counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
foreach ($sevStmt->fetchAll() as $row) {
    $counts[$row['severity']] = (int) $row['count'];
}

respond(true, [
    'alerts'          => $alerts,
    'severity_counts' => $counts,
    'pagination'      => [
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => (int) ceil($total / $perPage),
    ],
]);
