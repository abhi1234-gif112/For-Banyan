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

$where  = ['m.client_id = ?'];
$params = [$clientId];

if (!empty($_GET['platform'])) {
    $where[]  = 'm.platform = ?';
    $params[] = sanitise($_GET['platform']);
}
if (!empty($_GET['sentiment'])) {
    $where[]  = 'm.sentiment = ?';
    $params[] = sanitise($_GET['sentiment']);
}
if (!empty($_GET['tone'])) {
    $where[]  = 'm.tone = ?';
    $params[] = sanitise($_GET['tone']);
}
if (!empty($_GET['date_from'])) {
    $where[]  = 'm.collected_at >= ?';
    $params[] = sanitise($_GET['date_from']) . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where[]  = 'm.collected_at <= ?';
    $params[] = sanitise($_GET['date_to']) . ' 23:59:59';
}
if (!empty($_GET['search'])) {
    $where[]  = '(m.content LIKE ? OR m.author_handle LIKE ?)';
    $search   = '%' . $_GET['search'] . '%';
    $params[] = $search;
    $params[] = $search;
}

$whereSQL = implode(' AND ', $where);
$sort     = in_array($_GET['sort'] ?? '', ['collected_at', 'reach_estimate']) ? sanitise($_GET['sort']) : 'collected_at';
$order    = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM mentions m WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];

$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare(
    "SELECT m.*, i.influence_score, i.stance
     FROM mentions m
     LEFT JOIN individuals i ON m.author_handle = i.handle
     WHERE $whereSQL
     ORDER BY m.$sort $order
     LIMIT ? OFFSET ?"
);
$stmt->execute($params);
$mentions = $stmt->fetchAll();

foreach ($mentions as &$mention) {
    $mention['engagement']        = json_decode($mention['engagement'] ?? '{}', true);
    $mention['topics']            = json_decode($mention['topics'] ?? '[]', true);
    $mention['keywords_matched']  = json_decode($mention['keywords_matched'] ?? '[]', true);
}

respond(true, [
    'mentions'   => $mentions,
    'pagination' => [
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => (int) ceil($total / $perPage),
    ],
]);
