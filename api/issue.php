<?php
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$secret = getenv('APP_SECRET');
$fanvueUrl = getenv('FANVUE_URL');

if (!$secret || !$fanvueUrl || !preg_match('#^https?://#i', $fanvueUrl)) {
    http_response_code(500);
    exit;
}

$site = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || ($site !== '' && $site !== 'same-origin')) {
    http_response_code(404);
    exit;
}

$payload = base64_encode(json_encode([
    'url' => $fanvueUrl,
    'exp' => time() + 60,
], JSON_UNESCAPED_SLASHES));

$sig = hash_hmac('sha256', $payload, $secret);
$token = $payload . '.' . $sig;

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');

setcookie('olivia_gate', $token, [
    'expires' => time() + 60,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
