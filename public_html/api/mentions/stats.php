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

$dateFrom = sanitise($_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')));
$dateTo   = sanitise($_GET['date_to'] ?? date('Y-m-d'));

// Sentiment totals
$stmt = $db->prepare(
    "SELECT sentiment, COUNT(*) as count FROM mentions
     WHERE client_id = ? AND collected_at BETWEEN ? AND ? AND sentiment IS NOT NULL
     GROUP BY sentiment"
);
$stmt->execute([$clientId, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$sentimentTotals = [];
foreach ($stmt->fetchAll() as $row) {
    $sentimentTotals[$row['sentiment']] = (int) $row['count'];
}

// Mentions by day
$stmt = $db->prepare(
    "SELECT DATE(collected_at) as date, sentiment, COUNT(*) as count
     FROM mentions
     WHERE client_id = ? AND collected_at BETWEEN ? AND ? AND sentiment IS NOT NULL
     GROUP BY DATE(collected_at), sentiment
     ORDER BY date ASC"
);
$stmt->execute([$clientId, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

$byDay = [];
foreach ($stmt->fetchAll() as $row) {
    $d = $row['date'];
    if (!isset($byDay[$d])) {
        $byDay[$d] = ['date' => $d, 'positive' => 0, 'negative' => 0, 'neutral' => 0, 'total' => 0];
    }
    $byDay[$d][$row['sentiment']] = (int) $row['count'];
    $byDay[$d]['total'] += (int) $row['count'];
}

// By platform
$stmt = $db->prepare(
    "SELECT platform, COUNT(*) as count FROM mentions
     WHERE client_id = ? AND collected_at BETWEEN ? AND ?
     GROUP BY platform ORDER BY count DESC"
);
$stmt->execute([$clientId, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$byPlatform = $stmt->fetchAll();

// Total reach and mentions
$stmt = $db->prepare(
    "SELECT COALESCE(SUM(reach_estimate), 0) as total_reach, COUNT(*) as total_mentions
     FROM mentions WHERE client_id = ? AND collected_at BETWEEN ? AND ?"
);
$stmt->execute([$clientId, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$totals = $stmt->fetch();

// Active alerts
$stmt = $db->prepare("SELECT COUNT(*) as count FROM alerts WHERE client_id = ? AND is_read = 0");
$stmt->execute([$clientId]);
$alertCount = (int) $stmt->fetch()['count'];

// Top individuals by mention count
$stmt = $db->prepare(
    "SELECT m.author_handle, COUNT(*) as mention_count, i.influence_score, i.stance
     FROM mentions m
     LEFT JOIN individuals i ON m.author_handle = i.handle
     WHERE m.client_id = ? AND m.collected_at BETWEEN ? AND ?
     GROUP BY m.author_handle
     ORDER BY mention_count DESC
     LIMIT 5"
);
$stmt->execute([$clientId, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
$topAuthors = $stmt->fetchAll();

respond(true, [
    'sentiment_totals' => $sentimentTotals,
    'by_day'           => array_values($byDay),
    'by_platform'      => $byPlatform,
    'total_reach'      => (int) $totals['total_reach'],
    'total_mentions'   => (int) $totals['total_mentions'],
    'active_alerts'    => $alertCount,
    'top_authors'      => $topAuthors,
]);
