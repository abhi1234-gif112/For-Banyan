<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user = requireAuth();
$db   = getDB();
$id   = sanitise($_GET['id'] ?? '');

if (!$id) {
    respond(false, null, 'Individual ID required', 400);
}

$stmt = $db->prepare("SELECT * FROM individuals WHERE id = ?");
$stmt->execute([$id]);
$individual = $stmt->fetch();

if (!$individual) {
    respond(false, null, 'Not found', 404);
}

// JSON fields
foreach (['platforms', 'languages', 'topics', 'suggestions'] as $field) {
    $individual[$field] = json_decode($individual[$field] ?? '[]', true);
}

// Recent mentions
$stmt = $db->prepare(
    "SELECT id, client_id, platform, source_url, content, sentiment, tone, reach_estimate,
     engagement, collected_at, summary_en
     FROM mentions WHERE author_handle = ?
     ORDER BY collected_at DESC LIMIT 20"
);
$stmt->execute([$individual['handle']]);
$recentMentions = $stmt->fetchAll();
foreach ($recentMentions as &$m) {
    $m['engagement'] = json_decode($m['engagement'] ?? '{}', true);
}

// 30-day sparkline
$stmt = $db->prepare(
    "SELECT DATE(collected_at) as date,
     SUM(CASE WHEN sentiment='positive' THEN 1 ELSE 0 END) as positive,
     SUM(CASE WHEN sentiment='negative' THEN 1 ELSE 0 END) as negative,
     COUNT(*) as total
     FROM mentions WHERE author_handle = ? AND collected_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(collected_at) ORDER BY date ASC"
);
$stmt->execute([$individual['handle']]);
$sparkline = $stmt->fetchAll();

respond(true, [
    'individual'     => $individual,
    'recent_mentions' => $recentMentions,
    'sparkline'      => $sparkline,
]);
