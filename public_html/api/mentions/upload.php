<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user = requireAuth();
$db   = getDB();

if (!isRole($user, 'analyst')) {
    respond(false, null, 'Insufficient permissions', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, null, 'Method not allowed', 405);
}

$input    = getInput();
$clientId = sanitise($input['client_id'] ?? '');
$platform = sanitise($input['platform'] ?? '');
$content  = trim($input['content'] ?? '');
$sourceUrl = sanitise($input['source_url'] ?? '');
$authorHandle = sanitise($input['author_handle'] ?? '');

if (!$clientId || !$platform || !$content) {
    respond(false, null, 'client_id, platform, and content are required', 400);
}

if (!canAccessClient($user, $clientId)) {
    respond(false, null, 'Access denied', 403);
}

$hash = hash('sha256', $sourceUrl . $content);
$stmt = $db->prepare("SELECT id FROM mentions WHERE content_hash = ?");
$stmt->execute([$hash]);
if ($stmt->fetch()) {
    respond(false, null, 'Duplicate mention', 409);
}

$id = generateUUID();
$db->prepare(
    "INSERT INTO mentions (id, client_id, platform, source_url, author_handle, content,
     content_language, reach_estimate, engagement, content_hash, published_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
)->execute([
    $id, $clientId, $platform, $sourceUrl ?: null, $authorHandle ?: null,
    mb_substr($content, 0, 5000),
    preg_match('/[\x{0900}-\x{097F}]/u', $content) ? 'hi' : 'en',
    (int) ($input['reach_estimate'] ?? 0),
    json_encode($input['engagement'] ?? []),
    $hash,
]);

respond(true, ['id' => $id], '', 201);
