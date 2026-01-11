<?php
require_once __DIR__ . '/../auth/config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($HCAPTCHA_SITE_KEY)) {
    http_response_code(500);
    echo json_encode(['error' => 'hCaptcha is not configured']);
    exit;
}

echo json_encode(['sitekey' => $HCAPTCHA_SITE_KEY]);
?>
