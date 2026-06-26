<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user   = requireAuth();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if (!isRole($user, 'analyst')) {
    respond(false, null, 'Insufficient permissions', 403);
}

if ($method === 'POST') {
    $input    = getInput();
    $clientId = sanitise($input['client_id'] ?? '');
    $keyword  = sanitise($input['keyword'] ?? '');
    $type     = sanitise($input['type'] ?? 'primary');
    $language = sanitise($input['language'] ?? 'en');

    if (!$clientId || !$keyword) {
        respond(false, null, 'client_id and keyword required', 400);
    }
    if (!canAccessClient($user, $clientId)) {
        respond(false, null, 'Access denied', 403);
    }

    // Bulk import
    if (!empty($input['bulk'])) {
        $keywords = array_filter(array_map('trim', explode(',', $input['bulk'])));
        $inserted = 0;
        foreach ($keywords as $kw) {
            if (mb_strlen($kw) < 2) continue;
            try {
                $db->prepare(
                    "INSERT IGNORE INTO keywords (id, client_id, keyword, type, language) VALUES (UUID(), ?, ?, ?, ?)"
                )->execute([$clientId, mb_substr($kw, 0, 500), $type, $language]);
                $inserted++;
            } catch (Exception $e) {}
        }
        respond(true, ['inserted' => $inserted]);
    }

    $id = generateUUID();
    $db->prepare(
        "INSERT INTO keywords (id, client_id, keyword, type, language) VALUES (?, ?, ?, ?, ?)"
    )->execute([$id, $clientId, mb_substr($keyword, 0, 500), $type, $language]);

    respond(true, ['id' => $id], '', 201);
}

if ($method === 'PUT') {
    $input    = getInput();
    $id       = sanitise($input['id'] ?? '');
    $isActive = isset($input['is_active']) ? (int) $input['is_active'] : null;

    if (!$id) {
        respond(false, null, 'Keyword ID required', 400);
    }
    $stmt = $db->prepare("SELECT client_id FROM keywords WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || !canAccessClient($user, $row['client_id'])) {
        respond(false, null, 'Access denied', 403);
    }

    if ($isActive !== null) {
        $db->prepare("UPDATE keywords SET is_active = ? WHERE id = ?")->execute([$isActive, $id]);
    }
    respond(true, ['updated' => true]);
}

if ($method === 'DELETE') {
    $id = sanitise($_GET['id'] ?? getInput()['id'] ?? '');
    if (!$id) {
        respond(false, null, 'Keyword ID required', 400);
    }
    $stmt = $db->prepare("SELECT client_id FROM keywords WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || !canAccessClient($user, $row['client_id'])) {
        respond(false, null, 'Access denied', 403);
    }
    $db->prepare("DELETE FROM keywords WHERE id = ?")->execute([$id]);
    respond(true, ['deleted' => true]);
}

respond(false, null, 'Method not allowed', 405);
