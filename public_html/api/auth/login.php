<?php
ini_set('max_execution_time', 30);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once VENDOR_PATH . 'autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, null, 'Method not allowed', 405);
}

$input    = getInput();
$email    = sanitise($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    respond(false, null, 'Email and password required', 400);
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    respond(false, null, 'Invalid email or password', 401);
}

$db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

$token = generateToken([
    'user_id'             => $user['id'],
    'email'               => $user['email'],
    'role'                => $user['role'],
    'assigned_client_ids' => $user['assigned_client_ids'],
]);

respond(true, [
    'token' => $token,
    'user'  => [
        'id'                  => $user['id'],
        'email'               => $user['email'],
        'role'                => $user['role'],
        'assigned_client_ids' => json_decode($user['assigned_client_ids'] ?? '[]', true),
    ],
]);
