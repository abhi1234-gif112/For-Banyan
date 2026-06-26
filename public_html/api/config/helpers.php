<?php
function respond(bool $success, $data = null, string $error = '', int $code = 200): void {
    http_response_code($code);
    $response = ['success' => $success];
    if ($data !== null) $response['data'] = $data;
    if ($error) $response['error'] = $error;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function getInput(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if ($json !== null) return $json;
    return $_POST ?: [];
}

function sanitise(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function callClaude(string $systemPrompt, string $userPrompt, float $temperature = 0.3, int $maxTokens = 3000): string {
    require_once __DIR__ . '/config.php';

    $payload = json_encode([
        'model'       => 'claude-sonnet-4-6',
        'max_tokens'  => $maxTokens,
        'temperature' => $temperature,
        'system'      => $systemPrompt,
        'messages'    => [['role' => 'user', 'content' => $userPrompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 55,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
    ]);

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) throw new Exception('Claude API error: ' . $curlError);

    $data = json_decode($response, true);
    if (!isset($data['content'][0]['text'])) {
        throw new Exception('Claude API failed: ' . ($data['error']['message'] ?? $response));
    }

    return $data['content'][0]['text'];
}

function getRateLimit(string $key, int $windowSeconds = 900): int {
    $db  = getDB();
    $now = date('Y-m-d H:i:s');
    $db->prepare("DELETE FROM rate_limits WHERE expires_at < ?")->execute([$now]);
    $stmt = $db->prepare("SELECT counter FROM rate_limits WHERE key_name = ? AND expires_at > ?");
    $stmt->execute([$key, $now]);
    $row = $stmt->fetch();
    return $row ? (int) $row['counter'] : 0;
}

function incrementRateLimit(string $key, int $windowSeconds = 900): int {
    $db      = getDB();
    $expires = date('Y-m-d H:i:s', time() + $windowSeconds);
    $db->prepare(
        "INSERT INTO rate_limits (key_name, counter, expires_at) VALUES (?, 1, ?)
         ON DUPLICATE KEY UPDATE counter = counter + 1, expires_at = IF(expires_at < NOW(), ?, expires_at)"
    )->execute([$key, $expires, $expires]);
    return getRateLimit($key, $windowSeconds);
}

function logCron(string $script, string $status, int $records = 0, string $error = ''): void {
    try {
        $db = getDB();
        $db->prepare(
            "INSERT INTO cron_logs (script_name, status, records_processed, error_message) VALUES (?, ?, ?, ?)"
        )->execute([$script, $status, $records, $error ?: null]);
    } catch (Exception $e) {
        // silent — cron logs must never crash a cron script
    }
}
