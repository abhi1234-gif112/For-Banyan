<?php
require_once __DIR__ . '/config.php';
require_once VENDOR_PATH . 'autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function requireAuth(): array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorised']);
        exit();
    }

    $token = substr($authHeader, 7);
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
        exit();
    }
}

function generateToken(array $payload): string {
    $payload['iat'] = time();
    $payload['exp'] = time() + (7 * 24 * 60 * 60);
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function isRole(array $user, string $role): bool {
    $hierarchy = ['viewer' => 0, 'analyst' => 1, 'super_admin' => 2];
    return ($hierarchy[$user['role']] ?? 0) >= ($hierarchy[$role] ?? 0);
}

function canAccessClient(array $user, string $clientId): bool {
    if ($user['role'] === 'super_admin') return true;
    $assigned = json_decode($user['assigned_client_ids'] ?? '[]', true);
    return in_array($clientId, $assigned);
}
