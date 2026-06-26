<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('detect_alerts', 'started');
$db            = getDB();
$alertsCreated = 0;

function createAlert(PDO $db, string $clientId, string $type, string $severity, string $title, string $description, array $triggerData = []): void {
    global $alertsCreated;
    $fourHoursAgo = date('Y-m-d H:i:s', strtotime('-4 hours'));
    $stmt = $db->prepare(
        "SELECT id FROM alerts WHERE client_id = ? AND alert_type = ? AND triggered_at > ?"
    );
    $stmt->execute([$clientId, $type, $fourHoursAgo]);
    if ($stmt->fetch()) return;

    $db->prepare(
        "INSERT INTO alerts (id, client_id, alert_type, severity, title, description, trigger_data)
         VALUES (UUID(), ?, ?, ?, ?, ?, ?)"
    )->execute([$clientId, $type, $severity, mb_substr($title, 0, 500), $description, json_encode($triggerData)]);
    $alertsCreated++;
}

$clients = getAllActiveClients();

foreach ($clients as $client) {
    $cid  = $client['id'];
    $name = $client['name'];

    // Negative spike
    $stmt = $db->prepare(
        "SELECT COUNT(*) as cnt FROM mentions WHERE client_id = ?
         AND sentiment = 'negative' AND collected_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)"
    );
    $stmt->execute([$cid]);
    $twoHourNeg = (int) $stmt->fetch()['cnt'];

    $stmt = $db->prepare(
        "SELECT COUNT(*) / 14.0 as daily_avg FROM mentions WHERE client_id = ?
         AND sentiment = 'negative' AND collected_at > DATE_SUB(NOW(), INTERVAL 14 DAY)"
    );
    $stmt->execute([$cid]);
    $dailyAvg   = (float) $stmt->fetch()['daily_avg'];
    $hourlyAvg  = $dailyAvg / 24;

    if ($twoHourNeg > 0 && $hourlyAvg > 0 && $twoHourNeg > ($hourlyAvg * 2 * 2)) {
        createAlert($db, $cid, 'spike', 'critical',
            "Negative mention spike for $name",
            "$twoHourNeg negative mentions in last 2 hours vs average of " . round($hourlyAvg * 2, 1) . ". Immediate attention required.",
            ['two_hour_count' => $twoHourNeg, 'hourly_avg' => round($hourlyAvg, 2)]
        );
    }

    // Viral negative
    $stmt = $db->prepare(
        "SELECT * FROM mentions WHERE client_id = ? AND sentiment = 'negative'
         AND collected_at > DATE_SUB(NOW(), INTERVAL 3 HOUR)
         AND (CAST(JSON_EXTRACT(engagement, '$.likes') AS UNSIGNED) +
              CAST(JSON_EXTRACT(engagement, '$.shares') AS UNSIGNED)) > 500
         LIMIT 1"
    );
    $stmt->execute([$cid]);
    $viral = $stmt->fetch();
    if ($viral) {
        createAlert($db, $cid, 'viral_negative', 'critical',
            "Viral negative content about $name",
            "A negative post by {$viral['author_handle']} on {$viral['platform']} is gaining significant traction. Content: " . mb_substr($viral['content'], 0, 200) . '...',
            ['mention_id' => $viral['id'], 'author' => $viral['author_handle']]
        );
    }

    // Coordinated attack
    $stmt = $db->prepare(
        "SELECT COUNT(DISTINCT author_handle) as authors FROM mentions
         WHERE client_id = ? AND sentiment = 'negative' AND is_opposition_linked = 1
         AND collected_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $stmt->execute([$cid]);
    $coordCount = (int) $stmt->fetch()['authors'];
    if ($coordCount >= 5) {
        createAlert($db, $cid, 'coordinated_campaign', 'critical',
            "Coordinated opposition attack on $name detected",
            "$coordCount opposition-linked accounts posted negative content in the last hour. This indicates a coordinated campaign.",
            ['account_count' => $coordCount]
        );
    }

    // Keyword surge
    $keywords = getClientKeywords($cid);
    foreach (array_slice($keywords, 0, 5) as $keyword) {
        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM mentions WHERE client_id = ?
             AND JSON_OVERLAPS(keywords_matched, JSON_ARRAY(?))
             AND collected_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        $stmt->execute([$cid, $keyword]);
        $hourCount = (int) $stmt->fetch()['cnt'];

        $stmt = $db->prepare(
            "SELECT COUNT(*) / (7 * 24) as hourly_avg FROM mentions WHERE client_id = ?
             AND JSON_OVERLAPS(keywords_matched, JSON_ARRAY(?))
             AND collected_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $stmt->execute([$cid, $keyword]);
        $kwAvg = (float) $stmt->fetch()['hourly_avg'];

        if ($hourCount > 0 && $kwAvg > 0 && $hourCount > $kwAvg * 3) {
            createAlert($db, $cid, 'keyword_surge', 'medium',
                "Keyword surge: \"$keyword\" trending for $name",
                "\"$keyword\" mentioned $hourCount times in last hour vs average of " . round($kwAvg, 1) . " per hour.",
                ['keyword' => $keyword, 'count' => $hourCount, 'avg' => round($kwAvg, 2)]
            );
            break;
        }
    }

    // New high-risk individual
    $stmt = $db->prepare(
        "SELECT * FROM individuals WHERE risk_score > 70
         AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll() as $ind) {
        createAlert($db, $cid, 'new_threat_individual', 'medium',
            "New high-risk individual identified: {$ind['name']}",
            "{$ind['handle']} profiled with risk score {$ind['risk_score']}/100. Category: {$ind['category']}.",
            ['individual_id' => $ind['id']]
        );
    }

    // Positive milestone
    $stmt = $db->prepare(
        "SELECT COUNT(*) as cnt FROM mentions WHERE client_id = ?
         AND sentiment = 'positive' AND collected_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $stmt->execute([$cid]);
    $posCount = (int) $stmt->fetch()['cnt'];
    if ($posCount >= 50) {
        createAlert($db, $cid, 'positive_milestone', 'low',
            "$posCount positive mentions for $name in 24 hours",
            "Strong positive sentiment day. Consider amplifying top-performing content.",
            ['count' => $posCount]
        );
    }
}

logCron('detect_alerts', 'completed', $alertsCreated);
echo "Alert detection: $alertsCreated new alerts created\n";
