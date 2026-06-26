<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user = requireAuth();
$db   = getDB();

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if (!empty($_GET['stance'])) {
    $where[]  = 'stance = ?';
    $params[] = sanitise($_GET['stance']);
}
if (!empty($_GET['category'])) {
    $where[]  = 'category = ?';
    $params[] = sanitise($_GET['category']);
}
if (!empty($_GET['risk_min'])) {
    $where[]  = 'risk_score >= ?';
    $params[] = (int) $_GET['risk_min'];
}
if (!empty($_GET['risk_max'])) {
    $where[]  = 'risk_score <= ?';
    $params[] = (int) $_GET['risk_max'];
}
if (!empty($_GET['search'])) {
    $where[]  = '(name LIKE ? OR handle LIKE ?)';
    $s        = '%' . $_GET['search'] . '%';
    $params[] = $s;
    $params[] = $s;
}

$whereSQL = implode(' AND ', $where);
$sortMap  = [
    'influence' => 'influence_score',
    'risk'      => 'risk_score',
    'active'    => 'last_active',
    'mentions'  => 'mention_count_30d',
];
$sortCol = $sortMap[$_GET['sort'] ?? ''] ?? 'influence_score';
$order   = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$countStmt = $db->prepare("SELECT COUNT(*) as total FROM individuals WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];

$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare(
    "SELECT id, name, handle, platforms, category, sub_category, reach_estimate,
     influence_score, risk_score, stance, languages, topics,
     is_verified, is_political, party_affiliation, last_active,
     mention_count_7d, mention_count_30d, sentiment_avg_30d
     FROM individuals WHERE $whereSQL
     ORDER BY $sortCol $order LIMIT ? OFFSET ?"
);
$stmt->execute($params);
$individuals = $stmt->fetchAll();

foreach ($individuals as &$ind) {
    $ind['platforms'] = json_decode($ind['platforms'] ?? '[]', true);
    $ind['languages'] = json_decode($ind['languages'] ?? '[]', true);
    $ind['topics']    = json_decode($ind['topics'] ?? '[]', true);
}

respond(true, [
    'individuals' => $individuals,
    'pagination'  => [
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => (int) ceil($total / $perPage),
    ],
]);
