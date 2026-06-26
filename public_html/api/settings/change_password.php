<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$u    = requireAuth();
$db   = getDB();
$body = getInput();

$current = $body['current_password'] ?? '';
$new     = $body['new_password']     ?? '';

if (strlen($new) < 8) respond(400, 'New password must be at least 8 characters');

$row = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
$row->execute([$u->sub]);
$row = $row->fetch();

if (!$row || !password_verify($current, $row['password_hash'])) {
    respond(401, 'Current password is incorrect');
}

$hash = password_hash($new, PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$hash, $u->sub]);

respond(200, 'Password changed successfully');
