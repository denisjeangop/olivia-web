<?php
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

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

$token = $_COOKIE['olivia_gate'] ?? '';
if ($token === '') {
    deny();
}

$data = json_decode(base64_decode($token, true), true);
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
