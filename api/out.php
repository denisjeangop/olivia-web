<?php
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$secret = getenv('APP_SECRET');

function sign_payload($payload, $secret) {
    return hash_hmac('sha256', $payload, $secret);
}

function deny() {
    http_response_code(404);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    deny();
}

$site = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
if ($site !== '' && $site !== 'same-origin') {
    deny();
}

if (!$secret) {
    deny();
}

$token = $_COOKIE['olivia_gate'] ?? '';
if ($token === '') {
    deny();
}

[$payload, $sig] = array_pad(explode('.', $token, 2), 2, null);
if ($payload === null || $sig === null || !hash_equals(sign_payload($payload, $secret), $sig)) {
    deny();
}

$data = json_decode(base64_decode($payload, true), true);
if (!is_array($data) || !isset($data['url']) || !isset($data['exp'])) {
    deny();
}

if ((int) $data['exp'] < time()) {
    deny();
}

if (!preg_match('#^https?://#i', $data['url'])) {
    deny();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['d' => base64_encode($data['url'])]);
