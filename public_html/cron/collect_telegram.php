<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_telegram', 'started');
$count = 0;
$db    = getDB();

$offsetKey = 'telegram_update_offset';
$stmt = $db->prepare("SELECT counter FROM rate_limits WHERE key_name = ?");
$stmt->execute([$offsetKey]);
$row    = $stmt->fetch();
$offset = $row ? (int) $row['counter'] : 0;

$url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN
    . '/getUpdates?offset=' . $offset . '&limit=100&timeout=0';

$response = curlGet($url);
if ($response['code'] !== 200) {
    logCron('collect_telegram', 'failed', 0, "HTTP {$response['code']}");
    exit();
}

$data = json_decode($response['body'], true);
if (empty($data['result'])) {
    logCron('collect_telegram', 'completed', 0);
    exit("Telegram: no new updates\n");
}

$clients      = getAllActiveClients();
$lastUpdateId = $offset;

foreach ($data['result'] as $update) {
    $lastUpdateId = max($lastUpdateId, $update['update_id'] + 1);
    $message      = $update['message'] ?? $update['channel_post'] ?? null;
    if (!$message || empty($message['text'])) continue;

    $text      = $message['text'];
    $chatTitle = $message['chat']['title'] ?? $message['chat']['username'] ?? 'unknown';
    $members   = $message['chat']['members_count'] ?? 0;

    foreach ($clients as $client) {
        $keywords = getClientKeywords($client['id']);
        $matched  = false;
        foreach ($keywords as $kw) {
            if (mb_stripos($text, $kw) !== false) { $matched = true; break; }
        }
        if (!$matched) continue;

        $inserted = insertMention([
            'client_id'     => $client['id'],
            'platform'      => 'Other',
            'source_url'    => null,
            'author_handle' => $chatTitle,
            'content'       => mb_substr($text, 0, 2000),
            'language'      => preg_match('/[\x{0900}-\x{097F}]/u', $text) ? 'hi' : 'en',
            'reach_estimate' => $members,
            'published_at'  => date('Y-m-d H:i:s', $message['date']),
        ]);
        if ($inserted) $count++;
    }
}

// Save offset
$db->prepare(
    "INSERT INTO rate_limits (key_name, counter, expires_at)
     VALUES (?, ?, '2099-12-31 23:59:59')
     ON DUPLICATE KEY UPDATE counter = ?"
)->execute([$offsetKey, $lastUpdateId, $lastUpdateId]);

logCron('collect_telegram', 'completed', $count);
echo "Telegram: collected $count new mentions\n";
