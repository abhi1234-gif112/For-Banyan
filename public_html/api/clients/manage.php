<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user = requireAuth();

if (!isRole($user, 'super_admin')) {
    respond(false, null, 'Super admin only', 403);
}

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();

if ($method === 'POST') {
    $name         = sanitise($input['name'] ?? '');
    $handle       = sanitise($input['handle'] ?? '');
    $role         = sanitise($input['role'] ?? '');
    $party        = sanitise($input['party'] ?? '');
    $constituency = sanitise($input['constituency'] ?? '');
    $state        = sanitise($input['state'] ?? '');

    if (!$name) {
        respond(false, null, 'Client name required', 400);
    }

    $id = generateUUID();
    $db->prepare(
        "INSERT INTO clients (id, name, handle, role, party, constituency, state,
         language_scope, platforms, keywords, aliases)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $id, $name, $handle, $role, $party, $constituency, $state,
        json_encode($input['language_scope'] ?? []),
        json_encode($input['platforms'] ?? []),
        json_encode($input['keywords'] ?? []),
        json_encode($input['aliases'] ?? []),
    ]);

    respond(true, ['id' => $id], '', 201);
}

if ($method === 'PUT') {
    $id = sanitise($input['id'] ?? '');
    if (!$id) {
        respond(false, null, 'Client ID required', 400);
    }

    $fields = ['name', 'handle', 'role', 'party', 'constituency', 'state', 'is_active'];
    $sets   = [];
    $params = [];

    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $sets[]   = "$field = ?";
            $params[] = $field === 'is_active' ? (int) $input[$field] : sanitise((string) $input[$field]);
        }
    }
    foreach (['language_scope', 'platforms', 'keywords', 'aliases'] as $jsonField) {
        if (isset($input[$jsonField])) {
            $sets[]   = "$jsonField = ?";
            $params[] = json_encode($input[$jsonField]);
        }
    }

    if (empty($sets)) {
        respond(false, null, 'Nothing to update', 400);
    }

    $params[] = $id;
    $db->prepare("UPDATE clients SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
    respond(true, ['updated' => true]);
}

respond(false, null, 'Method not allowed', 405);
