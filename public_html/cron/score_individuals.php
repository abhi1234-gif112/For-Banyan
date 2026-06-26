<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('score_individuals', 'started');
$db        = getDB();
$processed = 0;

$stmt = $db->query(
    "SELECT * FROM individuals
     WHERE updated_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) OR influence_score = 0
     LIMIT 50"
);
$individuals = $stmt->fetchAll();

if (empty($individuals)) {
    logCron('score_individuals', 'completed', 0);
    exit("No individuals to score\n");
}

// Max reach for normalisation
$maxReachRow = $db->query("SELECT MAX(reach_estimate) as m FROM individuals")->fetch();
$maxReach    = max(1, (int) ($maxReachRow['m'] ?? 1));

// Max 30d mentions for normalisation
$maxMentRow  = $db->query("SELECT MAX(mention_count_30d) as m FROM individuals")->fetch();
$maxMentions = max(1, (int) ($maxMentRow['m'] ?? 1));

foreach ($individuals as $ind) {
    $handle = $ind['handle'];

    // --- INFLUENCE SCORE ---
    $reach = max(1, (int) $ind['reach_estimate']);
    $reachScore = min(30, log10($reach) * 5);

    // Engagement rate from recent mentions
    $s2 = $db->prepare(
        "SELECT AVG(
             (CAST(JSON_EXTRACT(engagement,'$.likes') AS UNSIGNED) +
              CAST(JSON_EXTRACT(engagement,'$.shares') AS UNSIGNED)) /
             GREATEST(reach_estimate, 1)
         ) as avg_eng FROM mentions WHERE author_handle = ? AND collected_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $s2->execute([$handle]);
    $engRate      = (float) ($s2->fetch()['avg_eng'] ?? 0);
    $engScore     = min(25, $engRate * 10000);

    // Platform tier
    $platforms    = json_decode($ind['platforms'] ?? '[]', true);
    $tierMap      = ['Twitter/X' => 20, 'YouTube' => 20, 'News' => 20, 'Facebook' => 16, 'Instagram' => 14, 'LinkedIn' => 14, 'Blog' => 8, 'Other' => 6, 'Telegram' => 8, 'Reddit' => 6];
    $platformScore = 0;
    foreach ($platforms as $p) { $platformScore = max($platformScore, $tierMap[$p] ?? 4); }

    // Topic specialisation
    $s3 = $db->prepare(
        "SELECT COUNT(*) as cnt FROM mentions WHERE author_handle = ? AND collected_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $s3->execute([$handle]);
    $mentionCnt = (int) $s3->fetch()['cnt'];
    $topicScore = $mentionCnt > 0 ? min(15, ($mentionCnt / max(1, $maxMentions)) * 15) : 0;

    $verifiedBonus   = $ind['is_verified'] ? 10 : 0;
    $influenceScore  = (int) min(100, $reachScore + $engScore + $platformScore + $topicScore + $verifiedBonus);

    // --- RISK SCORE ---
    $sentimentAvg   = (float) ($ind['sentiment_avg_30d'] ?: 0.5);
    $sentimentFactor = (1.0 - $sentimentAvg) * 35;

    $volumeFactor = min(25, ($ind['mention_count_30d'] / $maxMentions) * 25);

    $oppositionBonus = $ind['is_political'] ? 20 : 0;

    $s4 = $db->prepare(
        "SELECT COUNT(*) as cnt FROM mentions WHERE author_handle = ?
         AND is_viral = 1 AND collected_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $s4->execute([$handle]);
    $viralFactor = min(15, (int) $s4->fetch()['cnt'] * 3);

    $riskScore = (int) min(100, $sentimentFactor + $volumeFactor + $oppositionBonus + $viralFactor);

    // --- STANCE ---
    if ($sentimentAvg > 0.65 && !$ind['is_political']) {
        $stance = 'Ally';
    } elseif ($sentimentAvg < 0.35 || $riskScore > 70) {
        $stance = 'Threat';
    } elseif ($sentimentAvg >= 0.35 && $sentimentAvg <= 0.65 || ($riskScore >= 40 && $riskScore <= 70)) {
        $stance = 'Watchlist';
    } else {
        $stance = 'Neutral';
    }

    // --- UPDATE ---
    $db->prepare(
        "UPDATE individuals SET
         influence_score = ?, risk_score = ?, stance = ?, updated_at = NOW()
         WHERE id = ?"
    )->execute([$influenceScore, $riskScore, $stance, $ind['id']]);

    // Generate AI suggestions if missing or stale
    $suggestionsAge = $ind['suggestions']
        ? (time() - strtotime($ind['updated_at'] ?? 'now')) / 86400
        : 999;

    if ($suggestionsAge > 7 || !$ind['suggestions']) {
        // Get first active client for context
        $cRow = $db->query("SELECT name, role, party, state FROM clients WHERE is_active = 1 LIMIT 1")->fetch();
        if ($cRow) {
            $rateLimitKey = 'claude_per_minute';
            if (getRateLimit($rateLimitKey, 60) < 40) {
                incrementRateLimit($rateLimitKey, 60);
                $sysPrompt  = "You are a senior political ORM strategist. Generate 2-4 specific action suggestions for this individual as a JSON array. Each object must have: {action_type: 'engage|monitor|counter|coordinate|prepare|outreach', priority: 'high|medium|low', title: 'max 10 words', detail: '1-2 sentences', next_step: 'what to ask Claude to do'}. Return ONLY valid JSON array, no markdown.";
                $userPrompt = "Individual: {$ind['name']} | Handle: {$ind['handle']} | Category: {$ind['category']} | Stance: $stance | Influence: $influenceScore | Risk: $riskScore | Sentiment avg: " . round($sentimentAvg, 2) . "\nClient: {$cRow['name']} | {$cRow['role']} | {$cRow['party']} | {$cRow['state']}";

                try {
                    $raw      = callClaude($sysPrompt, $userPrompt, 0.4, 600);
                    $clean    = preg_replace('/```json|```/', '', $raw);
                    $suggs    = json_decode(trim($clean), true);
                    if (is_array($suggs)) {
                        $db->prepare("UPDATE individuals SET suggestions = ? WHERE id = ?")
                            ->execute([json_encode($suggs), $ind['id']]);
                    }
                } catch (Exception $e) {
                    // Suggestions are optional; don't fail the script
                }
            }
        }
    }

    $processed++;
}

logCron('score_individuals', 'completed', $processed);
echo "Scored: $processed individuals\n";
