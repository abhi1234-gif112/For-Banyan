<?php
ini_set('max_execution_time', 120);
ini_set('memory_limit', '256M');

require_once dirname(__DIR__) . '/api/config/config.php';

$isCLI    = (php_sapi_name() === 'cli');
$hasSecret = isset($_GET['cron_secret']) && $_GET['cron_secret'] === CRON_SECRET;

if (!$isCLI && !$hasSecret) {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__) . '/api/config/db.php';
require_once dirname(__DIR__) . '/api/config/helpers.php';
require_once VENDOR_PATH . 'autoload.php';

function curlGet(string $url, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'NAZAR-Bot/1.0 (SaptangaLabs)',
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $body    = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error   = curl_error($ch);
    curl_close($ch);
    return ['body' => $body, 'code' => $code, 'error' => $error];
}

function insertMention(array $data): bool {
    $db   = getDB();
    $hash = hash('sha256', ($data['source_url'] ?? '') . ($data['content'] ?? ''));

    $stmt = $db->prepare("SELECT id FROM mentions WHERE content_hash = ?");
    $stmt->execute([$hash]);
    if ($stmt->fetch()) return false;

    $db->prepare(
        "INSERT INTO mentions (id, client_id, platform, source_url, author_handle,
         content, content_language, reach_estimate, engagement, content_hash, published_at)
         VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $data['client_id'],
        $data['platform'],
        $data['source_url'] ?? null,
        $data['author_handle'] ?? null,
        mb_substr($data['content'] ?? '', 0, 5000),
        $data['language'] ?? 'en',
        $data['reach_estimate'] ?? 0,
        json_encode($data['engagement'] ?? []),
        $hash,
        $data['published_at'] ?? date('Y-m-d H:i:s'),
    ]);
    return true;
}

function getAllActiveClients(): array {
    return getDB()->query("SELECT * FROM clients WHERE is_active = 1")->fetchAll();
}

function getClientKeywords(string $clientId): array {
    $stmt = getDB()->prepare("SELECT keyword FROM keywords WHERE client_id = ? AND is_active = 1");
    $stmt->execute([$clientId]);
    return array_column($stmt->fetchAll(), 'keyword');
}
