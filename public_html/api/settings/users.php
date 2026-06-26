<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$u = requireAuth();
if (!isRole($u, 'super_admin')) respond(403, 'Forbidden');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $db->query("
        SELECT id, name, email, role, assigned_client_ids, last_login, created_at
        FROM users ORDER BY created_at DESC
    ")->fetchAll();
    foreach ($rows as &$r) {
        $r['assigned_client_ids'] = $r['assigned_client_ids'] ? json_decode($r['assigned_client_ids']) : [];
    }
    respond(200, 'ok', ['users' => $rows]);
}

$body = getInput();

if ($method === 'POST') {
    $name     = sanitise($body['name'] ?? '');
    $email    = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $body['password'] ?? '';
    $role     = in_array($body['role'] ?? '', ['viewer','analyst','super_admin']) ? $body['role'] : 'analyst';
    $clients  = json_encode($body['assigned_client_ids'] ?? []);

    if (!$name || !$email || strlen($password) < 8) respond(400, 'Name, valid email, and password (8+ chars) required');

    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) respond(409, 'Email already exists');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $id   = generateUUID();
    $stmt = $db->prepare("INSERT INTO users (id,name,email,password_hash,role,assigned_client_ids) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$id, $name, $email, $hash, $role, $clients]);
    respond(201, 'User created', ['id' => $id]);
}

if ($method === 'PUT') {
    $id      = sanitise($body['id'] ?? '');
    $name    = sanitise($body['name'] ?? '');
    $email   = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $role    = in_array($body['role'] ?? '', ['viewer','analyst','super_admin']) ? $body['role'] : 'analyst';
    $clients = json_encode($body['assigned_client_ids'] ?? []);

    if (!$id || !$name || !$email) respond(400, 'Missing fields');

    if (!empty($body['password'])) {
        if (strlen($body['password']) < 8) respond(400, 'Password must be 8+ characters');
        $hash = password_hash($body['password'], PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET name=?,email=?,password_hash=?,role=?,assigned_client_ids=? WHERE id=?");
        $stmt->execute([$name, $email, $hash, $role, $clients, $id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET name=?,email=?,role=?,assigned_client_ids=? WHERE id=?");
        $stmt->execute([$name, $email, $role, $clients, $id]);
    }
    respond(200, 'Updated');
}

respond(405, 'Method not allowed');
