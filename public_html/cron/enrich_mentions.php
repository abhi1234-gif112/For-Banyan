<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('enrich_mentions', 'started');
$db        = getDB();
$processed = 0;

$stmt = $db->query(
    "SELECT m.*, c.name as client_name, c.role as client_role,
     c.party as client_party, c.state as client_state
     FROM mentions m JOIN clients c ON m.client_id = c.id
     WHERE m.enriched_at IS NULL
     ORDER BY m.collected_at ASC LIMIT 30"
);
$mentions = $stmt->fetchAll();

if (empty($mentions)) {
    logCron('enrich_mentions', 'completed', 0);
    exit("No mentions to enrich\n");
}

$systemPrompt = "You are a political intelligence analyst specialising in Indian politics and media. You understand Hindi, Hinglish, English, Marathi, Chhattisgarhi, and Indian regional languages. Analyse content for political figures. Respond ONLY with a valid JSON object — no markdown, no explanation, no code blocks.";

foreach ($mentions as $mention) {
    $rateLimitKey = 'claude_per_minute';
    if (getRateLimit($rateLimitKey, 60) >= 45) {
        echo "Claude rate limit approached, sleeping 60s\n";
        sleep(60);
    }
    incrementRateLimit($rateLimitKey, 60);

    $userPrompt = "Analyse this content for political client: {$mention['client_name']} ({$mention['client_role']}, {$mention['client_party']}, {$mention['client_state']}).\n\nContent: [[[ "
        . mb_substr($mention['content'], 0, 1500)
        . " ]]]\n\nAuthor: {$mention['author_handle']}\nPlatform: {$mention['platform']}\n\n"
        . "Return this exact JSON structure:\n"
        . '{"sentiment":"positive|negative|neutral","sentiment_score":0.0,"tone":"informational|critical|promotional|satirical|threatening|emotional","topics":["use only from: governance,budget-finance,tribal-welfare,youth-employment,law-order,opposition-attack,media-coverage,social-praise,corruption-allegation,personal-attack,religious-communal,women-welfare,environment,election-campaign,party-politics,viral-satire,international,other"],"keywords_matched":[],"is_opposition_content":false,"risk_flag":false,"risk_reason":null,"summary_en":"one sentence English summary","summary_hi":"ek vakya Hindi mein","recommended_action":"none|monitor|respond|escalate"}';

    try {
        $rawResponse = callClaude($systemPrompt, $userPrompt, 0.1, 800);
        $clean       = preg_replace('/```json|```/', '', $rawResponse);
        $result      = json_decode(trim($clean), true);

        if (!$result || !isset($result['sentiment'])) {
            throw new Exception('Invalid JSON response from Claude');
        }

        $db->prepare(
            "UPDATE mentions SET
             sentiment = ?, sentiment_score = ?, tone = ?, topics = ?,
             keywords_matched = ?, is_opposition_linked = ?,
             summary_en = ?, summary_hi = ?, recommended_action = ?, enriched_at = NOW()
             WHERE id = ?"
        )->execute([
            $result['sentiment'],
            $result['sentiment_score'] ?? 0.5,
            $result['tone'] ?? 'informational',
            json_encode($result['topics'] ?? []),
            json_encode($result['keywords_matched'] ?? []),
            $result['is_opposition_content'] ? 1 : 0,
            mb_substr($result['summary_en'] ?? '', 0, 500),
            mb_substr($result['summary_hi'] ?? '', 0, 500),
            $result['recommended_action'] ?? 'none',
            $mention['id'],
        ]);
        $processed++;

        // Auto-create individual profile if unknown
        if ($mention['author_handle'] && $mention['author_handle'] !== 'unknown') {
            $s2 = $db->prepare("SELECT id FROM individuals WHERE handle = ?");
            $s2->execute([$mention['author_handle']]);
            if (!$s2->fetch()) {
                $db->prepare(
                    "INSERT IGNORE INTO individuals (id, handle, name, platforms, category, last_active)
                     VALUES (UUID(), ?, ?, JSON_ARRAY(?), 'Influencer', NOW())"
                )->execute([$mention['author_handle'], $mention['author_handle'], $mention['platform']]);
            } else {
                $db->prepare(
                    "UPDATE individuals SET last_active = NOW(),
                     mention_count_7d = mention_count_7d + 1,
                     mention_count_30d = mention_count_30d + 1
                     WHERE handle = ?"
                )->execute([$mention['author_handle']]);
            }
        }

    } catch (Exception $e) {
        $db->prepare(
            "UPDATE mentions SET enriched_at = NOW(), summary_en = ? WHERE id = ?"
        )->execute(['Enrichment failed: ' . $e->getMessage(), $mention['id']]);
    }
}

logCron('enrich_mentions', 'completed', $processed);
echo "Enriched: $processed mentions\n";
